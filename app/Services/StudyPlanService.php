<?php

namespace App\Services;

use App\Models\StudyPlan;
use App\Models\StudyPlanSemester;
use App\Models\StudyPlanSubject;
use App\Models\Subject;
use App\Models\UserGrade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudyPlanService
{
    /**
     * Generate a multi-semester study plan for a user.
     *
     * @param int $userId
     * @param string $name
     * @param string $mode (normal, fast, slow)
     * @return StudyPlan
     */
    public function generatePlan(int $userId, string $name, string $mode = 'normal'): StudyPlan
    {
        return DB::transaction(function () use ($userId, $name, $mode) {
            // Delete old plan of the same mode (optional, or just create new)
            // StudyPlan::where('user_id', $userId)->where('mode', $mode)->delete();

            $maxCredits = $this->getMaxCreditsByMode($mode);
            
            $userGrades = UserGrade::where('user_id', $userId)->get();
            $passedSubjectIds = $userGrades->where('status', 'passed')->pluck('subject_id')->toArray();
            $failedSubjectIds = $userGrades->where('status', 'failed')->pluck('subject_id')->toArray();

            $allSubjects = Subject::with(['prerequisites', 'relatedRelations'])->get();
            
            // Filter out passed subjects
            $remainingSubjects = $allSubjects->reject(function ($subject) use ($passedSubjectIds) {
                return in_array($subject->id, $passedSubjectIds);
            })->values();

            $plannedSubjectIds = $passedSubjectIds; // Start with passed, add as we plan
            
            $plan = StudyPlan::create([
                'user_id' => $userId,
                'name' => $name,
                'mode' => $mode,
            ]);

            $semesterIndex = 1;
            
            while ($remainingSubjects->count() > 0) {
                // Find available subjects for this semester
                $availableSubjects = $remainingSubjects->filter(function ($subject) use ($plannedSubjectIds) {
                    // Check if all prerequisites are met in planned/passed subjects
                    foreach ($subject->prerequisites as $prereq) {
                        if (!in_array($prereq->id, $plannedSubjectIds)) {
                            return false;
                        }
                    }
                    return true;
                });

                if ($availableSubjects->count() === 0) {
                    // Prevent infinite loop if prerequisites are unresolvable
                    Log::warning("Cannot resolve prerequisites for remaining subjects for User {$userId}");
                    break;
                }

                // Sort available subjects by priority
                $availableSubjects = $availableSubjects->sortByDesc(function ($subject) use ($failedSubjectIds) {
                    $score = 0;
                    if (in_array($subject->id, $failedSubjectIds)) $score += 50; // Failed subjects first
                    if ($subject->requirement_type && $subject->requirement_type !== 'none') $score += 30; // Core subjects
                    $score += (5 * $subject->relatedRelations->where('type', 'prerequisite')->count()); // Unlocks more subjects
                    return $score;
                });

                $currentSemesterCredits = 0;
                $subjectsForThisSemester = [];

                foreach ($availableSubjects as $key => $subject) {
                    if ($currentSemesterCredits + $subject->credits <= $maxCredits) {
                        $subjectsForThisSemester[] = $subject;
                        $currentSemesterCredits += $subject->credits;
                        
                        // Remove from remaining
                        $remainingSubjects = $remainingSubjects->reject(function ($s) use ($subject) {
                            return $s->id === $subject->id;
                        });
                    }
                }

                if (count($subjectsForThisSemester) > 0) {
                    $semester = StudyPlanSemester::create([
                        'study_plan_id' => $plan->id,
                        'semester_index' => $semesterIndex,
                        'expected_credits' => $currentSemesterCredits,
                    ]);

                    foreach ($subjectsForThisSemester as $subject) {
                        StudyPlanSubject::create([
                            'study_plan_semester_id' => $semester->id,
                            'subject_id' => $subject->id,
                        ]);
                        // Add to planned for future semesters' prerequisite checks
                        $plannedSubjectIds[] = $subject->id;
                    }
                    
                    $semesterIndex++;
                } else {
                    // No subjects fit in the credit limit for some reason (maybe all subjects have credits > maxCredits)
                    break; 
                }
            }

            $plan->update(['target_semester_count' => $semesterIndex - 1]);

            return $plan->load('semesters.subjects.subject');
        });
    }

    private function getMaxCreditsByMode(string $mode): int
    {
        return match ($mode) {
            'fast' => 22,
            'slow' => 14,
            default => 18,
        };
    }
}
