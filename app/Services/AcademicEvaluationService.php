<?php

namespace App\Services;

use App\Models\User;

class AcademicEvaluationService
{
    public function __construct(
        protected ProgressService $progressService,
        protected SkillProfilingService $skillService
    ) {}

    /**
     * Đánh giá toàn diện tiến độ học tập và đề xuất điều chỉnh kế hoạch.
     *
     * Ma trận tư vấn: GPA (4 mức) × Tiến độ (2 mức) = 8 trường hợp
     *
     * Mức GPA:
     *   - good     : GPA >= 7.5
     *   - average  : 6.0 <= GPA < 7.5
     *   - weak     : 5.0 <= GPA < 6.0
     *   - critical : GPA < 5.0
     *
     * Mức Tiến độ:
     *   - ontrack  : Tiến độ bình thường hoặc nhanh hơn kỳ vọng
     *   - behind   : Chậm hơn kỳ vọng (ít tín chỉ tích lũy so với số kỳ đã học)
     *
     * @param int    $userId
     * @param string $currentMode           Chế độ kế hoạch hiện tại (normal/fast/slow/dynamic)
     * @param int    $currentTargetSemesters Tổng số học kỳ mục tiêu
     * @param int    $currentSem             Học kỳ hiện tại (đã hoàn thành xong)
     * @return array
     */
    public function evaluate(
        int $userId,
        string $currentMode = 'normal',
        int $currentTargetSemesters = 8,
        int $currentSem = 1
    ): array {
        $progress = $this->progressService->evaluateProgress($userId);

        $gpa              = floatval($progress['current_gpa']);
        $earnedCredits    = $progress['earned_credits'];
        $totalRequired    = $progress['total_required_credits'];
        $remainingCredits = max(0, $totalRequired - $earnedCredits);
        $completionPct    = $progress['completion_percentage'];
        $passedCount      = $progress['passed_subjects_count'];
        $failedCount      = $progress['failed_subjects_count'];

        // ── Phân loại mức GPA ──────────────────────────────────────────────
        $gpaLevel = match (true) {
            $gpa >= 7.5 => 'good',      // GPA tốt
            $gpa >= 6.0 => 'average',   // GPA trung bình
            $gpa >= 5.0 => 'weak',      // GPA yếu
            default     => 'critical',  // Nguy hiểm (bao gồm GPA = 0 khi chưa có điểm)
        };

        // ── Đánh giá tiến độ so với kỳ vọng ───────────────────────────────
        // Số tín chỉ kỳ vọng đã tích lũy được đến thời điểm này
        $semsElapsed         = max(1, $currentSem - 1); // Số HK đã hoàn thành
        $expectedCreditsPerSem = $totalRequired / max(1, $currentTargetSemesters);
        $expectedEarned      = $expectedCreditsPerSem * $semsElapsed;
        $progressGap         = $earnedCredits - $expectedEarned; // dương = nhanh, âm = chậm

        // Tiến độ bị coi là "chậm" khi thiếu hơn 10% tín chỉ kỳ vọng
        $behindThreshold = $expectedEarned * 0.10;
        $progressLevel   = ($progressGap >= -$behindThreshold) ? 'ontrack' : 'behind';

        // Số tín chỉ và học kỳ còn lại
        $remainingSems         = max(1, $currentTargetSemesters - $currentSem + 1);
        $projectedCreditsPerSem = $remainingCredits / $remainingSems;
        $projectedRounded       = (int) round($projectedCreditsPerSem); // hiển thị (tránh số lẻ 13.125)

        // Tỷ lệ pass
        $totalGraded = max(1, $passedCount + $failedCount);
        $passRate    = $passedCount / $totalGraded;

        // ── Chỉ đánh giá khi sinh viên đã có ít nhất 1 học kỳ hoàn thành ──
        if ($earnedCredits === 0 && $failedCount === 0) {
            return $this->buildResult(
                'KEEP',
                'Bạn chưa có dữ liệu học tập. Hãy nhập điểm hoặc hoàn tất học kỳ đầu tiên để nhận tư vấn.',
                $currentMode,
                $currentTargetSemesters,
                false,
                $gpa,
                $gpaLevel,
                $progressLevel
            );
        }

        // ══════════════════════════════════════════════════════════════════
        // MA TRẬN TƯ VẤN: 8 TRƯỜNG HỢP (GPA × TIẾN ĐỘ)
        // ══════════════════════════════════════════════════════════════════
        // ── Phân tích định hướng kỹ năng ──────────────────────────────
        $user = User::find($userId);
        $skillFocus = $user?->pref_skill_focus;
        $skillAnalysis = $skillFocus ? $this->skillService->analyzeSkillProgress($userId, $skillFocus) : null;

        $result = match ([$gpaLevel, $progressLevel]) {

            // ── CASE 1: GPA tốt + Đúng/Nhanh tiến độ → Tăng tốc hoặc duy trì ──
            ['good', 'ontrack'] => $this->caseGoodOntrack(
                $gpa, $passRate, $earnedCredits, $remainingCredits,
                $remainingSems, $currentSem, $currentTargetSemesters, $currentMode
            ),

            // ── CASE 2: GPA tốt + Chậm tiến độ → Tăng tốc hợp lý ──
            ['good', 'behind'] => $this->caseGoodBehind(
                $gpa, $remainingCredits, $remainingSems,
                $currentSem, $currentTargetSemesters, $currentMode, $progressGap
            ),

            // ── CASE 3: GPA trung bình + Đúng tiến độ → Duy trì ──
            ['average', 'ontrack'] => $this->buildResult(
                'KEEP',
                "GPA của bạn là {$gpa} (trung bình khá) và tiến độ đúng kế hoạch. Hệ thống khuyến nghị duy trì nhịp học hiện tại ({$projectedRounded} TC/kỳ). Tập trung nâng điểm các môn đang học.",
                $currentMode,
                $currentTargetSemesters,
                false,
                $gpa,
                $gpaLevel,
                $progressLevel
            ),

            // ── CASE 4: GPA trung bình + Chậm tiến độ → Cân bằng ──
            ['average', 'behind'] => $this->caseAverageBehind(
                $gpa, $remainingCredits, $remainingSems,
                $currentSem, $currentTargetSemesters, $currentMode, $projectedCreditsPerSem
            ),

            // ── CASE 5: GPA yếu + Đúng tiến độ → Giảm tải ──
            ['weak', 'ontrack'] => $this->caseWeakOntrack(
                $gpa, $failedCount, $remainingCredits, $remainingSems,
                $currentSem, $currentTargetSemesters, $currentMode
            ),

            // ── CASE 6: GPA yếu + Chậm tiến độ → Cân bằng GPA và tiến độ ──
            ['weak', 'behind'] => $this->caseWeakBehind(
                $gpa, $failedCount, $remainingCredits, $remainingSems,
                $currentSem, $currentTargetSemesters, $currentMode
            ),

            // ── CASE 7: GPA nguy hiểm + Đúng tiến độ → Giảm tải mạnh ──
            ['critical', 'ontrack'] => $this->caseCriticalOntrack(
                $gpa, $failedCount, $remainingCredits, $remainingSems,
                $currentSem, $currentTargetSemesters, $currentMode
            ),

            // ── CASE 8: GPA nguy hiểm + Chậm tiến độ → Kéo dài lộ trình ──
            ['critical', 'behind'] => $this->caseCriticalBehind(
                $gpa, $failedCount, $remainingCredits, $remainingSems,
                $currentSem, $currentTargetSemesters, $currentMode, $projectedCreditsPerSem
            ),

            // ── Fallback ──
            default => $this->buildResult(
                'KEEP',
                'Kế hoạch hiện tại phù hợp. Không cần điều chỉnh.',
                $currentMode,
                $currentTargetSemesters,
                false,
                $gpa,
                $gpaLevel,
                $progressLevel
            ),
        };

        // Gắn phân tích định hướng kỹ năng vào kết quả
        $result['skill_focus']    = $skillFocus;
        $result['skill_analysis'] = $skillAnalysis;
        $result['skill_message']  = $skillAnalysis ? $this->skillService->buildSkillMessage($skillAnalysis) : null;

        // Số liệu chuẩn để FE hiển thị NHẤT QUÁN với tư vấn (tránh modal hiện 2 con số TC/kỳ mâu thuẫn)
        $result['earned_credits']         = $earnedCredits;
        $result['remaining_credits']      = $remainingCredits;
        $result['total_required_credits'] = $totalRequired;
        $result['projected_tc_per_sem']   = $projectedRounded;

        return $result;
    }

    // ══════════════════════════════════════════════════════════════════════
    // CÁC HANDLER CHO TỪNG TRƯỜNG HỢP
    // ══════════════════════════════════════════════════════════════════════

    /**
     * CASE 1: GPA tốt (>=7.5) + Đúng/Nhanh tiến độ
     * → Tăng tốc nếu đủ điều kiện, hoặc duy trì
     */
    private function caseGoodOntrack(
        float $gpa, float $passRate, int $earnedCredits, int $remainingCredits,
        int $remainingSems, int $currentSem, int $currentTargetSems, string $currentMode
    ): array {
        // Đủ điều kiện tăng tốc khi pass rate cao và GPA xuất sắc
        if ($gpa >= 8.0 && $passRate >= 0.9) {
            $fastRemainingSems = max(3, (int) ceil($remainingCredits / 22));
            $newTargetSems     = max(5, $currentSem + $fastRemainingSems - 1);

            if ($newTargetSems < $currentTargetSems) {
                return $this->buildResult(
                    'SPEED_UP',
                    "Thành tích xuất sắc! GPA {$gpa} và tỷ lệ pass " . round($passRate * 100) . "%. "
                    . "Bạn hoàn toàn có thể rút ngắn lộ trình xuống còn {$newTargetSems} học kỳ (~22 TC/kỳ) để ra trường sớm.",
                    'fast',
                    $newTargetSems,
                    true,
                    $gpa,
                    'good',
                    'ontrack'
                );
            }
        }

        // GPA tốt nhưng chưa đủ điều kiện tăng tốc → duy trì
        return $this->buildResult(
            'KEEP',
            "GPA {$gpa} tốt và tiến độ đúng kế hoạch. Hệ thống khuyến nghị duy trì nhịp học hiện tại. "
            . "Bạn đang trên đà tốt nghiệp đúng hạn!",
            $currentMode,
            $currentTargetSems,
            false,
            $gpa,
            'good',
            'ontrack'
        );
    }

    /**
     * CASE 2: GPA tốt (>=7.5) + Chậm tiến độ
     * → Tăng tốc hợp lý để bù đắp tiến độ
     */
    private function caseGoodBehind(
        float $gpa, int $remainingCredits, int $remainingSems,
        int $currentSem, int $currentTargetSems, string $currentMode, float $progressGap
    ): array {
        // Tính số kỳ cần thiết nếu tăng lên 20 TC/kỳ
        $newRemainingSems = (int) ceil($remainingCredits / 20);
        $newTargetSems    = $currentSem + $newRemainingSems - 1;

        return $this->buildResult(
            'SPEED_UP',
            "GPA {$gpa} tốt nhưng bạn đang chậm tiến độ khoảng " . abs(round($progressGap)) . " TC. "
            . "Hệ thống khuyên tăng lên ~20 TC/kỳ để bù đắp. Với khả năng học tốt, bạn hoàn toàn có thể làm được.",
            'fast',
            max($currentTargetSems, $newTargetSems),
            true,
            $gpa,
            'good',
            'behind'
        );
    }

    /**
     * CASE 4: GPA trung bình (6.0–7.5) + Chậm tiến độ
     * → Cân bằng: tăng nhẹ số tín chỉ, chú ý chất lượng
     */
    private function caseAverageBehind(
        float $gpa, int $remainingCredits, int $remainingSems,
        int $currentSem, int $currentTargetSems, string $currentMode, float $projectedCreditsPerSem
    ): array {
        // Tính số kỳ cần thiết với 17 TC/kỳ (cân bằng)
        $balancedSems  = (int) ceil($remainingCredits / 17);
        $newTargetSems = $currentSem + $balancedSems - 1;
        $projDisplay   = (int) round($projectedCreditsPerSem);

        if ($newTargetSems > $currentTargetSems) {
            return $this->buildResult(
                'REPLAN',
                "GPA {$gpa} (trung bình) và đang chậm tiến độ. "
                . "Để không quá tải (tránh học {$projDisplay} TC/kỳ), "
                . "hệ thống đề xuất kéo dài nhẹ kế hoạch ra {$newTargetSems} học kỳ (~17 TC/kỳ). "
                . "Vừa đảm bảo GPA, vừa không bị áp lực quá lớn.",
                'normal',
                $newTargetSems,
                true,
                $gpa,
                'average',
                'behind'
            );
        }

        return $this->buildResult(
            'KEEP',
            "GPA {$gpa} (trung bình) và tiến độ hơi chậm. Hệ thống khuyên tăng nhẹ lên ~17 TC/kỳ và tập trung nâng điểm các môn còn yếu.",
            $currentMode,
            $currentTargetSems,
            false,
            $gpa,
            'average',
            'behind'
        );
    }

    /**
     * CASE 5: GPA yếu (5.0–6.0) + Đúng tiến độ
     * → Giảm tải: học ít môn hơn, tập trung nâng GPA
     */
    private function caseWeakOntrack(
        float $gpa, int $failedCount, int $remainingCredits, int $remainingSems,
        int $currentSem, int $currentTargetSems, string $currentMode
    ): array {
        $safeRemainingSems = (int) ceil($remainingCredits / 14);
        $newTargetSems     = $currentSem + $safeRemainingSems - 1;

        $failNote = $failedCount > 0
            ? " Đặc biệt cần ưu tiên học lại {$failedCount} môn đã rớt."
            : '';

        if ($newTargetSems > $currentTargetSems) {
            return $this->buildResult(
                'REPLAN',
                "GPA {$gpa} đang ở mức thấp dù tiến độ bình thường. "
                . "Hệ thống khuyến nghị giảm xuống ~14 TC/kỳ và kéo dài kế hoạch ra {$newTargetSems} học kỳ "
                . "để tập trung nâng điểm, tránh rớt thêm môn.{$failNote}",
                'slow',
                $newTargetSems,
                true,
                $gpa,
                'weak',
                'ontrack'
            );
        }

        return $this->buildResult(
            'REDUCE',
            "GPA {$gpa} thấp. Hệ thống khuyên giảm số tín chỉ xuống ~14 TC/kỳ để tập trung học tốt hơn.{$failNote}",
            'slow',
            $currentTargetSems,
            false,
            $gpa,
            'weak',
            'ontrack'
        );
    }

    /**
     * CASE 6: GPA yếu (5.0–6.0) + Chậm tiến độ
     * → Cân bằng giữa GPA và tiến độ: không giảm quá nhiều, không tăng
     */
    private function caseWeakBehind(
        float $gpa, int $failedCount, int $remainingCredits, int $remainingSems,
        int $currentSem, int $currentTargetSems, string $currentMode
    ): array {
        // Cân bằng: ~15 TC/kỳ
        $balancedSems  = (int) ceil($remainingCredits / 15);
        $newTargetSems = $currentSem + $balancedSems - 1;

        $failNote = $failedCount > 0
            ? " Ưu tiên học lại {$failedCount} môn đã rớt trước tiên."
            : '';

        return $this->buildResult(
            'REPLAN',
            "GPA {$gpa} thấp và bạn đang chậm tiến độ — đây là tình huống cần cân bằng. "
            . "Hệ thống đề xuất duy trì ~15 TC/kỳ và kéo dài kế hoạch ra {$newTargetSems} học kỳ. "
            . "Không nên giảm quá ít (sẽ trễ nghiêm trọng) nhưng cũng không tăng (sẽ ảnh hưởng GPA).{$failNote}",
            'normal',
            max($currentTargetSems, $newTargetSems),
            true,
            $gpa,
            'weak',
            'behind'
        );
    }

    /**
     * CASE 7: GPA nguy hiểm (<5.0) + Đúng tiến độ
     * → Giảm tải mạnh, cảnh báo nguy cơ bị đình chỉ
     */
    private function caseCriticalOntrack(
        float $gpa, int $failedCount, int $remainingCredits, int $remainingSems,
        int $currentSem, int $currentTargetSems, string $currentMode
    ): array {
        $safeRemainingSems = (int) ceil($remainingCredits / 12);
        $newTargetSems     = $currentSem + $safeRemainingSems - 1;

        $failNote = $failedCount >= 3
            ? " ⚠️ Bạn đang nợ {$failedCount} môn — hãy ưu tiên học lại ngay!"
            : '';

        return $this->buildResult(
            'REPLAN',
            "⚠️ CẢNH BÁO: GPA {$gpa} dưới ngưỡng 5.0, có nguy cơ bị cảnh báo học vụ. "
            . "Hệ thống khuyến nghị giảm mạnh xuống ~12 TC/kỳ và kéo dài ra {$newTargetSems} học kỳ "
            . "để tập trung phục hồi GPA. Hãy liên hệ cố vấn học tập ngay.{$failNote}",
            'slow',
            $newTargetSems,
            true,
            $gpa,
            'critical',
            'ontrack'
        );
    }

    /**
     * CASE 8: GPA nguy hiểm (<5.0) + Chậm tiến độ
     * → Kéo dài lộ trình, giảm tải tối đa
     */
    private function caseCriticalBehind(
        float $gpa, int $failedCount, int $remainingCredits, int $remainingSems,
        int $currentSem, int $currentTargetSems, string $currentMode, float $projectedCreditsPerSem
    ): array {
        $safeRemainingSems = (int) ceil($remainingCredits / 12);
        $newTargetSems     = $currentSem + $safeRemainingSems - 1;

        $failNote = $failedCount >= 3
            ? " Đặc biệt nghiêm trọng: bạn đang nợ {$failedCount} môn."
            : '';
        $projDisplay = (int) round($projectedCreditsPerSem);

        return $this->buildResult(
            'REPLAN',
            "⚠️ NGUY CƠ CAO: GPA {$gpa} dưới 5.0 VÀ đang chậm tiến độ nghiêm trọng. "
            . "Nếu tiếp tục như hiện tại, bạn cần {$projDisplay} TC/kỳ — vượt quá khả năng an toàn. "
            . "Hệ thống đề xuất kéo dài ra {$newTargetSems} học kỳ với ~12 TC/kỳ để ổn định học lực trước.{$failNote} "
            . "Hãy liên hệ cố vấn học tập ngay lập tức.",
            'slow',
            $newTargetSems,
            true,
            $gpa,
            'critical',
            'behind'
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // HELPER: Xây dựng response chuẩn hóa
    // ══════════════════════════════════════════════════════════════════════

    private function buildResult(
        string $status,
        string $message,
        string $suggestedMode,
        int $suggestedSems,
        bool $isDynamic,
        float $gpa,
        string $gpaLevel,
        string $progressLevel
    ): array {
        return [
            'status'           => $status,        // KEEP | REPLAN | SPEED_UP | REDUCE
            'message'          => $message,
            'suggested_mode'   => $suggestedMode, // normal | fast | slow | dynamic
            'suggested_sems'   => $suggestedSems,
            'is_dynamic'       => $isDynamic,
            'gpa'              => $gpa,
            'gpa_level'        => $gpaLevel,       // good | average | weak | critical
            'progress_level'   => $progressLevel,  // ontrack | behind
        ];
    }
}
