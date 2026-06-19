<?php

namespace App\Services;

use App\Models\Subject;

class GraduationAdvisorService
{
    protected $progressService;
    protected $evaluationService;
    protected $recommendationService;

    public function __construct(
        ProgressService $progressService,
        AcademicEvaluationService $evaluationService,
        RecommendationService $recommendationService
    ) {
        $this->progressService = $progressService;
        $this->evaluationService = $evaluationService;
        $this->recommendationService = $recommendationService;
    }

    /**
     * Tư vấn môn học cho học kỳ tiếp theo dựa trên đầy đủ tiêu chí:
     * - GPA hiện tại & Tiến độ
     * - Môn tiên quyết / Song hành
     * - Số tín chỉ mục tiêu (để tốt nghiệp đúng hạn / đúng mode)
     */
    public function adviseCourses(int $userId, int $nextSemester, string $currentMode = 'normal'): array
    {
        $progress = $this->progressService->evaluateProgress($userId);
        
        $evaluation = $this->evaluationService->evaluate(
            $userId,
            $currentMode,
            $progress['remaining_semesters'] + $progress['completed_semesters'],
            $nextSemester
        );

        $recommendations = $this->recommendationService->getRecommendations($userId);

        // Tính số tín chỉ mục tiêu dựa trên mode và tiến độ
        $creditTarget = $this->calcCreditTarget(
            $progress['remaining_credits'],
            $progress['remaining_semesters'],
            $evaluation['suggested_mode']
        );

        // Xếp hạng ưu tiên dựa trên 8 tiêu chí
        $advised = [];
        foreach ($recommendations as $rec) {
            // Bỏ qua các môn không đủ điều kiện (thiếu tiên quyết)
            if (isset($rec['can_study']) && $rec['can_study'] === false) {
                continue;
            }

            $priority = $this->calcPriority($rec, $progress, $evaluation, $nextSemester);
            $advised[] = [
                'subject'  => $rec['subject'],
                'reasons'  => $rec['reasons'],
                'priority' => $priority,
                'score'    => $rec['score'] ?? 0,
            ];
        }

        // Sắp xếp theo mức độ ưu tiên giảm dần
        usort($advised, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        return $this->fitCreditsTarget($advised, $creditTarget);
    }

    /**
     * Tính số tín chỉ mục tiêu cho kỳ tới
     */
    private function calcCreditTarget(int $remainingCredits, int $remainingSems, string $mode): int
    {
        if ($remainingSems <= 0) return $remainingCredits;

        $base = (int) ceil($remainingCredits / $remainingSems);

        return match ($mode) {
            'fast'   => max(20, $base + 2), // Tăng tốc: Ít nhất 20 TC, có thể cao hơn
            'slow'   => min(14, max(12, $base - 2)), // Học nhẹ: Giới hạn 12-14 TC
            default  => min(22, max(14, $base)),     // Cân bằng: 14-22 TC
        };
    }

    /**
     * Tính toán mức độ ưu tiên cho từng môn học
     */
    private function calcPriority(array $rec, array $progress, array $eval, int $sem): int
    {
        $score = 0;
        $subject = $rec['subject'];

        // Ưu tiên 1: Môn fail → Bắt buộc học lại
        if (isset($rec['is_failed']) && $rec['is_failed']) {
            $score += 100;
        }

        // Ưu tiên 2: Môn bắt buộc trong kỳ chuẩn
        if (isset($subject['assigned_semester_index']) && $subject['assigned_semester_index'] == $sem) {
            $score += 80;
        }

        // Ưu tiên 3: Môn mở khóa nhiều môn khác (Prerequisites)
        $dependentCount = $rec['dependent_count'] ?? 0;
        $score += $dependentCount * 20;

        // Ưu tiên 4: Phù hợp năng lực khi GPA thấp (chọn môn dễ pass)
        $gpa = floatval($progress['current_gpa']);
        if ($gpa < 6.0 && isset($subject['skill_group_avg']) && $subject['skill_group_avg'] >= 7.0) {
            $score += 40; // Môn thuộc nhóm kỹ năng sinh viên có thế mạnh
        }

        // Ưu tiên 5: Môn bắt buộc để tốt nghiệp (Yêu cầu loại)
        if (isset($subject['requirement_type']) && $subject['requirement_type'] !== 'none') {
            $score += 30;
        }

        // Giảm điểm: Môn học vượt quá xa học kỳ chuẩn
        if (isset($subject['assigned_semester_index'])) {
            $distanceToStandard = $subject['assigned_semester_index'] - $sem;
            if ($distanceToStandard > 2) {
                $score -= 30;
            }
        }

        return $score;
    }

    /**
     * Chọn danh sách môn sao cho tổng tín chỉ vừa đủ với mục tiêu
     */
    private function fitCreditsTarget(array $advised, int $target): array
    {
        $result = [];
        $currentCredits = 0;

        foreach ($advised as $item) {
            $credits = intval($item['subject']['credits'] ?? 0);
            
            // Nếu thêm môn này mà không vượt quá target + 2 (sai số cho phép)
            if ($currentCredits + $credits <= $target + 2) {
                $result[] = $item;
                $currentCredits += $credits;
            }

            if ($currentCredits >= $target) {
                break; // Đã đủ tín chỉ
            }
        }

        return [
            'subjects' => $result,
            'total_credits' => $currentCredits,
            'target_credits' => $target
        ];
    }
}
