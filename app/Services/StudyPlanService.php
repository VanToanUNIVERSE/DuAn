<?php

namespace App\Services;

use App\Models\CurriculumSubject;
use App\Models\ProgramGroup;
use App\Models\SemesterHistory;
use App\Models\StudyPlan;
use App\Models\StudyPlanSemester;
use App\Models\StudyPlanSubject;
use App\Models\Subject;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\UserGrade;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudyPlanService
{
    // ══════════════════════════════════════════════════════════════════════
    // PUBLIC API
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Tạo kế hoạch học tập mới hoàn toàn.
     *
     * @return array{plan: StudyPlan, forced_slow: bool, applied_mode: string}
     */
    public function generatePlan(int $userId, string $name, string $mode): array
    {
        return DB::transaction(function () use ($userId, $name, $mode) {
            $user      = User::findOrFail($userId);
            $forcedSlow = false;

            // Kiểm tra GPA — buộc chuyển slow nếu đang yếu
            if ($mode !== 'slow') {
                $progress = (new ProgressService())->evaluateProgress($userId);
                if ($progress['current_gpa'] > 0 && $progress['current_gpa'] < 5.0) {
                    $mode       = 'slow';
                    $forcedSlow = true;
                }
            }

            // Mỗi sinh viên chỉ có 1 active plan
            StudyPlan::where('user_id', $userId)->where('is_active', true)->update(['is_active' => false]);

            $plan = StudyPlan::create([
                'user_id'               => $userId,
                'name'                  => $name,
                'mode'                  => $mode,
                'target_semester_count' => $this->defaultTargetSems($mode),
                'is_active'             => true,
                'is_saved'              => true,
            ]);

            // Load dữ liệu
            [$allSubjects, $passedIds, $failedIds, $historySubjectIds] = $this->loadPlanningData($userId);
            $groupIds = $this->resolveGroupIds();

            // Tái tạo các kỳ lịch sử (đã học qua)
            $lastHistorySem = $this->buildHistorySemesters($plan, $userId);

            // Sinh viên mới, chưa có điểm, mode normal → clone thẳng chương trình khung
            if (empty($passedIds) && $lastHistorySem === 0 && $mode === 'normal') {
                $this->cloneCurriculumFramework($plan, $allSubjects);
                return [
                    'plan'        => $plan->load('semesters.subjects.subject'),
                    'forced_slow' => false,
                    'applied_mode'=> 'normal',
                ];
            }

            // Subjects cần schedule = tất cả chương trình TRỪ những cái đã PASS và có trong lịch sử.
            // Môn FAIL trong lịch sử vẫn cần học lại nên giữ lại trong toSchedule.
            $toSchedule = $allSubjects->reject(fn($s) =>
                in_array($s->id, $passedIds) && in_array($s->id, $historySubjectIds)
            );

            // alreadyPlanned = môn đã pass + môn lịch sử không thất bại.
            // Môn fail KHÔNG được đưa vào plannedSet ban đầu → môn phụ thuộc vào chúng
            // sẽ không được xếp trước kỳ học lại của chúng.
            $passedHistoryIds = array_diff($historySubjectIds, $failedIds);
            $alreadyPlanned   = array_unique(array_merge($passedIds, $passedHistoryIds));
            $startSem         = max(1, $lastHistorySem + 1);

            $schedule     = $this->buildSchedule($toSchedule, $alreadyPlanned, $startSem, $mode, $user, $groupIds);
            $totalSems    = $this->persistSchedule($plan, $schedule, $passedIds);
            $plan->update(['target_semester_count' => max($totalSems, $plan->target_semester_count)]);

            // Kiểm tra overflow: kế hoạch có vượt số kỳ mục tiêu của mode không?
            $defaultTarget  = $this->defaultTargetSems($mode);
            $maxSemInPlan   = empty($schedule) ? 0 : max(array_keys($schedule));
            $overSems       = $maxSemInPlan > $defaultTarget;
            $overSemCount   = max(0, $maxSemInPlan - $defaultTarget);
            $overNotice     = null;

            if ($overSems) {
                $overCredits = 0;
                $overSubjectCount = 0;
                $modeLimit = $this->modeMaxCredits($mode);
                for ($s = $defaultTarget + 1; $s <= $maxSemInPlan; $s++) {
                    foreach ($schedule[$s] ?? [] as $subId) {
                        $sub = $toSchedule->firstWhere('id', $subId);
                        $overCredits += (int)(($sub->credits ?? null) ?? 3);
                        $overSubjectCount++;
                    }
                }
                $overNotice = "Kế hoạch cần thêm {$overSemCount} học kỳ ({$overSubjectCount} môn, {$overCredits} TC) "
                    . "vượt ngoài {$defaultTarget} kỳ mục tiêu. "
                    . "Nguyên nhân: tổng tín chỉ chưa học vượt quá giới hạn {$modeLimit} TC/kỳ, "
                    . "hoặc ràng buộc tiên quyết buộc một số môn phải dời sang kỳ sau.";
            }

            return [
                'plan'               => $plan->load('semesters.subjects.subject'),
                'forced_slow'        => $forcedSlow,
                'applied_mode'       => $mode,
                'over_semesters'     => $overSems,
                'over_semesters_count'  => $overSemCount,
                'over_semesters_notice' => $overNotice,
            ];
        });
    }

    /**
     * Tái phân bổ môn học từ học kỳ $fromSem trở đi.
     * Dùng cho: đổi chế độ (changeMode) và áp dụng gợi ý (applySuggestions).
     *
     * @param  int[]   $pinnedSubjectIds  Danh sách môn cần ghim vào đúng $fromSem (cho applySuggestions)
     */
    public function redistributeFrom(StudyPlan $plan, int $fromSem, array $pinnedSubjectIds = []): StudyPlan
    {
        return DB::transaction(function () use ($plan, $fromSem, $pinnedSubjectIds) {
            $userId   = $plan->user_id;
            $mode     = $plan->mode;
            $user     = User::findOrFail($userId);
            $groupIds = $this->resolveGroupIds();

            [$allSubjects, $passedIds, $failedIds, $historySubjectIds] = $this->loadPlanningData($userId);

            $plan->load('semesters.subjects');

            // Gom subject_id đã có trong các kỳ TRƯỚC fromSem (từ plan)
            $planPriorIds = [];
            foreach ($plan->semesters as $sem) {
                if ($sem->semester_index < $fromSem) {
                    foreach ($sem->subjects as $ss) {
                        $planPriorIds[] = $ss->subject_id;
                    }
                }
            }

            // alreadyPlanned = passed (history + UserGrade) + plan prior — giống generatePlan
            // Thiếu phần này khiến plannedSet rỗng → môn có tiên quyết HK1 không xếp được vào HK2
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

            // toSchedule = tất cả chương trình trừ những đã pass và có trong quá khứ
            // (môn fail vẫn cần học lại nên giữ lại)
            $toSchedule = $allSubjects->reject(fn($s) =>
                in_array($s->id, $alreadyPlanned) && !in_array($s->id, $failedIds)
            );

            $schedule = [];
            $startSem = $fromSem;

            // Ghim môn được chỉ định vào đầu kỳ (applySuggestions)
            if (!empty($pinnedSubjectIds)) {
                $pinnedSubs          = $toSchedule->whereIn('id', $pinnedSubjectIds)->values();
                $schedule[$startSem] = $pinnedSubs->pluck('id')->toArray();
                $alreadyPlanned      = array_merge($alreadyPlanned, $schedule[$startSem]);
                $toSchedule          = $toSchedule->whereNotIn('id', $pinnedSubjectIds)->values();
                $startSem++;
            }

            $rest     = $this->buildSchedule($toSchedule, $alreadyPlanned, $startSem, $mode, $user, $groupIds);
            $schedule = $schedule + $rest;

            $totalSems = $this->persistSchedule($plan, $schedule, $passedIds);
            $plan->update(['target_semester_count' => max($totalSems, $fromSem)]);

            return $plan->load('semesters.subjects.subject');
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    // CORE GREEDY SCHEDULER (dùng chung cho cả generate và redistribute)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Thuật toán Greedy xếp môn học vào từng học kỳ.
     *
     * Thiết kế:
     *  - `$remaining` là PHP array keyed by subject_id → O(1) removal với unset()
     *  - `$plannedSet` là array_flip → O(1) lookup với isset()
     *  - Priority được tính per-semester (phụ thuộc vào $semIndex)
     *
     * @param  Collection $subjects       Danh sách môn cần xếp lịch
     * @param  int[]      $alreadyPlanned Môn đã được tính là "xong" (pass hoặc trong kỳ trước)
     * @param  int        $startSem       Học kỳ bắt đầu xếp
     * @return array<int, int[]>          [semesterIndex => [subjectId, ...]]
     */
    private function buildSchedule(Collection $subjects, array $alreadyPlanned, int $startSem, string $mode, User $user, array $groupIds): array
    {
        // Dùng PHP array thuần để tránh overhead của Illuminate Collection trong vòng lặp nóng
        $remaining   = $subjects->keyBy('id')->all(); // [id => Subject object]
        $subjectMap  = $remaining;                    // reference tĩnh — không bị unset theo vòng lặp
        $plannedSet  = array_flip($alreadyPlanned);   // [id => 0] for O(1) lookup

        // Precompute failed set cho priority
        $failedSet = array_flip(
            UserGrade::where('user_id', $user->id)
                ->get()
                ->filter(fn($g) => $g->grade !== null && $g->grade <= 5.0
                    && !in_array($g->status, ['pass', 'passed']))
                ->pluck('subject_id')
                ->toArray()
        );

        $modeLimit     = $this->modeMaxCredits($mode);
        $targetSemsCap = $this->defaultTargetSems($mode); // cố định theo mode, không adaptive
        $semIndex      = $startSem;
        $maxIterations = $startSem + 24; // safety guard
        $schedule      = [];

        while (!empty($remaining) && $semIndex <= $maxIterations) {
            $isOdd = ($semIndex % 2) !== 0;

            // Phân bổ đều tín chỉ qua các kỳ còn lại trong target.
            // Nếu vượt targetSemsCap, thuật toán tự tạo thêm kỳ → caller sẽ cảnh báo.
            $remCredits    = array_sum(array_map(fn($s) => (int)($s->credits ?? 3), $remaining));
            $remSems       = max(1, $targetSemsCap - $semIndex + 1);
            $targetCredits = min((int)ceil($remCredits / $remSems), $modeLimit);

            // Lọc môn có thể xếp vào học kỳ này
            $available = [];
            foreach ($remaining as $id => $subject) {
                if ($this->canPlace($subject, $plannedSet, $isOdd, $groupIds, $subjects)) {
                    $available[] = $subject;
                }
            }

            if (empty($available)) {
                Log::warning("[Planner] Deadlock sem={$semIndex} user={$user->id}, " . count($remaining) . " môn không thể xếp lịch.");
                break;
            }

            // Sắp xếp theo priority giảm dần
            usort($available, fn($a, $b) =>
                $this->computePriority($b, $failedSet, $groupIds, $semIndex, $mode, $user)
                <=> $this->computePriority($a, $failedSet, $groupIds, $semIndex, $mode, $user)
            );

            // Greedy packing: nhét môn vào học kỳ cho đến khi đầy tín chỉ.
            // Trước khi pick một môn, ước tính corequisite credits của nó để không
            // gây overflow sau khi BFS corequisite chạy.
            $semSubjectIds = [];
            $semCredits    = 0;

            foreach ($available as $subject) {
                $credits = (int)($subject->credits ?? 3);

                // Ước tính số TC corequisite sẽ bị kéo vào cùng kỳ nếu pick môn này
                $coreqEstimate = 0;
                foreach ($subject->corequisites ?? [] as $coreq) {
                    if (isset($remaining[$coreq->id]) && !isset($plannedSet[$coreq->id])) {
                        $coreqEstimate += (int)($remaining[$coreq->id]->credits ?? 3);
                    }
                }

                // Chỉ pick nếu tổng (môn + corequisites ước tính) còn nằm trong giới hạn
                if ($semCredits + $credits + $coreqEstimate <= $targetCredits) {
                    $semSubjectIds[]          = $subject->id;
                    $semCredits              += $credits;
                    $plannedSet[$subject->id] = true; // cập nhật ngay để môn sau có thể check tiên quyết
                    unset($remaining[$subject->id]);  // O(1) removal
                }
            }

            // Tránh vòng lặp vô tận: nếu không môn nào vừa, nhét môn đầu tiên
            if (empty($semSubjectIds)) {
                $first                  = reset($available);
                $semSubjectIds[]        = $first->id;
                $plannedSet[$first->id] = true;
                unset($remaining[$first->id]);
            }

            // ── Corequisite enforcement (BFS) ─────────────────────────────────────
            // Mỗi môn đã pick → kéo tất cả môn song hành của nó vào cùng học kỳ.
            // Lặp BFS để xử lý chain: A↔B, B↔C → A, B, C cùng vào một kỳ.
            $coQueue  = $semSubjectIds;             // hàng đợi để duyệt
            $coPulled = array_flip($semSubjectIds); // tránh xử lý 2 lần

            while (!empty($coQueue)) {
                $checkId = array_shift($coQueue);
                $subObj  = $subjectMap[$checkId] ?? null;
                if (!$subObj) continue;

                foreach ($subObj->corequisites ?? [] as $coreq) {
                    if (isset($coPulled[$coreq->id])) continue;   // đã xử lý
                    $coPulled[$coreq->id] = true;

                    if (isset($plannedSet[$coreq->id])) continue;  // đã ở kỳ trước
                    if (!isset($remaining[$coreq->id])) continue;  // không còn chờ lịch

                    $coreqObj  = $remaining[$coreq->id];
                    $coOffered = $coreqObj->offered_in ?? null;

                    // Kiểm tra ràng buộc chẵn/lẻ — nếu vi phạm thì log và bỏ qua
                    if ($isOdd && $coOffered === '2') {
                        Log::warning("[Planner][Corequisite] {$coreqObj->name} yêu cầu kỳ chẵn nhưng {$subObj->name} đang ở kỳ lẻ {$semIndex}.");
                        continue;
                    }
                    if (!$isOdd && $coOffered === '1') {
                        Log::warning("[Planner][Corequisite] {$coreqObj->name} yêu cầu kỳ lẻ nhưng {$subObj->name} đang ở kỳ chẵn {$semIndex}.");
                        continue;
                    }

                    // Kéo corequisite vào cùng học kỳ (ràng buộc bắt buộc)
                    $semSubjectIds[]             = $coreqObj->id;
                    $semCredits                 += (int)($coreqObj->credits ?? 3);
                    $plannedSet[$coreqObj->id]   = true;
                    unset($remaining[$coreqObj->id]);
                    $coQueue[]                   = $coreqObj->id; // kiểm tra tiếp corequisite của nó
                }
            }

            $schedule[$semIndex] = $semSubjectIds;
            $semIndex++;
        }

        return $schedule;
    }

    /**
     * Kiểm tra môn có thể xếp vào học kỳ hiện tại không.
     * Xét: chẵn/lẻ, tiên quyết tường minh, tiên quyết nhóm (requirement_type).
     */
    private function canPlace(object $subject, array $plannedSet, bool $isOdd, array $groupIds, Collection $allSubjects): bool
    {
        // Ràng buộc học kỳ chẵn/lẻ
        $offeredIn = $subject->offered_in ?? null;
        if ($isOdd  && $offeredIn === '2') return false;
        if (!$isOdd && $offeredIn === '1') return false;

        // Tiên quyết tường minh (SubjectRelation type = 'prerequisite')
        foreach ($subject->prerequisites ?? [] as $prereq) {
            if (!isset($plannedSet[$prereq->id])) return false;
        }

        // Tiên quyết nhóm (requirement_type)
        $req = $subject->requirement_type ?? null;
        if (!$req || $req === 'none') return true;

        $requiredIds = match ($req) {
            'completed_basic'       => $allSubjects->whereIn('program_group_id', $groupIds['basic'])->pluck('id')->all(),
            'completed_major'       => $allSubjects->whereIn('program_group_id', $groupIds['major'])->pluck('id')->all(),
            'completed_specialized' => $allSubjects->whereIn('program_group_id', $groupIds['specialized'])->pluck('id')->all(),
            'completed_all'         => $allSubjects->where('id', '!=', $subject->id)->pluck('id')->all(),
            default                 => [],
        };

        foreach ($requiredIds as $id) {
            if (!isset($plannedSet[$id])) return false;
        }

        return true;
    }

    /**
     * Tính điểm ưu tiên cho môn học trong một học kỳ cụ thể.
     * Điểm cao hơn → được xếp sớm hơn.
     */
    private function computePriority(object $subject, array $failedSet, array $groupIds, int $semIndex, string $mode, User $user): int
    {
        $score = 0;

        // Nhóm chương trình (Đại cương > Cơ sở ngành > phần còn lại)
        $pgId = $subject->program_group_id ?? null;
        if (in_array($pgId, $groupIds['basic']))       $score += 200;
        elseif (in_array($pgId, $groupIds['major']))   $score += 150;

        // Môn rớt → cần học lại càng sớm càng tốt
        if (isset($failedSet[$subject->id]))           $score += 120;

        // Số môn phụ thuộc vào môn này → unlock nhiều môn khác = ưu tiên cao
        $dependents = $subject->relatedRelations?->where('type', 'prerequisite')->count() ?? 0;
        $score += $dependents * 50;

        // Đồ án / Thực tập → schedule sớm nhất khi đủ điều kiện
        if (str_contains($subject->name, 'Đồ án') || str_contains($subject->name, 'Thực tập')) {
            $score += 300;
        }

        // Môn có tiên quyết nhóm → ưu tiên
        $reqType = $subject->requirement_type ?? null;
        if ($reqType && $reqType !== 'none') $score += 30;

        // Định hướng kỹ năng của sinh viên
        if ($user->pref_skill_focus
            && $subject->skillGroup
            && $subject->skillGroup->focus_area === $user->pref_skill_focus) {
            $score += 80;
        }

        // Bám sát học kỳ chuẩn trong chương trình khung (mode normal/slow)
        if ($mode !== 'fast') {
            $assigned = $subject->assigned_semester_index ?? null;
            if ($assigned) {
                if ($assigned === $semIndex)    $score += 200; // đúng học kỳ chuẩn
                elseif ($assigned < $semIndex)  $score += 150; // đã trễ, ưu tiên học bù
                else                            $score -= ($assigned - $semIndex) * 80; // còn sớm
            }
        }

        return $score;
    }

    // ══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Load toàn bộ dữ liệu cần thiết để lập kế hoạch.
     * @return array{Collection, int[], int[], int[]}
     */
    private function loadPlanningData(int $userId): array
    {
        $allUserGrades = UserGrade::where('user_id', $userId)->get()
            ->groupBy('subject_id')
            ->map(fn($g) => $g->sortByDesc('grade')->first());

        $passedIds = $allUserGrades
            ->filter(fn($g) => $g->grade > 5.0 || in_array($g->status, ['pass', 'passed']))
            ->pluck('subject_id')->toArray();

        $failedIds = $allUserGrades
            ->filter(fn($g) => $g->grade !== null && $g->grade <= 5.0
                && !in_array($g->status, ['pass', 'passed']))
            ->pluck('subject_id')->toArray();

        $allSubjects = $this->loadSubjectsForUser($userId);

        $historySubjectIds = SemesterHistory::where('user_id', $userId)
            ->with('items')
            ->get()
            ->flatMap(fn($h) => $h->items->pluck('subject_id'))
            ->unique()->toArray();

        return [$allSubjects, $passedIds, $failedIds, $historySubjectIds];
    }

    /**
     * Load danh sách môn học theo chương trình khung của sinh viên.
     * Mỗi môn có thêm thuộc tính `assigned_semester_index`.
     */
    private function loadSubjectsForUser(int $userId): Collection
    {
        $user        = User::find($userId);
        $frameworkId = null;

        if ($user?->pref_academic_year && $user?->pref_program_type) {
            $program = TrainingProgram::where('academic_year', $user->pref_academic_year)
                ->where('program_type', $user->pref_program_type)
                ->first();
            if ($program) {
                $frameworkId = $program->curriculumFrameworks()->first()?->id;
            }
        }

        if (!$frameworkId) {
            $subjects = Subject::with(['prerequisites', 'corequisites', 'relatedRelations', 'skillGroup'])->get();
            $subjects->each(fn($s) => $s->assigned_semester_index = (int)($s->semester_id ?? 1));
            return $subjects;
        }

        return CurriculumSubject::where('curriculum_framework_id', $frameworkId)
            ->with(['subject.prerequisites', 'subject.corequisites', 'subject.relatedRelations', 'subject.skillGroup', 'semester'])
            ->get()
            ->filter(fn($cs) => $cs->subject !== null)
            ->map(function ($cs) {
                $cs->subject->assigned_semester_index = (int)($cs->semester?->name ?? $cs->subject->semester_id ?? 1);
                return $cs->subject;
            })
            ->unique('id')
            ->values();
    }

    /**
     * Tạo các StudyPlanSemester tương ứng với lịch sử học kỳ đã qua.
     * @return int Số học kỳ lịch sử cao nhất (0 nếu chưa có)
     */
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
     * Clone 100% từ chương trình khung gốc (dành cho sinh viên mới, mode normal).
     */
    private function cloneCurriculumFramework(StudyPlan $plan, Collection $allSubjects): void
    {
        $grouped = $allSubjects->groupBy('assigned_semester_index')->sortKeys();
        $maxSem  = 0;

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
     * Ghi kết quả schedule vào DB.
     * @param  array<int, int[]> $schedule [semesterIndex => [subjectId, ...]]
     * @return int Học kỳ cuối cùng được tạo
     */
    private function persistSchedule(StudyPlan $plan, array $schedule, array $passedIds): int
    {
        if (empty($schedule)) return $plan->semesters()->max('semester_index') ?? 0;

        // Load credits 1 lần duy nhất (tránh N+1)
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
     * Lấy program_group_id cho từng loại nhóm (tra DB 1 lần).
     */
    private function resolveGroupIds(): array
    {
        return [
            'basic'       => ProgramGroup::where('name', 'like', '%Đại cương%')
                ->orWhere('name', 'like', '%Anh văn%')->pluck('id')->toArray(),
            'major'       => ProgramGroup::where('name', 'like', '%Cơ sở ngành%')->pluck('id')->toArray(),
            'specialized' => ProgramGroup::where('name', 'like', '%Chuyên ngành%')->pluck('id')->toArray(),
        ];
    }

    private function modeMaxCredits(string $mode): int
    {
        return match ($mode) { 'fast' => 22, 'slow' => 14, default => 18 };
    }

    private function defaultTargetSems(string $mode): int
    {
        return match ($mode) { 'fast' => 6, 'slow' => 10, default => 8 };
    }
}
