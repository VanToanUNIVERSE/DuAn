<?php

namespace App\Services;

class AcademicEvaluationService
{
    protected $progressService;

    public function __construct(ProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Evaluate the student's progress and return recommendation
     */
    public function evaluate(int $userId, string $currentMode = 'normal'): array
    {
        $progress = $this->progressService->evaluateProgress($userId);
        
        $gpa = floatval($progress['current_gpa']);
        $failedCount = $progress['failed_subjects_count'];
        $completionPercentage = $progress['completion_percentage'];
        
        $suggestedMode = $currentMode;
        $status = 'KEEP';
        $message = 'Kế hoạch hiện tại phù hợp. Không cần điều chỉnh.';

        if ($gpa >= 7.0 && $failedCount == 0) {
            if ($currentMode === 'slow') {
                $status = 'BALANCE';
                $suggestedMode = 'normal';
                $message = 'Điểm số rất tốt. Bạn có thể quay lại tiến độ bình thường để ra trường sớm hơn.';
            } else {
                $status = 'KEEP';
                $suggestedMode = $currentMode;
                $message = 'Tiến độ học tập xuất sắc.';
            }
        } elseif ($gpa >= 5.5 && $gpa < 7.0) {
            if ($currentMode !== 'slow') {
                $status = 'REDUCE_LOAD';
                $suggestedMode = 'slow';
                $message = 'Nên giảm khối lượng học tập để cải thiện kết quả.';
            }
        } elseif ($gpa < 5.5) {
            if ($completionPercentage < 40 && $failedCount > 2) {
                if ($currentMode !== 'normal') {
                    $status = 'BALANCE';
                    $suggestedMode = 'normal';
                    $message = 'Mặc dù GPA thấp nhưng bạn đang chậm tiến độ. Đề xuất kế hoạch cân bằng để tốt nghiệp đúng hạn.';
                }
            } else {
                if ($currentMode !== 'slow') {
                    $status = 'REPLAN';
                    $suggestedMode = 'slow';
                    $message = 'Phát hiện nguy cơ học chậm tiến độ và kết quả kém. Đề xuất giảm khối lượng học tập.';
                }
            }
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'suggested_mode' => $suggestedMode,
            'gpa' => $gpa
        ];
    }
}
