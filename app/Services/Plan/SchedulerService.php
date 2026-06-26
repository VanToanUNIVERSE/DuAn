<?php

namespace App\Services\Plan;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SchedulerService
{
    /**
     * Thuật toán Greedy xếp môn học vào từng học kỳ.
     *
     * Thiết kế:
     *  - `$remaining` keyed by subject_id → O(1) removal với unset()
     *  - `$plannedSet` là array_flip → O(1) lookup với isset()
     *  - Priority được tính per-semester (phụ thuộc vào $semIndex)
     *
     * @param  Collection $subjects       Danh sách môn cần xếp lịch
     * @param  int[]      $alreadyPlanned Môn đã được tính là "xong" (pass hoặc trong kỳ trước)
     * @param  int[]      $failedIds      Môn đã thi rớt (để tăng priority)
     * @param  int        $startSem       Học kỳ bắt đầu xếp
     * @return array<int, int[]>          [semesterIndex => [subjectId, ...]]
     */
    public function schedule(
        Collection $subjects,
        array $alreadyPlanned,
        array $failedIds,
        int $startSem,
        int $tcPerSem,
        int $targetSemesters,
        User $user,
        array $groupIds
    ): array {
        $remaining   = $subjects->keyBy('id')->all();
        $subjectMap  = $remaining;
        $plannedSet  = array_flip($alreadyPlanned);
        $failedSet   = array_flip($failedIds);

        $semIndex         = $startSem;
        $maxIterations    = $startSem + 24;
        $consecutiveEmpty = 0;
        $schedule         = [];

        while (!empty($remaining) && $semIndex <= $maxIterations) {
            $isOdd = ($semIndex % 2) !== 0;

            $available = [];
            foreach ($remaining as $id => $subject) {
                if ($this->canPlace($subject, $plannedSet, $isOdd, $groupIds, $subjects)) {
                    $available[] = $subject;
                }
            }

            if (empty($available)) {
                Log::warning("[Planner] Deadlock sem={$semIndex} user={$user->id}, " . count($remaining) . " môn không thể xếp lịch.");
                $consecutiveEmpty++;
                $semIndex++;
                if ($consecutiveEmpty >= 2) break;
                continue;
            }
            $consecutiveEmpty = 0;

            usort($available, fn($a, $b) =>
                $this->computePriority($b, $failedSet, $groupIds, $semIndex, $user)
                <=> $this->computePriority($a, $failedSet, $groupIds, $semIndex, $user)
            );

            $semSubjectIds = [];
            $semCredits    = 0;

            foreach ($available as $subject) {
                $credits = (int)($subject->credits ?? 3);

                $coreqEstimate = 0;
                foreach ($subject->corequisites ?? [] as $coreq) {
                    if (isset($remaining[$coreq->id]) && !isset($plannedSet[$coreq->id])) {
                        $coreqEstimate += (int)($remaining[$coreq->id]->credits ?? 3);
                    }
                }

                if ($semCredits + $credits + $coreqEstimate <= $tcPerSem) {
                    $semSubjectIds[]          = $subject->id;
                    $semCredits              += $credits;
                    $plannedSet[$subject->id] = true;
                    unset($remaining[$subject->id]);
                }
            }

            // Tránh vòng lặp vô tận: nếu không môn nào vừa, nhét môn đầu tiên
            if (empty($semSubjectIds)) {
                $first                  = reset($available);
                $semSubjectIds[]        = $first->id;
                $plannedSet[$first->id] = true;
                unset($remaining[$first->id]);
            }

            // ── Corequisite enforcement (BFS) ──────────────────────────────────
            $coQueue  = $semSubjectIds;
            $coPulled = array_flip($semSubjectIds);

            while (!empty($coQueue)) {
                $checkId = array_shift($coQueue);
                $subObj  = $subjectMap[$checkId] ?? null;
                if (!$subObj) continue;

                foreach ($subObj->corequisites ?? [] as $coreq) {
                    if (isset($coPulled[$coreq->id])) continue;
                    $coPulled[$coreq->id] = true;

                    if (isset($plannedSet[$coreq->id])) continue;
                    if (!isset($remaining[$coreq->id])) continue;

                    $coreqObj  = $remaining[$coreq->id];
                    $coOffered = $coreqObj->offered_in ?? null;

                    if ($isOdd && $coOffered === '2') {
                        Log::warning("[Planner][Corequisite] {$coreqObj->name} yêu cầu kỳ chẵn nhưng {$subObj->name} đang ở kỳ lẻ {$semIndex}.");
                        continue;
                    }
                    if (!$isOdd && $coOffered === '1') {
                        Log::warning("[Planner][Corequisite] {$coreqObj->name} yêu cầu kỳ lẻ nhưng {$subObj->name} đang ở kỳ chẵn {$semIndex}.");
                        continue;
                    }

                    $semSubjectIds[]           = $coreqObj->id;
                    $semCredits               += (int)($coreqObj->credits ?? 3);
                    $plannedSet[$coreqObj->id] = true;
                    unset($remaining[$coreqObj->id]);
                    $coQueue[]                 = $coreqObj->id;
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
        $offeredIn = $subject->offered_in ?? null;
        if ($isOdd  && $offeredIn === '2') return false;
        if (!$isOdd && $offeredIn === '1') return false;

        foreach ($subject->prerequisites ?? [] as $prereq) {
            if (!isset($plannedSet[$prereq->id])) return false;
        }

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
        if (in_array($pgId, $groupIds['basic']))     $score += 200;
        elseif (in_array($pgId, $groupIds['major'])) $score += 150;

        if (isset($failedSet[$subject->id])) $score += 120;

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

        $assigned = $subject->assigned_semester_index ?? null;
        if ($assigned) {
            if ($assigned === $semIndex)    $score += 200;
            elseif ($assigned < $semIndex)  $score += 150;
            else                            $score -= ($assigned - $semIndex) * 80;
        }

        return $score;
    }
}
