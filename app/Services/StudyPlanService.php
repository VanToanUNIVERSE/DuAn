<?php

namespace App\Services;

use App\Models\SemesterHistory;
use App\Models\StudyPlan;
use App\Models\StudyPlanSemester;
use App\Models\StudyPlanSubject;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserGrade;
use App\Services\Plan\PlanDataService;
use App\Services\Plan\SchedulerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            $user->update(['pref_graduation_semester' => $targetSemesters]);

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

            $toSchedule = $allSubjects->reject(fn($s) =>
                in_array($s->id, $passedIds) && in_array($s->id, $historySubjectIds)
            );
            $toSchedule = $this->pruneElectiveSubjects($toSchedule, $passedIds, $allSubjects);

            $passedHistoryIds = array_diff($historySubjectIds, $failedIds);
            $alreadyPlanned   = array_unique(array_merge($passedIds, $passedHistoryIds));
            $startSem         = max(1, $lastHistorySem + 1);

            $schedule  = $this->scheduler->schedule($toSchedule, $alreadyPlanned, $failedIds, $startSem, $tcPerSem, $targetSemesters, $user, $groupIds);
            $this->assertCompleteSchedule($toSchedule, $schedule);
            $totalSems = $this->persistSchedule($plan, $schedule, $passedIds, $failedIds);
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
        $remainingCredits = $this->pruneElectiveSubjects($toSchedule, $passedIds, $allSubjects)
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
    public function redistributeFrom(
        StudyPlan $plan,
        int $fromSem,
        array $pinnedSubjectIds = [],
        ?int $planningHorizon = null
    ): StudyPlan
    {
        return DB::transaction(function () use ($plan, $fromSem, $pinnedSubjectIds, $planningHorizon) {
            $userId          = $plan->user_id;
            $tcPerSem        = $plan->tc_per_sem ?? 18;
            $targetSemesters = $plan->target_semesters ?? 8;
            $scheduleUntil    = $planningHorizon ?? $targetSemesters;
            $user            = User::findOrFail($userId);
            $groupIds        = $this->dataService->resolveGroupIds();

            [$allSubjects, $passedIds, $failedIds, $historySubjectIds] = $this->dataService->loadPlanningData($userId);

            $plan->load('semesters.subjects');
            $this->deduplicateRetakes($plan);
            $this->deduplicateSubjects($plan);
            $this->restoreHistorySubjects($plan, $userId);
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

            // Môn rớt vẫn còn trong các học kỳ lịch sử trước $fromSem, nên cũng xuất
            // hiện trong $planPriorIds. Nếu đưa chúng vào $alreadyPlanned, scheduler
            // vừa nhận môn đó trong $toSchedule vừa coi nó là "đã xếp": vòng chính
            // sẽ bỏ qua và nhánh chống deadlock chỉ đẩy mỗi kỳ một môn (HK9, HK10…).
            // Loại failedIds SAU KHI hợp nhất mọi nguồn để môn học lại được xếp bình
            // thường, có thể gom nhiều môn trong cùng kỳ theo trần tín chỉ.
            $alreadyPlanned = array_values(array_diff(
                array_unique(array_merge($passedIds, $passedHistoryIds, $planPriorIds)),
                $failedIds
            ));

            // Xóa tất cả kỳ >= fromSem để rebuild
            $semIds = $plan->semesters->where('semester_index', '>=', $fromSem)->pluck('id');
            StudyPlanSubject::whereIn('study_plan_semester_id', $semIds)->delete();
            StudyPlanSemester::whereIn('id', $semIds)->delete();

            $toSchedule = $allSubjects->reject(fn($s) =>
                in_array($s->id, $alreadyPlanned) && !in_array($s->id, $failedIds)
            );
            $toSchedule = $this->pruneElectiveSubjects($toSchedule, $passedIds, $allSubjects);

            $startSem = $fromSem;
            $failedIdsForScheduler = $failedIds;

            // Ghim môn được chỉ định vào đúng $fromSem (applySuggestions)
            if (!empty($pinnedSubjectIds)) {
                $pinnedCreditMap = Subject::whereIn('id', $pinnedSubjectIds)->pluck('credits', 'id')->toArray();
                $pinnedCredits   = (int) array_sum(
                    array_map(fn($id) => (int)($pinnedCreditMap[$id] ?? 3), $pinnedSubjectIds)
                );
                if ($pinnedCredits > $tcPerSem) {
                    throw ValidationException::withMessages([
                        'subject_ids' => "Tổng môn gợi ý {$pinnedCredits} TC vượt giới hạn {$tcPerSem} TC/kỳ.",
                    ]);
                }
                $pinnedSem       = StudyPlanSemester::create([
                    'study_plan_id'    => $plan->id,
                    'semester_index'   => $fromSem,
                    'expected_credits' => $pinnedCredits,
                ]);

                foreach ($pinnedSubjectIds as $subjectId) {
                    $isRetake = in_array($subjectId, $failedIds);
                    StudyPlanSubject::create([
                        'study_plan_semester_id' => $pinnedSem->id,
                        'subject_id'             => $subjectId,
                        'is_completed'           => false,
                        'is_retake'              => $isRetake,
                    ]);
                }

                $alreadyPlanned = array_merge($alreadyPlanned, $pinnedSubjectIds);
                $toSchedule     = $toSchedule->whereNotIn('id', $pinnedSubjectIds)->values();
                // Môn rớt đã được ghim vào kỳ hiện tại giờ là một bước đã xếp trong
                // chuỗi tiên quyết. Không truyền chúng như "failed chưa xếp" nữa,
                // nếu không SchedulerService sẽ loại khỏi plannedSet và khóa toàn bộ
                // các môn phụ thuộc ở những kỳ sau.
                $failedIdsForScheduler = array_values(array_diff($failedIds, $pinnedSubjectIds));
                $startSem       = $fromSem + 1;
            }

            $rest      = $this->scheduler->schedule($toSchedule, $alreadyPlanned, $failedIdsForScheduler, $startSem, $tcPerSem, $scheduleUntil, $user, $groupIds);
            $this->assertCompleteSchedule($toSchedule, $rest);
            $totalSems = $this->persistSchedule($plan, $rest, $passedIds, $failedIds);
            $plan->update(['target_semester_count' => max($totalSems, $fromSem)]);

            return $plan->load('semesters.subjects.subject');
        });
    }

    /**
     * Tìm (hoặc tạo) học kỳ TƯƠNG LAI sớm nhất có index >= $minSem và đúng chẵn/lẻ theo
     * offered_in của môn. Dùng khi thêm môn MỚI (chưa học) mà khung đang ở kỳ đã qua —
     * môn mới phải xếp vào kỳ tương lai, không thể nhét vào kỳ đã có điểm.
     */
    public function findOrCreateFutureSemester(StudyPlan $plan, int $minSem, ?string $offeredIn): StudyPlanSemester
    {
        $plan->loadMissing('semesters');

        $fits = function (int $idx) use ($offeredIn): bool {
            $isOdd = $idx % 2 !== 0;
            if ($offeredIn === '1' && !$isOdd) return false; // chỉ kỳ lẻ
            if ($offeredIn === '2' && $isOdd)  return false; // chỉ kỳ chẵn
            return true;
        };

        $sem = $plan->semesters
            ->where('semester_index', '>=', $minSem)
            ->sortBy('semester_index')
            ->first(fn($s) => $fits((int) $s->semester_index));
        if ($sem) return $sem;

        $next = max($minSem, ((int) $plan->semesters->max('semester_index')) + 1);
        while (!$fits($next)) $next++;

        return StudyPlanSemester::create([
            'study_plan_id'    => $plan->id,
            'semester_index'   => $next,
            'expected_credits' => 0,
        ]);
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

    /**
     * Gộp các học kỳ TRÙNG semester_index của cùng một kế hoạch (bug do 2 request
     * cập nhật điểm chạy song song cùng lúc tự tạo 2 kỳ mới trùng số — mỗi request
     * đọc DB trước khi request kia kịp ghi, nên cả hai đều nghĩ "chưa có kỳ nào ở đây"
     * và cùng tạo kỳ mới). Giữ lại kỳ TẠO SỚM NHẤT (id nhỏ nhất), chuyển hết môn từ
     * các kỳ trùng còn lại sang rồi xóa các kỳ đó — để không bị rải rác mỗi môn một
     * kỳ riêng và để kéo-thả hoạt động lại (kéo-thả định vị kỳ đích theo semester_index).
     */
    public function mergeDuplicateSemesters(StudyPlan $plan): void
    {
        $plan->load('semesters.subjects');

        $bySemIndex = $plan->semesters->groupBy('semester_index');
        foreach ($bySemIndex as $sems) {
            if ($sems->count() <= 1) continue;

            $sorted  = $sems->sortBy('id')->values();
            $keeper  = $sorted->first();
            $dupes   = $sorted->slice(1);

            foreach ($dupes as $dupe) {
                StudyPlanSubject::where('study_plan_semester_id', $dupe->id)
                    ->update(['study_plan_semester_id' => $keeper->id]);
            }

            $keeper->refresh()->load('subjects.subject');
            $keeper->update([
                'expected_credits' => $keeper->subjects->sum(fn($ss) => (int) ($ss->subject->credits ?? 3)),
            ]);

            StudyPlanSemester::whereIn('id', $dupes->pluck('id'))->delete();
        }

        $plan->load('semesters.subjects');
        $this->deduplicateRetakes($plan);
        $this->deduplicateSubjects($plan);
    }

    /**
     * Gỡ các dòng KHÔNG-retake bị TRÙNG của cùng một môn (1 môn chỉ nên có 1 bản gốc
     * + các bản retake riêng). Ưu tiên giữ bản CÓ ĐIỂM; nếu chưa có điểm thì giữ bản ở
     * học kỳ SỚM nhất. Tránh trường hợp 1 môn nằm ở 2 học kỳ làm khung nhóm tự chọn
     * (hoặc thẻ môn) hiển thị lặp 2 lần.
     */
    public function deduplicateSubjects(StudyPlan $plan): void
    {
        $plan->load('semesters.subjects');

        $bySubject = [];
        foreach ($plan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                if ($ss->is_retake) continue; // bản retake là lần học riêng — giữ
                $bySubject[$ss->subject_id][] = [
                    'id'             => $ss->id,
                    'semester_index' => $sem->semester_index,
                    'has_grade'      => $ss->subject_grade !== null,
                ];
            }
        }

        $toDelete = [];
        foreach ($bySubject as $rows) {
            if (count($rows) <= 1) continue;
            usort($rows, function ($a, $b) {
                if ($a['has_grade'] !== $b['has_grade']) {
                    return $b['has_grade'] <=> $a['has_grade']; // có điểm → giữ trước
                }
                return $a['semester_index'] <=> $b['semester_index']; // kỳ sớm hơn → giữ
            });
            array_shift($rows); // giữ dòng đầu, xóa phần còn lại
            foreach ($rows as $r) $toDelete[] = $r['id'];
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

    /**
     * Khôi phục các dòng lịch sử từng bị phiên bản cũ xóa khi chuyển môn rớt sang
     * học kỳ học lại. Lần học cũ phải luôn nằm nguyên ở học kỳ đã hoàn thành.
     */
    private function restoreHistorySubjects(StudyPlan $plan, int $userId): void
    {
        $histories = SemesterHistory::where('user_id', $userId)
            ->with('items.subject')
            ->orderBy('semester_number')
            ->get();

        foreach ($histories as $history) {
            $semester = StudyPlanSemester::firstOrCreate(
                [
                    'study_plan_id'  => $plan->id,
                    'semester_index' => $history->semester_number,
                ],
                ['expected_credits' => $history->total_credits ?? 0]
            );
            $semester->update(['expected_credits' => $history->total_credits ?? 0]);

            foreach ($history->items as $item) {
                if (!$item->subject) continue;

                $row = StudyPlanSubject::where('study_plan_semester_id', $semester->id)
                    ->where('subject_id', $item->subject_id)
                    ->where('is_retake', false)
                    ->first();

                $values = [
                    'subject_grade' => $item->grade,
                    'is_completed'  => $item->status === 'pass',
                ];

                if ($row) {
                    $row->update($values);
                } else {
                    StudyPlanSubject::create([
                        'study_plan_semester_id' => $semester->id,
                        'subject_id'             => $item->subject_id,
                        'is_retake'              => false,
                        ...$values,
                    ]);
                }
            }
        }
    }

    /**
     * Không bao giờ lưu một kế hoạch bị thiếu môn chỉ vì scheduler gặp deadlock.
     * Toàn bộ thao tác đang nằm trong transaction nên exception sẽ khôi phục kế
     * hoạch cũ thay vì để người dùng thấy một lộ trình 5–6 kỳ giả tạo.
     */
    private function assertCompleteSchedule(Collection $subjects, array $schedule): void
    {
        $scheduledIds = collect($schedule)->flatten()->map(fn ($id) => (int) $id)->unique();
        $missing = $subjects->reject(fn ($subject) => $scheduledIds->contains((int) $subject->id));

        if ($missing->isEmpty()) return;

        $preview = $missing->take(5)->pluck('name')->implode(', ');
        $remaining = max(0, $missing->count() - 5);
        $suffix = $remaining > 0 ? " và {$remaining} môn khác" : '';

        throw ValidationException::withMessages([
            'schedule' => "Không thể xếp đủ {$missing->count()} môn ({$preview}{$suffix}). Kế hoạch cũ được giữ nguyên.",
        ]);
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

    /**
     * Tự động xếp một dòng HỌC LẠI cho môn vừa rớt vào học kỳ kế tiếp hợp lệ
     * (tôn trọng học kỳ chẵn/lẻ theo offered_in). Idempotent: nếu đã có học lại
     * chưa chấm điểm cho môn này thì bỏ qua. Lưu lại điểm cũ + kỳ rớt để hiển thị.
     *
     * @return int|null  semester_index của học kỳ chứa học lại, null nếu đã có sẵn
     */
    public function scheduleRetake(StudyPlan $plan, int $subjectId, int $fromSem, ?float $originalGrade): ?int
    {
        $plan->loadMissing('semesters.subjects.subject');
        $semIds = $plan->semesters->pluck('id');

        // Đã có học lại chưa chấm điểm cho môn này → không tạo trùng
        $hasPending = StudyPlanSubject::whereIn('study_plan_semester_id', $semIds)
            ->where('subject_id', $subjectId)
            ->where('is_retake', true)
            ->whereNull('subject_grade')
            ->exists();
        if ($hasPending) return null;

        $subject   = Subject::find($subjectId);
        $offeredIn = $subject?->offered_in; // '1' lẻ, '2' chẵn, null = cả hai
        $newCredits = (int) ($subject?->credits ?? 3);
        $fitsParity = function (int $semIdx) use ($offeredIn): bool {
            $isOdd = $semIdx % 2 !== 0;
            if ($offeredIn === '1' && !$isOdd) return false;
            if ($offeredIn === '2' && $isOdd)  return false;
            return true;
        };

        // Trần TC/kỳ để GOM nhiều môn rớt vào chung một kỳ thay vì mỗi môn tự đẻ một kỳ
        // mới gần như trống (bug: 3 môn rớt → 3 kỳ riêng, mỗi kỳ 1 môn).
        $creditCap = max(12, min(22, $plan->tc_per_sem ?? 18));

        // Tìm học kỳ kế tiếp (> kỳ rớt) có parity phù hợp VÀ còn đủ chỗ tín chỉ
        $targetSem = $plan->semesters
            ->where('semester_index', '>', $fromSem)
            ->sortBy('semester_index')
            ->first(function ($s) use ($fitsParity, $newCredits, $creditCap) {
                if (!$fitsParity($s->semester_index)) return false;
                $used = $s->subjects->sum(fn($ss) => (int) ($ss->subject->credits ?? 3));
                return $used + $newCredits <= $creditCap;
            });

        // Không có → tạo học kỳ mới ở cuối với parity đúng
        if (!$targetSem) {
            $next = ($plan->semesters->max('semester_index') ?? $fromSem) + 1;
            while (!$fitsParity($next)) $next++;
            $targetSem = StudyPlanSemester::create([
                'study_plan_id'    => $plan->id,
                'semester_index'   => $next,
                'expected_credits' => 0,
            ]);
        }

        StudyPlanSubject::create([
            'study_plan_semester_id' => $targetSem->id,
            'subject_id'             => $subjectId,
            'is_completed'           => false,
            'is_retake'              => true,
            'original_attempt_sem'   => $fromSem,
            'original_grade'         => $originalGrade,
        ]);

        return $targetSem->semester_index;
    }

    /**
     * Gỡ các dòng học lại CHƯA chấm điểm của một môn (khi môn chuyển từ rớt → đạt,
     * hoặc xóa điểm). Giữ lại học lại đã có điểm vì đó là lịch sử thật.
     */
    public function removeUngradedRetake(StudyPlan $plan, int $subjectId): void
    {
        $plan->loadMissing('semesters');
        StudyPlanSubject::whereIn('study_plan_semester_id', $plan->semesters->pluck('id'))
            ->where('subject_id', $subjectId)
            ->where('is_retake', true)
            ->whereNull('subject_grade')
            ->delete();
    }

    private function persistSchedule(StudyPlan $plan, array $schedule, array $passedIds, array $failedIds = []): int
    {
        if (empty($schedule)) return $plan->semesters()->max('semester_index') ?? 0;

        $allSubjectIds = array_unique(array_merge(...array_values($schedule)));
        $creditMap     = Subject::whereIn('id', $allSubjectIds)->pluck('credits', 'id')->toArray();
        $passedSet     = array_flip($passedIds);
        $failedSet     = array_flip($failedIds);

        // Điểm rớt cũ (để dòng học lại hiển thị "đã rớt, điểm Y") — giữ dấu qua mỗi lần rải lại
        $failedGradeMap = empty($failedIds) ? [] : UserGrade::where('user_id', $plan->user_id)
            ->whereIn('subject_id', $failedIds)->pluck('grade', 'subject_id')->toArray();

        $maxSem = 0;
        foreach ($schedule as $semIndex => $subjectIds) {
            $credits = array_sum(array_map(fn($id) => (int)($creditMap[$id] ?? 3), $subjectIds));
            $sem     = StudyPlanSemester::create([
                'study_plan_id'    => $plan->id,
                'semester_index'   => (int)$semIndex,
                'expected_credits' => $credits,
            ]);
            foreach ($subjectIds as $subjectId) {
                $isRetake = isset($failedSet[$subjectId]);
                StudyPlanSubject::create([
                    'study_plan_semester_id' => $sem->id,
                    'subject_id'             => $subjectId,
                    'is_completed'           => isset($passedSet[$subjectId]),
                    'is_retake'              => $isRetake,
                    'original_grade'         => $isRetake ? ($failedGradeMap[$subjectId] ?? null) : null,
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
    private function pruneElectiveSubjects(Collection $subjects, array $passedIds, ?Collection $progressSource = null): Collection
    {
        // TC đã đậu của mỗi nhóm phải đếm từ TOÀN BỘ danh sách môn ($progressSource),
        // không chỉ từ $subjects sẽ được rải: ở redistributeFrom, môn tự chọn ĐÃ ĐẬU đã bị
        // loại khỏi $subjects (nằm trong alreadyPlanned) → nếu chỉ đếm trong $subjects thì
        // groupProgress = 0, prune tưởng nhóm chưa có TC nào và GIỮ THÊM môn thay thế
        // → nhóm bị chọn dư (vd 8/4 TC). Mặc định dùng chính $subjects khi không truyền.
        $progressSource ??= $subjects;

        $groupProgress = [];
        foreach ($progressSource as $subject) {
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
