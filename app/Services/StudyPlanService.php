<?php

namespace App\Services;

use App\Models\SemesterHistory;
use App\Models\StudyPlan;
use App\Models\StudyPlanSemester;
use App\Models\StudyPlanSubject;
use App\Models\Subject;
use App\Models\User;
use App\Services\Plan\PlanDataService;
use App\Services\Plan\SchedulerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudyPlanService
{
    public function __construct(
        protected PlanDataService $dataService,
        protected SchedulerService $scheduler
    ) {}

    // ══════════════════════════════════════════════════════════════════════
    // PUBLIC API
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Tạo kế hoạch học tập mới.
     *
     * @param  int $targetSemesters  Số kỳ mục tiêu (6/7/8/9/10)
     * @return array{plan: StudyPlan, tc_per_sem: int, target_semesters: int, over_semesters: bool, over_semesters_notice: ?string}
     */
    public function generatePlan(int $userId, string $name, int $targetSemesters = 8): array
    {
        return DB::transaction(function () use ($userId, $name, $targetSemesters) {
            $user            = User::findOrFail($userId);
            $targetSemesters = max(6, min(10, $targetSemesters));

            StudyPlan::where('user_id', $userId)->where('is_active', true)->update(['is_active' => false]);

            [$allSubjects, $passedIds, $failedIds, $historySubjectIds] = $this->dataService->loadPlanningData($userId);
            $groupIds = $this->dataService->resolveGroupIds();

            $tcPerSem = $this->recommendTcPerSem($userId, $targetSemesters, $allSubjects, $passedIds, $historySubjectIds);
            $mode     = $tcPerSem >= 20 ? 'fast' : ($tcPerSem <= 14 ? 'slow' : 'normal');

            $plan = StudyPlan::create([
                'user_id'               => $userId,
                'name'                  => $name,
                'mode'                  => $mode,
                'target_semester_count' => $targetSemesters,
                'target_semesters'      => $targetSemesters,
                'tc_per_sem'            => $tcPerSem,
                'is_active'             => true,
                'is_saved'              => true,
            ]);

            $lastHistorySem = $this->buildHistorySemesters($plan, $userId);

            // Sinh viên mới, chưa có điểm, target = 8 kỳ → clone chương trình khung
            if (empty($passedIds) && $lastHistorySem === 0 && $targetSemesters === 8) {
                $this->cloneCurriculumFramework($plan, $allSubjects);
                return [
                    'plan'                  => $plan->load('semesters.subjects.subject'),
                    'tc_per_sem'            => $tcPerSem,
                    'target_semesters'      => $targetSemesters,
                    'over_semesters'        => false,
                    'over_semesters_notice' => null,
                ];
            }

            $toSchedule = $allSubjects->reject(fn($s) =>
                in_array($s->id, $passedIds) && in_array($s->id, $historySubjectIds)
            );
            $toSchedule = $this->pruneElectiveSubjects($toSchedule, $passedIds);

            $passedHistoryIds = array_diff($historySubjectIds, $failedIds);
            $alreadyPlanned   = array_unique(array_merge($passedIds, $passedHistoryIds));
            $startSem         = max(1, $lastHistorySem + 1);

            $schedule  = $this->scheduler->schedule($toSchedule, $alreadyPlanned, $failedIds, $startSem, $tcPerSem, $targetSemesters, $user, $groupIds);
            $totalSems = $this->persistSchedule($plan, $schedule, $passedIds);
            $plan->update(['target_semester_count' => max($totalSems, $targetSemesters)]);

            $maxSemInPlan = empty($schedule) ? 0 : max(array_keys($schedule));
            $overSems     = $maxSemInPlan > $targetSemesters;
            $overSemCount = max(0, $maxSemInPlan - $targetSemesters);
            $overNotice   = null;

            if ($overSems) {
                $overCredits = $overSubjectCount = 0;
                for ($s = $targetSemesters + 1; $s <= $maxSemInPlan; $s++) {
                    foreach ($schedule[$s] ?? [] as $subId) {
                        $sub = $toSchedule->firstWhere('id', $subId);
                        $overCredits += (int)(($sub->credits ?? null) ?? 3);
                        $overSubjectCount++;
                    }
                }
                $overNotice = "Kế hoạch cần thêm {$overSemCount} học kỳ ({$overSubjectCount} môn, {$overCredits} TC) "
                    . "vượt ngoài {$targetSemesters} kỳ mục tiêu. "
                    . "Nguyên nhân: tổng tín chỉ chưa học vượt quá giới hạn {$tcPerSem} TC/kỳ, "
                    . "hoặc ràng buộc tiên quyết buộc một số môn phải dời sang kỳ sau.";
            }

            return [
                'plan'                  => $plan->load('semesters.subjects.subject'),
                'tc_per_sem'            => $tcPerSem,
                'target_semesters'      => $targetSemesters,
                'over_semesters'        => $overSems,
                'over_semesters_count'  => $overSemCount,
                'over_semesters_notice' => $overNotice,
            ];
        });
    }

    /**
     * Tính TC/kỳ khuyến nghị để hoàn tất số tín chỉ còn lại trong $targetSemesters kỳ.
     * Dùng khi tạo kế hoạch (generatePlan) và khi đổi mục tiêu tốt nghiệp (adjustTarget),
     * đảm bảo số học kỳ thực sự co/giãn theo mục tiêu.
     *
     * Có thể truyền sẵn dữ liệu đã nạp để tránh truy vấn lại; nếu không sẽ tự nạp.
     */
    public function recommendTcPerSem(
        int $userId,
        int $targetSemesters,
        ?Collection $allSubjects = null,
        ?array $passedIds = null,
        ?array $historySubjectIds = null
    ): int {
        if ($allSubjects === null) {
            [$allSubjects, $passedIds, , $historySubjectIds] = $this->dataService->loadPlanningData($userId);
        }

        $completedSems = SemesterHistory::where('user_id', $userId)->max('semester_number') ?? 0;
        $remainingSems = max(1, $targetSemesters - $completedSems);

        // Đếm đúng số TC sẽ thực sự học: bỏ môn đã qua và CẮT BỚT môn tự chọn
        // dư thừa (chỉ giữ đủ required_credits mỗi nhóm) — giống hệt tập mà
        // scheduler rải. Nếu đếm cả môn tự chọn chưa cắt, trần TC/kỳ sẽ bị thổi
        // phồng → kỳ đầu ôm quá nhiều, kỳ cuối bị đói.
        $toSchedule = $allSubjects->reject(fn($s) =>
            in_array($s->id, $passedIds) && in_array($s->id, $historySubjectIds)
        );
        $remainingCredits = $this->pruneElectiveSubjects($toSchedule, $passedIds)
            ->sum(fn($s) => (int)($s->credits ?? 3));

        return max(12, min(22, (int) ceil($remainingCredits / $remainingSems)));
    }

    /**
     * Tính TC/kỳ khuyến nghị từ CHÍNH số tín chỉ đang có trong kế hoạch — chính xác
     * hơn recommendTcPerSem khi đổi mục tiêu, vì dùng đúng tập môn sẽ được rải lại
     * (đã chốt môn tự chọn) thay vì dựng lại ước lượng. Nhờ vậy các kỳ cân bằng đều.
     *
     * @param int $fromSem          Học kỳ bắt đầu rải lại (các kỳ trước giữ nguyên)
     * @param int $targetSemesters  Số kỳ mục tiêu
     */
    public function recommendTcPerSemForPlan(StudyPlan $plan, int $fromSem, int $targetSemesters): int
    {
        $plan->loadMissing('semesters.subjects.subject');

        $window  = max(1, $targetSemesters - $fromSem + 1);
        $credits = 0;
        foreach ($plan->semesters as $sem) {
            if ($sem->semester_index < $fromSem) continue;
            foreach ($sem->subjects as $ss) {
                $credits += (int) ($ss->subject->credits ?? 3);
            }
        }

        return max(12, min(22, (int) ceil($credits / $window)));
    }

    /**
     * Tái phân bổ môn học từ học kỳ $fromSem trở đi.
     * Dùng cho: applyAdvisory (redistribute) và applySuggestions.
     *
     * @param  int[] $pinnedSubjectIds  Môn cần ghim vào đúng $fromSem (cho applySuggestions)
     */
    public function redistributeFrom(StudyPlan $plan, int $fromSem, array $pinnedSubjectIds = []): StudyPlan
    {
        return DB::transaction(function () use ($plan, $fromSem, $pinnedSubjectIds) {
            $userId          = $plan->user_id;
            $tcPerSem        = $plan->tc_per_sem ?? 18;
            $targetSemesters = $plan->target_semesters ?? 8;
            $user            = User::findOrFail($userId);
            $groupIds        = $this->dataService->resolveGroupIds();

            [$allSubjects, $passedIds, $failedIds, $historySubjectIds] = $this->dataService->loadPlanningData($userId);

            $plan->load('semesters.subjects');
            $this->deduplicateRetakes($plan);
            $plan->load('semesters.subjects');

            // Gom subject_id đã có trong các kỳ TRƯỚC fromSem
            $planPriorIds = [];
            foreach ($plan->semesters as $sem) {
                if ($sem->semester_index < $fromSem) {
                    foreach ($sem->subjects as $ss) {
                        $planPriorIds[] = $ss->subject_id;
                    }
                }
            }

            $passedHistoryIds = array_diff($historySubjectIds, $failedIds);
            $alreadyPlanned   = array_unique(array_merge(
                array_diff($passedIds, $failedIds),
                $passedHistoryIds,
                $planPriorIds
            ));

            // Xóa tất cả kỳ >= fromSem để rebuild
            $semIds = $plan->semesters->where('semester_index', '>=', $fromSem)->pluck('id');
            StudyPlanSubject::whereIn('study_plan_semester_id', $semIds)->delete();
            StudyPlanSemester::whereIn('id', $semIds)->delete();

            $toSchedule = $allSubjects->reject(fn($s) =>
                in_array($s->id, $alreadyPlanned) && !in_array($s->id, $failedIds)
            );
            $toSchedule = $this->pruneElectiveSubjects($toSchedule, $passedIds);

            $startSem = $fromSem;

            // Ghim môn được chỉ định vào đúng $fromSem (applySuggestions)
            if (!empty($pinnedSubjectIds)) {
                $priorSemIds     = $plan->semesters->where('semester_index', '<', $fromSem)->pluck('id');
                $pinnedCreditMap = Subject::whereIn('id', $pinnedSubjectIds)->pluck('credits', 'id')->toArray();
                $pinnedSem       = StudyPlanSemester::create([
                    'study_plan_id'    => $plan->id,
                    'semester_index'   => $fromSem,
                    'expected_credits' => (int) array_sum(
                        array_map(fn($id) => (int)($pinnedCreditMap[$id] ?? 3), $pinnedSubjectIds)
                    ),
                ]);

                foreach ($pinnedSubjectIds as $subjectId) {
                    $isRetake = in_array($subjectId, $failedIds);
                    if ($isRetake && $priorSemIds->isNotEmpty()) {
                        StudyPlanSubject::whereIn('study_plan_semester_id', $priorSemIds)
                            ->where('subject_id', $subjectId)->delete();
                    }
                    StudyPlanSubject::create([
                        'study_plan_semester_id' => $pinnedSem->id,
                        'subject_id'             => $subjectId,
                        'is_completed'           => false,
                        'is_retake'              => $isRetake,
                    ]);
                }

                $alreadyPlanned = array_merge($alreadyPlanned, $pinnedSubjectIds);
                $toSchedule     = $toSchedule->whereNotIn('id', $pinnedSubjectIds)->values();
                $startSem       = $fromSem + 1;
            }

            $rest      = $this->scheduler->schedule($toSchedule, $alreadyPlanned, $failedIds, $startSem, $tcPerSem, $targetSemesters, $user, $groupIds);
            $totalSems = $this->persistSchedule($plan, $rest, $passedIds);
            $plan->update(['target_semester_count' => max($totalSems, $fromSem)]);

            return $plan->load('semesters.subjects.subject');
        });
    }

    /**
     * Xóa các bản sao retake trùng trong cùng một kế hoạch.
     * Giữ lại bản ở kỳ MỚI NHẤT, xóa các bản ở kỳ cũ hơn.
     */
    public function deduplicateRetakes(StudyPlan $plan): void
    {
        $plan->load('semesters.subjects');

        $groups = [];
        foreach ($plan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                if ($ss->is_retake && !$ss->is_completed && $ss->subject_grade === null) {
                    $groups[$ss->subject_id][] = [
                        'id'             => $ss->id,
                        'semester_index' => $sem->semester_index,
                    ];
                }
            }
        }

        $toDelete = [];
        foreach ($groups as $subjectId => $rows) {
            if (count($rows) <= 1) continue;
            usort($rows, fn($a, $b) => $a['semester_index'] <=> $b['semester_index']);
            array_pop($rows);
            foreach ($rows as $row) {
                $toDelete[] = $row['id'];
            }
        }

        if (!empty($toDelete)) {
            StudyPlanSubject::whereIn('id', $toDelete)->delete();
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════════

    private function buildHistorySemesters(StudyPlan $plan, int $userId): int
    {
        $histories = SemesterHistory::where('user_id', $userId)
            ->with('items.subject')
            ->orderBy('semester_number')
            ->get();

        $lastSem = 0;
        foreach ($histories as $history) {
            $semNum  = $history->semester_number;
            $lastSem = max($lastSem, $semNum);

            $sem = StudyPlanSemester::create([
                'study_plan_id'    => $plan->id,
                'semester_index'   => $semNum,
                'expected_credits' => $history->total_credits ?? 0,
            ]);

            foreach ($history->items as $item) {
                if (!$item->subject) continue;
                StudyPlanSubject::create([
                    'study_plan_semester_id' => $sem->id,
                    'subject_id'             => $item->subject_id,
                    'is_completed'           => $item->status === 'pass',
                    'subject_grade'          => $item->grade,
                ]);
            }
        }

        return $lastSem;
    }

    private function cloneCurriculumFramework(StudyPlan $plan, Collection $allSubjects): void
    {
        $allSubjects = $this->pruneElectiveSubjects($allSubjects, []);
        $grouped     = $allSubjects->groupBy('assigned_semester_index')->sortKeys();
        $maxSem      = 0;

        foreach ($grouped as $semIdx => $subjectsInSem) {
            $maxSem = max($maxSem, (int)$semIdx);
            $sem    = StudyPlanSemester::create([
                'study_plan_id'    => $plan->id,
                'semester_index'   => $semIdx,
                'expected_credits' => $subjectsInSem->sum('credits'),
            ]);
            foreach ($subjectsInSem as $s) {
                StudyPlanSubject::create(['study_plan_semester_id' => $sem->id, 'subject_id' => $s->id]);
            }
        }

        $plan->update(['target_semester_count' => $maxSem]);
    }

    private function persistSchedule(StudyPlan $plan, array $schedule, array $passedIds): int
    {
        if (empty($schedule)) return $plan->semesters()->max('semester_index') ?? 0;

        $allSubjectIds = array_unique(array_merge(...array_values($schedule)));
        $creditMap     = Subject::whereIn('id', $allSubjectIds)->pluck('credits', 'id')->toArray();
        $passedSet     = array_flip($passedIds);

        $maxSem = 0;
        foreach ($schedule as $semIndex => $subjectIds) {
            $credits = array_sum(array_map(fn($id) => (int)($creditMap[$id] ?? 3), $subjectIds));
            $sem     = StudyPlanSemester::create([
                'study_plan_id'    => $plan->id,
                'semester_index'   => (int)$semIndex,
                'expected_credits' => $credits,
            ]);
            foreach ($subjectIds as $subjectId) {
                StudyPlanSubject::create([
                    'study_plan_semester_id' => $sem->id,
                    'subject_id'             => $subjectId,
                    'is_completed'           => isset($passedSet[$subjectId]),
                ]);
            }
            $maxSem = max($maxSem, (int)$semIndex);
        }

        return $maxSem;
    }

    /**
     * Lọc nhóm tự chọn: chỉ giữ đủ TC cần thiết cho mỗi nhóm.
     * Môn bắt buộc (elective_group_id = null) luôn được giữ.
     */
    private function pruneElectiveSubjects(Collection $subjects, array $passedIds): Collection
    {
        $groupProgress = [];
        foreach ($subjects as $subject) {
            if ($subject->elective_group_id && in_array($subject->id, $passedIds)) {
                $gid = $subject->elective_group_id;
                $groupProgress[$gid] = ($groupProgress[$gid] ?? 0) + (int)($subject->credits ?? 3);
            }
        }

        $groupPicked = $groupProgress;

        return $subjects
            ->sortBy('assigned_semester_index')
            ->filter(function ($subject) use (&$groupPicked) {
                if (!$subject->elective_group_id) return true;

                $gid      = $subject->elective_group_id;
                $required = $subject->elective_required_credits ?? PHP_INT_MAX;
                $current  = $groupPicked[$gid] ?? 0;

                if ($current >= $required) return false;

                $groupPicked[$gid] = $current + (int)($subject->credits ?? 3);
                return true;
            })
            ->values();
    }
}
