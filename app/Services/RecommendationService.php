<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\UserGrade;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    /**
     * Generate subject recommendations for a given user.
     *
     * @param int $userId
     * @return array
     */
    public function getRecommendations(int $userId): array
    {
        // 1. Get user's grades
        $userGrades = UserGrade::where('user_id', $userId)->get();
        $passedSubjectIds = $userGrades->where('status', 'passed')->pluck('subject_id')->toArray();
        $failedSubjectIds = $userGrades->where('status', 'failed')->pluck('subject_id')->toArray();

        // 2. Get all available subjects (could be filtered by user's curriculum in the future)
        $allSubjects = Subject::with(['prerequisites', 'relatedRelations'])->get();

        $recommendations = [];

        foreach ($allSubjects as $subject) {
            // Skip if already passed
            if (in_array($subject->id, $passedSubjectIds)) {
                continue;
            }

            // Check prerequisites
            $hasUnmetPrerequisite = false;
            foreach ($subject->prerequisites as $prerequisite) {
                if (!in_array($prerequisite->id, $passedSubjectIds)) {
                    $hasUnmetPrerequisite = true;
                    break;
                }
            }

            if ($hasUnmetPrerequisite) {
                continue; // Cannot take this subject yet
            }

            // Calculate Score
            $score = 0;
            $reasons = [];

            // +50 if it's a failed subject (Retake priority)
            if (in_array($subject->id, $failedSubjectIds)) {
                $score += 50;
                $reasons[] = 'Học lại môn đã rớt';
            }

            // +30 for specific requirement types (Mandatory/Core)
            // Assuming 'none' means elective, others mean required in some way
            if ($subject->requirement_type && $subject->requirement_type !== 'none') {
                $score += 30;
                $reasons[] = 'Môn bắt buộc / cốt lõi';
            } else {
                $score += 10;
                $reasons[] = 'Môn tự chọn';
            }

            // +5 for each subject that depends on this one (Unlocking future subjects)
            $dependentCount = $subject->relatedRelations->where('type', 'prerequisite')->count();
            if ($dependentCount > 0) {
                $score += (5 * $dependentCount);
                $reasons[] = "Mở khóa {$dependentCount} môn học khác";
            }

            $recommendations[] = [
                'subject' => $subject,
                'score' => $score,
                'reasons' => $reasons,
            ];
        }

        // Sort recommendations by score descending
        usort($recommendations, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $recommendations;
    }
}
