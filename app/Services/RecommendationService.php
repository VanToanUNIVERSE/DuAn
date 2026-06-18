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

            // Check prerequisites & Collect info
            $hasUnmetPrerequisite = false;
            $prereqDetails = [];
            
            // 1. Explicit prerequisites
            foreach ($subject->prerequisites as $prereq) {
                $isPassed = in_array($prereq->id, $passedSubjectIds);
                if (!$isPassed) {
                    $hasUnmetPrerequisite = true;
                }
                $prereqDetails[] = [
                    'id' => $prereq->id,
                    'name' => $prereq->name,
                    'is_passed' => $isPassed
                ];
            }

            // 2. Implicit prerequisites (from requirement_type)
            $reqType = $subject->requirement_type;
            if ($reqType && $reqType !== 'none') {
                $basicGroupIds = [1, 2, 3]; // Kiến thức giáo dục đại cương
                $majorGroupIds = [4, 5];    // Kiến thức cơ sở ngành
                $specializedGroupIds = [6, 7]; // Kiến thức chuyên ngành

                $implicitPrereqSubjects = collect();
                
                if ($reqType === 'completed_basic') {
                    $implicitPrereqSubjects = $allSubjects->whereIn('program_group_id', $basicGroupIds);
                } elseif ($reqType === 'completed_major') {
                    $implicitPrereqSubjects = $allSubjects->whereIn('program_group_id', $majorGroupIds);
                } elseif ($reqType === 'completed_specialized') {
                    $implicitPrereqSubjects = $allSubjects->whereIn('program_group_id', $specializedGroupIds);
                } elseif ($reqType === 'completed_all') {
                    $implicitPrereqSubjects = $allSubjects->where('id', '!=', $subject->id);
                }

                foreach ($implicitPrereqSubjects as $impSub) {
                    if (collect($prereqDetails)->contains('id', $impSub->id)) continue;
                    
                    $isPassed = in_array($impSub->id, $passedSubjectIds);
                    if (!$isPassed) {
                        $hasUnmetPrerequisite = true;
                    }
                    $prereqDetails[] = [
                        'id' => $impSub->id,
                        'name' => $impSub->name,
                        'is_passed' => $isPassed
                    ];
                }
            }

            $subject->prerequisites_info = $prereqDetails;
            $subject->can_study = !$hasUnmetPrerequisite;

            if ($hasUnmetPrerequisite) {
                continue;
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
