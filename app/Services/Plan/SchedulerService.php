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
        $remaining  = $subjects->keyBy('id')->all();
        $subjectMap = $remaining;

        // Phòng thủ ở tầng scheduler: môn rớt luôn phải được xem là chưa hoàn
        // thành, kể cả caller vô tình truyền nó trong $alreadyPlanned.
        $plannedSet = array_flip(array_diff($alreadyPlanned, $failedIds));
        $failedSet  = array_flip($failedIds);

        $semIndex         = $startSem;
        $maxIterations    = $startSem + 24;
        $consecutiveEmpty = 0;
        $schedule         = [];

        while (!empty($remaining) && $semIndex <= $maxIterations) {
            $isOdd = ($semIndex % 2) !== 0;

            // ── Trần động: rải đều TC còn lại trên số học kỳ còn lại trong mục tiêu ──
            // balancedCap = TC còn lại / số kỳ còn lại = mức chia đều lý tưởng cho đúng
            // số kỳ mục tiêu. Đây là trần CHÍNH, KHÔNG kẹp bởi tcPerSem:
            //  - Kỳ đầu bị tiên quyết chặn → nạp thiếu → các kỳ sau balancedCap tự tăng
            //    để "đuổi kịp", nhờ vậy tổng vẫn khớp mục tiêu, không tràn sang kỳ dư.
            //  - Nếu kẹp min(tcPerSem, …) như trước: khi tcPerSem thấp, trần không tăng nổi
            //    → lịch tràn quá số kỳ mục tiêu, kỳ cuối bị đói TC (bug lệch tín chỉ).
            // tcPerSem giờ chỉ dùng để CHỌN số kỳ mục tiêu (fast/normal/slow), không kẹp trần.
            // hardMax chặn trên để tránh nhồi quá tải khi mục tiêu đặt quá ngắn.
            $remainingCredits = array_sum(array_map(fn($s) => (int)($s->credits ?? 3), $remaining));
            $semestersLeft    = max(1, $targetSemesters - $semIndex + 1);
            $balancedCap      = (int) ceil($remainingCredits / $semestersLeft);
            $hardMax          = 22;
            $effectiveCap     = min($hardMax, max(1, $balancedCap));

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
                // $available là snapshot đầu kỳ; một môn có thể đã bị KÉO vào kỳ này với tư
                // cách môn song hành của môn xử lý trước đó → bỏ qua để không xếp LẶP.
                if (isset($plannedSet[$subject->id]) || !isset($remaining[$subject->id])) continue;

                $credits = (int)($subject->credits ?? 3);

                // Gom các môn song hành TRỰC TIẾP (cùng chẵn/lẻ) sẽ bị kéo theo ngay,
                // để tính ĐỦ tín chỉ vào trần — tránh kỳ đầu vượt trần rồi đói kỳ cuối.
                $coreqIds     = [];
                $coreqCredits = 0;
                foreach ($subject->corequisites ?? [] as $coreq) {
                    if (!isset($remaining[$coreq->id]) || isset($plannedSet[$coreq->id])) continue;
                    $co        = $remaining[$coreq->id];
                    $coOffered = $co->offered_in ?? null;
                    if ($isOdd && $coOffered === '2') continue;   // môn TH chỉ mở kỳ chẵn
                    if (!$isOdd && $coOffered === '1') continue;   // môn TH chỉ mở kỳ lẻ
                    $coreqIds[]    = $coreq->id;
                    $coreqCredits += (int)($co->credits ?? 3);
                }

                if ($semCredits + $credits + $coreqCredits <= $effectiveCap) {
                    $semSubjectIds[]          = $subject->id;
                    $semCredits              += $credits + $coreqCredits; // tính LUÔN môn song hành
                    $plannedSet[$subject->id] = true;
                    unset($remaining[$subject->id]);

                    foreach ($coreqIds as $cid) {                  // kéo môn song hành vào cùng kỳ ngay
                        $semSubjectIds[]  = $cid;
                        $plannedSet[$cid] = true;
                        unset($remaining[$cid]);
                    }
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

            // ── Môn "chốt" đủ điều kiện NHỜ coursework vừa xếp kỳ này ────────────
            // Thực tập/Khóa luận (requirement_type completed_*) trở nên khả thi ngay khi
            // môn coursework cuối được xếp ở CHÍNH kỳ này. Vì $available được chốt từ ĐẦU kỳ
            // (trước khi đặt), chúng sẽ bị đẩy sang kỳ SAU (dư ngoài mục tiêu). Xét lại với
            // plannedSet đã cập nhật để xếp luôn vào kỳ này → gom vào cuối lộ trình, tránh
            // đẻ thêm học kỳ chỉ để chứa khóa luận.
            $lateChanged = true;
            while ($lateChanged) {
                $lateChanged = false;
                foreach ($remaining as $id => $subject) {
                    $req = $subject->requirement_type ?? null;
                    if (!$req || $req === 'none') continue;               // chỉ môn chốt
                    if (!$this->canPlace($subject, $plannedSet, $isOdd, $groupIds, $subjects)) continue;

                    $credits = (int) ($subject->credits ?? 3);
                    if ($semCredits + $credits > $effectiveCap) continue;

                    $semSubjectIds[]  = $id;
                    $semCredits      += $credits;
                    $plannedSet[$id]  = true;
                    unset($remaining[$id]);
                    $lateChanged      = true;                             // mở khoá môn chốt phụ thuộc nhau
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

        // Các môn "chốt" (Thực tập, Khóa luận…) có requirement_type completed_* và thường
        // thuộc chính nhóm chuyên ngành. Chúng chỉ nên yêu cầu COURSEWORK thường hoàn thành,
        // KHÔNG gate lẫn nhau — nếu không sẽ:
        //  - tự yêu cầu chính mình (thuộc nhóm đang xét) → không bao giờ xếp được;
        //  - Thực tập ⟷ Khóa luận yêu cầu chéo nhau → deadlock vòng → cả hai bị bỏ rơi.
        // → Loại self VÀ mọi môn cũng có requirement_type (gated) khỏi tập yêu cầu.
        $isGated = fn($s) => !empty($s->requirement_type) && $s->requirement_type !== 'none';

        $requiredIds = match ($req) {
            'completed_basic'       => $allSubjects->whereIn('program_group_id', $groupIds['basic']),
            'completed_major'       => $allSubjects->whereIn('program_group_id', $groupIds['major']),
            'completed_specialized' => $allSubjects->whereIn('program_group_id', $groupIds['specialized']),
            'completed_all'         => $allSubjects,
            default                 => collect(),
        };
        $requiredIds = $requiredIds
            ->where('id', '!=', $subject->id)
            ->reject($isGated)
            ->pluck('id')->all();

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
