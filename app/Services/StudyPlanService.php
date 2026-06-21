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
     * Tạo kế hoạch học tập mới.
     *
     * @param  int    $targetSemesters  Số kỳ mục tiêu tốt nghiệp (6/7/8/9/10)
     * @return array{plan: StudyPlan, tc_per_sem: int, target_semesters: int, over_semesters: bool, over_semesters_notice: ?string}
     */
    public function generatePlan(int $userId, string $name, int $targetSemesters = 8): array
    {
        return DB::transaction(function () use ($userId, $name, $targetSemesters) {
            $user     = User::findOrFail($userId);
            $targetSemesters = max(6, min(10, $targetSemesters));

            StudyPlan::where('user_id', $userId)->where('is_active', true)->update(['is_active' => false]);

            [$allSubjects, $passedIds, $failedIds, $historySubjectIds] = $this->loadPlanningData($userId);
            $groupIds = $this->resolveGroupIds();

            $lastHistorySem  = 0; // sẽ được set sau khi create plan
            $completedSems   = SemesterHistory::where('user_id', $userId)->max('semester_number') ?? 0;
            $remainingSems   = max(1, $targetSemesters - $completedSems);

            // Tính TC/kỳ từ số tín chỉ còn lại / số kỳ còn lại
            $remainingCredits = $allSubjects
                ->reject(fn($s) => in_array($s->id, $passedIds) && in_array($s->id, $historySubjectIds))
                ->sum(fn($s) => (int)($s->credits ?? 3));
            $tcPerSem = (int) ceil($remainingCredits / $remainingSems);
            $tcPerSem = max(12, min(22, $tcPerSem));

            // Derive mode for backward compat (hiển thị UI cũ nếu cần)
            $mode = $tcPerSem >= 20 ? 'fast' : ($tcPerSem <= 14 ? 'slow' : 'normal');

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

            $passedHistoryIds = array_diff($historySubjectIds, $failedIds);
            $alreadyPlanned   = array_unique(array_merge($passedIds, $passedHistoryIds));
            $startSem         = max(1, $lastHistorySem + 1);

            $schedule  = $this->buildSchedule($toSchedule, $alreadyPlanned, $startSem, $tcPerSem, $targetSemesters, $user, $groupIds);
            $totalSems = $this->persistSchedule($plan, $schedule, $passedIds);
            $plan->update(['target_semester_count' => max($totalSems, $targetSemesters)]);

            // Kiểm tra overflow
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
     * Tính toán tư vấn điều chỉnh TC/kỳ sau khi sinh viên hoàn thành một học kỳ.
     *
     * @return array{recommend: string, reason: string, current_tc_per_sem: int,
     *               recommended_tc_per_sem: int, new_graduation_estimate: ?int, semesters_delta: int}
     */
    public function computeAdvisory(StudyPlan $plan, int $userId): array
    {
        $histories = SemesterHistory::where('user_id', $userId)->orderBy('semester_number')->get();
        if ($histories->isEmpty()) {
            return ['recommend' => 'maintain', 'reason' => 'Chưa có dữ liệu học kỳ.', 'current_tc_per_sem' => $plan->tc_per_sem, 'recommended_tc_per_sem' => $plan->tc_per_sem, 'new_graduation_estimate' => null, 'semesters_delta' => 0];
        }

        [$allSubjects, $passedIds, $failedIds] = $this->loadPlanningData($userId);
        $completedSems   = $histories->count();
        $remainingSems   = max(1, $plan->target_semesters - $completedSems);
        $remainingCredits = $allSubjects
            ->reject(fn($s) => in_array($s->id, $passedIds))
            ->sum(fn($s) => (int)($s->credits ?? 3));

        // GPA: kỳ gần nhất (60%) + trung bình tích lũy (40%) → phản ánh xu hướng mà không bỏ qua lịch sử
        $avgGpa      = round($histories->avg('gpa'), 2);
        $lastGpa     = round((float) $histories->last()->gpa, 2);
        $effectiveGpa = round($lastGpa * 0.6 + $avgGpa * 0.4, 2);
        $currentTc   = $plan->tc_per_sem;

        // Tính TC/kỳ cần thiết để đạt đúng mục tiêu
        $neededTc = $remainingSems > 0 ? (int) ceil($remainingCredits / $remainingSems) : $currentTc;

        // Ngữ cảnh GPA để hiển thị trong lý do
        $gpaContext = $completedSems > 1
            ? "GPA kỳ gần nhất {$lastGpa} (trung bình tích lũy {$avgGpa})"
            : "GPA {$lastGpa}";

        // Quyết định dựa trên effectiveGpa để tránh bị lừa bởi 1 kỳ bất thường
        if ($effectiveGpa >= 7.0 && $currentTc < 22) {
            $newTc        = min(22, $currentTc + max(2, (int)($currentTc * 0.15)));
            $newSems      = (int) ceil($remainingCredits / $newTc) + $completedSems;
            $delta        = $plan->target_semesters - $newSems;
            $earlyBy      = $delta > 0 ? " Dự kiến tốt nghiệp sớm hơn {$delta} học kỳ so với mục tiêu." : '';
            return [
                'recommend'               => 'increase',
                'reason'                  => "{$gpaContext} — học lực tốt, bạn hoàn toàn có thể tăng tải lên {$newTc} TC/kỳ.{$earlyBy}",
                'current_tc_per_sem'      => $currentTc,
                'recommended_tc_per_sem'  => $newTc,
                'new_graduation_estimate' => $newSems,
                'semesters_delta'         => $delta,
            ];
        }

        if ($effectiveGpa < 5.5 || $neededTc > $currentTc * 1.15) {
            // Học yếu hoặc đang tụt hậu → gợi ý giảm
            $newTc        = max(12, $currentTc - max(2, (int)(($currentTc * 0.15))));
            $newSems      = (int) ceil($remainingCredits / $newTc) + $completedSems;
            $delta        = $newSems - $plan->target_semesters;
            $tradeOff     = $delta > 0
                ? " Tuy nhiên, điều này sẽ khiến bạn tốt nghiệp trễ hơn mục tiêu ban đầu {$delta} học kỳ (dự kiến học kỳ {$newSems})."
                : '';
            $trigger      = $effectiveGpa < 5.5
                ? "{$gpaContext} — học lực yếu"
                : "{$gpaContext} — cần đến {$neededTc} TC/kỳ để đúng tiến độ trong khi hiện tại chỉ có {$currentTc} TC/kỳ";
            return [
                'recommend'               => 'decrease',
                'reason'                  => "{$trigger}. Giảm xuống {$newTc} TC/kỳ giúp tránh nguy cơ học lại.{$tradeOff}",
                'current_tc_per_sem'      => $currentTc,
                'recommended_tc_per_sem'  => $newTc,
                'new_graduation_estimate' => $newSems,
                'semesters_delta'         => $delta,
            ];
        }

        return [
            'recommend'               => 'maintain',
            'reason'                  => "{$gpaContext} — tiến độ ổn định, phù hợp với kế hoạch hiện tại. Tiếp tục duy trì.",
            'current_tc_per_sem'      => $currentTc,
            'recommended_tc_per_sem'  => $currentTc,
            'new_graduation_estimate' => null,
            'semesters_delta'         => 0,
        ];
    }

    /**
     * Áp dụng tư vấn: cập nhật tc_per_sem và tùy chọn rải lại môn học.
     *
     * @param  bool $redistribute  true = rải lại tự động, false = chỉ cập nhật giới hạn
     */
    public function applyAdvisory(StudyPlan $plan, int $userId, int $newTcPerSem, bool $redistribute): StudyPlan
    {
        $newTcPerSem = max(12, min(22, $newTcPerSem));
        $mode = $newTcPerSem >= 20 ? 'fast' : ($newTcPerSem <= 14 ? 'slow' : 'normal');

        $plan->update(['tc_per_sem' => $newTcPerSem, 'mode' => $mode]);

        if ($redistribute) {
            $currentSem = $this->detectCurrentSemesterIndex($plan, $userId);
            return $this->redistributeFrom($plan->fresh(), $currentSem);
        }

        return $plan->load('semesters.subjects.subject');
    }

    /**
     * Tái phân bổ môn học từ học kỳ $fromSem trở đi.
     * Dùng cho: applyAdvisory (redistribute) và applySuggestions.
     *
     * @param  int[]   $pinnedSubjectIds  Danh sách môn cần ghim vào đúng $fromSem (cho applySuggestions)
     */
    public function redistributeFrom(StudyPlan $plan, int $fromSem, array $pinnedSubjectIds = []): StudyPlan
    {
        return DB::transaction(function () use ($plan, $fromSem, $pinnedSubjectIds) {
            $userId          = $plan->user_id;
            $tcPerSem        = $plan->tc_per_sem ?? 18;
            $targetSemesters = $plan->target_semesters ?? 8;
            $user            = User::findOrFail($userId);
            $groupIds        = $this->resolveGroupIds();

            [$allSubjects, $passedIds, $failedIds, $historySubjectIds] = $this->loadPlanningData($userId);

            $plan->load('semesters.subjects');

            // Dọn sạch retake trùng trước khi rebuild (sửa dữ liệu cũ bị duplicate)
            $this->deduplicateRetakes($plan);
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

            $startSem = $fromSem;

            // Ghim môn được chỉ định vào đúng $fromSem (applySuggestions)
            if (!empty($pinnedSubjectIds)) {
                $priorSemIds = $plan->semesters->where('semester_index', '<', $fromSem)->pluck('id');

                // Tạo học kỳ mục tiêu
                $pinnedCreditMap = Subject::whereIn('id', $pinnedSubjectIds)->pluck('credits', 'id')->toArray();
                $pinnedSem = StudyPlanSemester::create([
                    'study_plan_id'    => $plan->id,
                    'semester_index'   => $fromSem,
                    'expected_credits' => (int) array_sum(array_map(fn($id) => (int)($pinnedCreditMap[$id] ?? 3), $pinnedSubjectIds)),
                ]);

                foreach ($pinnedSubjectIds as $subjectId) {
                    $isRetake = in_array($subjectId, $failedIds);

                    if ($isRetake && $priorSemIds->isNotEmpty()) {
                        // Môn học lại: xóa TẤT CẢ entries cũ của môn này trong các kỳ trước.
                        // Plan chỉ giữ 1 bản mỗi môn — điểm lịch sử đã lưu trong UserGrade/SemesterHistory.
                        StudyPlanSubject::whereIn('study_plan_semester_id', $priorSemIds)
                            ->where('subject_id', $subjectId)
                            ->delete();
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

            $rest      = $this->buildSchedule($toSchedule, $alreadyPlanned, $startSem, $tcPerSem, $targetSemesters, $user, $groupIds);
            $totalSems = $this->persistSchedule($plan, $rest, $passedIds);
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
    private function buildSchedule(Collection $subjects, array $alreadyPlanned, int $startSem, int $tcPerSem, int $targetSemesters, User $user, array $groupIds): array
    {
        $remaining   = $subjects->keyBy('id')->all();
        $subjectMap  = $remaining;
        $plannedSet  = array_flip($alreadyPlanned);

        $failedSet = array_flip(
            UserGrade::where('user_id', $user->id)
                ->get()
                ->filter(fn($g) => $g->grade !== null && $g->grade <= 5.0
                    && !in_array($g->status, ['pass', 'passed']))
                ->pluck('subject_id')
                ->toArray()
        );

        $modeLimit     = $tcPerSem;        // giới hạn TC/kỳ = input trực tiếp
        $targetSemsCap = $targetSemesters; // cố định theo mục tiêu, không adaptive
        $semIndex      = $startSem;
        $maxIterations = $startSem + 24; // safety guard
        $schedule      = [];

        while (!empty($remaining) && $semIndex <= $maxIterations) {
            $isOdd = ($semIndex % 2) !== 0;

            // Luôn cố gắng pack đúng tcPerSem mỗi kỳ.
            // Không dùng adaptive (ceil(rem/sems)) vì sẽ kéo xuống thấp hơn giới hạn user chọn.
            $targetCredits = $modeLimit;

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
                $this->computePriority($b, $failedSet, $groupIds, $semIndex, $user)
                <=> $this->computePriority($a, $failedSet, $groupIds, $semIndex, $user)
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
    private function computePriority(object $subject, array $failedSet, array $groupIds, int $semIndex, User $user): int
    {
        $score = 0;

        $pgId = $subject->program_group_id ?? null;
        if (in_array($pgId, $groupIds['basic']))       $score += 200;
        elseif (in_array($pgId, $groupIds['major']))   $score += 150;

        if (isset($failedSet[$subject->id]))           $score += 120;

        $dependents = $subject->relatedRelations?->where('type', 'prerequisite')->count() ?? 0;
        $score += $dependents * 50;

        if (str_contains($subject->name, 'Đồ án') || str_contains($subject->name, 'Thực tập')) {
            $score += 300;
        }

        $reqType = $subject->requirement_type ?? null;
        if ($reqType && $reqType !== 'none') $score += 30;

        if ($user->pref_skill_focus
            && $subject->skillGroup
            && $subject->skillGroup->focus_area === $user->pref_skill_focus) {
            $score += 80;
        }

        // Bám sát học kỳ chuẩn trong chương trình khung
        $assigned = $subject->assigned_semester_index ?? null;
        if ($assigned) {
            if ($assigned === $semIndex)    $score += 200;
            elseif ($assigned < $semIndex)  $score += 150;
            else                            $score -= ($assigned - $semIndex) * 80;
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

    /**
     * Trả về semester_index hiện tại (sau các kỳ history, trước các kỳ chưa học).
     */
    private function detectCurrentSemesterIndex(StudyPlan $plan, int $userId): int
    {
        $lastHistory = SemesterHistory::where('user_id', $userId)->max('semester_number') ?? 0;
        return max(1, $lastHistory + 1);
    }

    /**
     * Xóa các bản sao retake trùng trong cùng một kế hoạch.
     * Với mỗi subject_id xuất hiện > 1 lần dưới dạng retake chưa hoàn thành,
     * giữ lại bản ở kỳ MỚI NHẤT (vì đó là lần học lại gần nhất được lên lịch),
     * xóa các bản ở kỳ cũ hơn.
     */
    public function deduplicateRetakes(StudyPlan $plan): void
    {
        $plan->load('semesters.subjects');

        // Chỉ gộp các entry retake CHƯA CÓ ĐIỂM (subject_grade = null).
        // Entry có điểm (dù rớt) = lịch sử lần học thực tế → GIỮ NGUYÊN, không xóa.
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
            // Giữ bản ở kỳ MỚI NHẤT (lịch dự kiến gần nhất), xóa các bản ở kỳ cũ hơn
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

    private function modeMaxCredits(string $mode): int
    {
        return match ($mode) { 'fast' => 22, 'slow' => 14, default => 18 };
    }

    private function defaultTargetSems(string $mode): int
    {
        return match ($mode) { 'fast' => 6, 'slow' => 10, default => 8 };
    }
}
