<?php

namespace App\Services;

/**
 * GraduationAdvisorService — Tư Vấn Toàn Diện Lộ Trình Tốt Nghiệp
 *
 * Service này kết hợp thông tin từ nhiều nguồn để đưa ra tư vấn môn học
 * và lộ trình học tập cá nhân hóa, dựa trên 8 tiêu chí:
 *
 *  1. Môn đã fail → ưu tiên học lại cao nhất
 *  2. Môn nằm đúng trong học kỳ chuẩn hiện tại
 *  3. Số môn khác phụ thuộc vào môn này (mở khóa nhiều môn)
 *  4. GPA thấp → ưu tiên môn sinh viên có thế mạnh (tránh rớt thêm)
 *  5. Môn bắt buộc (requirement_type != none)
 *  6. Học vượt quá xa kỳ chuẩn → giảm điểm ưu tiên
 *  7. Tín chỉ phù hợp với target tín chỉ còn lại
 *  8. Khả năng học theo tiến độ kế hoạch hiện tại
 */
class GraduationAdvisorService
{
    protected ProgressService $progressService;
    protected AcademicEvaluationService $evaluationService;

    public function __construct(
        ProgressService $progressService,
        AcademicEvaluationService $evaluationService
    ) {
        $this->progressService   = $progressService;
        $this->evaluationService = $evaluationService;
    }

    /**
     * Tư vấn tốt nghiệp đầy đủ: progress + evaluation + credit target + lời khuyên.
     *
     * @param int $userId
     * @return array
     */
    public function advise(int $userId): array
    {
        $progress   = $this->progressService->evaluateProgress($userId);
        $activePlan = \App\Models\StudyPlan::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        $evaluation = null;
        if ($activePlan) {
            $evaluation = $this->evaluationService->evaluate(
                $userId,
                $activePlan->mode ?? 'normal',
                $activePlan->target_semester_count ?? 8,
                $progress['current_semester']
            );
        }

        // Tính mục tiêu tín chỉ cho kỳ tiếp theo
        $creditTarget = $this->calcCreditTarget($progress, $activePlan);

        // Xây dựng lời khuyên tổng hợp
        $advice = $this->buildAdvice($progress, $evaluation, $creditTarget);

        return [
            'progress'      => $progress,
            'evaluation'    => $evaluation,
            'credit_target' => $creditTarget,
            'advice'        => $advice,
        ];
    }

    /**
     * Tính số tín chỉ mục tiêu cho học kỳ tiếp theo.
     *
     * Cân bằng giữa: tín chỉ còn lại, số kỳ còn lại, mode kế hoạch,
     * và rủi ro tốt nghiệp.
     */
    public function calcCreditTarget(array $progress, ?\App\Models\StudyPlan $activePlan): array
    {
        $remaining   = $progress['remaining_credits'];
        $remSems     = max(1, $progress['remaining_semesters']);
        $gpa         = floatval($progress['current_gpa']);
        $planMode    = $activePlan?->mode ?? 'normal';

        // Giới hạn TC tối đa theo mode
        $maxByMode = match($planMode) {
            'fast'  => 22,
            'slow'  => 14,
            default => 20,
        };

        // TC tối thiểu để đủ về đích đúng hạn
        $minNeeded = (int) ceil($remaining / $remSems);

        // Điều chỉnh theo GPA
        $recommended = match(true) {
            $gpa >= 7.5 => min($maxByMode, max($minNeeded, 18)),   // GPA tốt: thoải mái
            $gpa >= 6.0 => min($maxByMode, max($minNeeded, 16)),   // GPA TB: vừa phải
            $gpa >= 5.0 => min(16, max($minNeeded, 14)),           // GPA yếu: nhẹ
            $gpa > 0    => min(14, max($minNeeded, 12)),           // GPA nguy hiểm: rất nhẹ
            default     => min($maxByMode, $minNeeded),            // Chưa có điểm
        };

        // Không vượt quá giới hạn tuyệt đối 24 TC/kỳ
        $recommended = min(24, $recommended);

        return [
            'recommended_credits' => $recommended,
            'min_needed_credits'  => $minNeeded,
            'max_allowed_credits' => $maxByMode,
            'plan_mode'           => $planMode,
        ];
    }

    /**
     * Sắp xếp danh sách môn học được gợi ý theo thứ tự ưu tiên tư vấn.
     *
     * Điểm ưu tiên dựa trên 8 tiêu chí.
     *
     * @param array $recommendations  Danh sách từ RecommendationService (đã tính score)
     * @param array $progress         Kết quả từ ProgressService
     * @param int   $currentSem       Học kỳ hiện tại
     * @param array $failedSubjectIds Danh sách ID môn đã rớt
     * @return array  Danh sách môn đã sắp xếp với advisor_score
     */
    public function prioritizeSubjects(
        array $recommendations,
        array $progress,
        int $currentSem,
        array $failedSubjectIds = []
    ): array {
        $gpa           = floatval($progress['current_gpa']);
        $gradRisk      = $progress['graduation_risk'] ?? 'low';

        foreach ($recommendations as &$rec) {
            $rec['advisor_score'] = $this->calcAdvisorScore(
                $rec, $gpa, $gradRisk, $currentSem, $failedSubjectIds
            );
        }
        unset($rec);

        // Sắp xếp giảm dần theo advisor_score
        usort($recommendations, fn($a, $b) => $b['advisor_score'] <=> $a['advisor_score']);

        return $recommendations;
    }

    /**
     * Tính điểm ưu tiên cho một môn học theo 8 tiêu chí.
     */
    private function calcAdvisorScore(
        array $rec,
        float $gpa,
        string $gradRisk,
        int $currentSem,
        array $failedSubjectIds
    ): int {
        $score   = $rec['score'] ?? 0;     // Điểm base từ RecommendationService
        $subject = $rec['subject'] ?? [];
        $subId   = $subject['id'] ?? null;
        $assignedSem = $subject['assigned_semester_index'] ?? $currentSem;

        // ── Tiêu chí 1: Môn fail → ưu tiên cao nhất (+100) ─────────────
        if (in_array($subId, $failedSubjectIds)) {
            $score += 100;
        }

        // ── Tiêu chí 2: Môn đúng kỳ chuẩn hiện tại (+80) ───────────────
        if ($assignedSem === $currentSem) {
            $score += 80;
        } elseif ($assignedSem === $currentSem - 1) {
            $score += 40; // Môn kỳ trước chưa học
        }

        // ── Tiêu chí 3: Số môn phụ thuộc (mở khóa nhiều môn) ───────────
        // Mỗi môn phụ thuộc = +15 điểm (tối đa +60)
        $dependentCount = min(4, (int)($rec['dependent_count'] ?? 0));
        $score += $dependentCount * 15;

        // ── Tiêu chí 4: GPA thấp → ưu tiên môn sinh viên giỏi ──────────
        // Nếu GPA thấp, ưu tiên môn thuộc skill group sinh viên có thế mạnh
        if ($gpa < 6.0) {
            $skillGroupAvg = floatval($rec['skill_group_avg'] ?? 0);
            if ($skillGroupAvg >= 6.5) {
                $score += 40; // Sinh viên có thể làm tốt môn này
            }
        }

        // ── Tiêu chí 5: Môn bắt buộc (+30) ─────────────────────────────
        $reqType = $subject['requirement_type'] ?? 'none';
        if ($reqType !== 'none' && $reqType !== null) {
            $score += 30;
        }

        // ── Tiêu chí 6: Học vượt quá xa kỳ chuẩn → giảm điểm ──────────
        $distanceToStandard = $assignedSem - $currentSem;
        if ($distanceToStandard > 2) {
            $score -= ($distanceToStandard - 2) * 20; // -20 mỗi kỳ vượt quá 2
        }

        // ── Tiêu chí 7: Rủi ro tốt nghiệp cao → ưu tiên TC nhiều hơn ───
        if (in_array($gradRisk, ['high', 'critical'])) {
            $credits = (int)($subject['credits'] ?? 0);
            $score += $credits * 3; // Mỗi TC = +3 điểm khi đang trong tình trạng nguy hiểm
        }

        // ── Tiêu chí 8: GPA trend đang giảm → ưu tiên môn nhẹ nhàng ────
        // (Tích hợp trong tiêu chí 4 - không tăng thêm điểm cho môn khó)

        return max(0, $score);
    }

    /**
     * Xây dựng lời khuyên tổng hợp dựa trên progress và evaluation.
     */
    private function buildAdvice(array $progress, ?array $evaluation, array $creditTarget): array
    {
        $gpa         = floatval($progress['current_gpa']);
        $gradRisk    = $progress['graduation_risk'] ?? 'low';
        $gpaTrend    = $progress['gpa_trend'] ?? 'stable';
        $extraSems   = $progress['estimated_extra_sems'] ?? 0;
        $recommended = $creditTarget['recommended_credits'];

        // Tiêu đề và icon theo trạng thái
        [$title, $icon, $colorClass] = match(true) {
            $gpa >= 7.5 && $gradRisk === 'low'       => ['Xuất sắc! Đang trên đà tốt nghiệp đúng hạn', '🎉', 'success'],
            $gpa >= 6.0 && $gradRisk !== 'critical'  => ['Tiến độ tốt, cần duy trì nhịp học', '📈', 'info'],
            $gpa >= 5.0 || $gradRisk === 'moderate'  => ['Cần chú ý — điều chỉnh kế hoạch', '⚠️', 'warning'],
            default                                   => ['Nguy cơ cao — Hãy hành động ngay!', '🚨', 'danger'],
        };

        // Nội dung chi tiết
        $details = [];

        if ($gpa > 0) {
            $trendMsg = match($gpaTrend) {
                'improving' => '📈 GPA đang tăng — dấu hiệu tích cực!',
                'declining' => '📉 GPA đang giảm — cần điều chỉnh phương pháp học.',
                default     => '➡️ GPA ổn định qua các kỳ.',
            };
            $details[] = $trendMsg;
        }

        if ($extraSems > 0) {
            $details[] = "⏰ Dựa trên pace hiện tại, bạn có thể trễ ~{$extraSems} học kỳ nếu không tăng tốc.";
        }

        $details[] = "📚 Khuyến nghị đăng ký {$recommended} TC cho học kỳ tiếp theo.";

        if ($evaluation && $evaluation['status'] !== 'KEEP') {
            $details[] = "💡 " . $evaluation['message'];
        }

        return [
            'title'       => $title,
            'icon'        => $icon,
            'color_class' => $colorClass,
            'details'     => $details,
            'gpa_level'   => $evaluation['gpa_level'] ?? 'unknown',
        ];
    }
}
