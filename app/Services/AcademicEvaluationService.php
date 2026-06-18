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
    public function evaluate(int $userId, string $currentMode = 'normal', int $currentTargetSemesters = 8, int $currentSem = 1): array
    {
        $progress = $this->progressService->evaluateProgress($userId);
        
        $gpa = floatval($progress['current_gpa']);
        $gradedPercentage = $progress['graded_credits_percentage'];
        
        $suggestedMode = 'dynamic';
        $suggestedSems = $currentTargetSemesters;
        $status = 'KEEP';
        $message = 'Kế hoạch hiện tại phù hợp. Không cần điều chỉnh.';
        $isDynamicRecommendation = false;

        // Chỉ đánh giá khi đã học (có điểm) >= 30% chương trình
        if ($gradedPercentage >= 30) {
            $totalRequired = $progress['total_required_credits'];
            $earnedCredits = $progress['earned_credits'];
            $remainingCredits = max(0, $totalRequired - $earnedCredits);
            $remainingSems = max(1, $currentTargetSemesters - $currentSem + 1);
            
            $projectedCreditsPerSem = $remainingCredits / $remainingSems;
            $passRate = $progress['passed_subjects_count'] / max(1, ($progress['passed_subjects_count'] + $progress['failed_subjects_count']));

            // Logic 1: Học quá yếu hoặc nợ môn nhiều gây dồn ứ tín chỉ
            if ($projectedCreditsPerSem > 25 || $gpa < 5.5) {
                // Đề xuất kéo dài học kỳ để gánh không quá 15 tín chỉ/kỳ (mức an toàn cho sv yếu)
                $safeRemainingSems = ceil($remainingCredits / 15);
                $newTargetSems = $currentSem + $safeRemainingSems - 1;
                
                if ($newTargetSems > $currentTargetSemesters) {
                    $status = 'REPLAN';
                    $suggestedSems = $newTargetSems;
                    $isDynamicRecommendation = true;
                    $message = "Phát hiện nguy cơ học vụ! Bạn đang nợ môn hoặc điểm thấp (GPA {$gpa}), dẫn đến các kỳ tới phải gánh trung bình " . round($projectedCreditsPerSem, 1) . " TC/kỳ (vượt ngưỡng báo động 25 TC). Hệ thống khuyên bạn nên kéo dài lộ trình ra thành {$newTargetSems} học kỳ để giảm tải xuống mức an toàn (~15 TC/kỳ).";
                }
            } 
            // Logic 2: Học xuất sắc, cày nhanh
            elseif ($gpa >= 8.0 && $passRate >= 0.9) {
                // Có khả năng gánh 22-25 tín chỉ/kỳ. Tính số kỳ tối thiểu có thể.
                $fastRemainingSems = ceil($remainingCredits / 22);
                $newTargetSems = max(5, $currentSem + $fastRemainingSems - 1); // Cho phép ngắn nhất là 5 kỳ
                
                if ($newTargetSems < $currentTargetSemesters) {
                    $status = 'SPEED_UP';
                    $suggestedSems = $newTargetSems;
                    $isDynamicRecommendation = true;
                    $message = "Thành tích xuất sắc! Với phong độ hiện tại (GPA {$gpa}), bạn hoàn toàn có thể rút ngắn lộ trình học xuống còn {$newTargetSems} học kỳ để ra trường sớm.";
                }
            }
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'suggested_mode' => $suggestedMode,
            'suggested_sems' => $suggestedSems,
            'is_dynamic' => $isDynamicRecommendation,
            'gpa' => $gpa
        ];
    }
}
