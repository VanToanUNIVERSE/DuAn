<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\TrainingProgram;
use App\Models\UserGrade;

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
        $passedSubjectIds = $userGrades->filter(function ($grade) {
            return $grade->grade > 5.0 || in_array($grade->status, ['passed', 'pass']);
        })->pluck('subject_id')->toArray();
        $failedSubjectIds = $userGrades->filter(function ($grade) {
            return ($grade->grade !== null && $grade->grade <= 5.0) || in_array($grade->status, ['failed', 'fail']);
        })->pluck('subject_id')->toArray();
        $gradedSubjectIds = $userGrades->filter(function($grade) {
            return $grade->grade !== null || $grade->status !== null;
        })->pluck('subject_id')->toArray();

        $user = \App\Models\User::find($userId);
        $allSubjects = $this->getSubjectsForUserCurriculum($user);

        $currentSemester = 1;
        foreach ($gradedSubjectIds as $pid) {
            $sub = $allSubjects->firstWhere('id', $pid);
            if ($sub && isset($sub->assigned_semester_index)) {
                if ($sub->assigned_semester_index >= $currentSemester) {
                    $currentSemester = $sub->assigned_semester_index + 1;
                }
            }
        }

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

            if ($subject->assigned_semester_index) {
                $distance = abs($subject->assigned_semester_index - $currentSemester);
                $score -= ($distance * 10);

                if ($distance === 0) {
                    $reasons[] = 'Dung hoc ky chuan';
                }
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

    private function getSubjectsForUserCurriculum($user)
    {
        $frameworkId = null;

        if ($user && $user->pref_academic_year && $user->pref_program_type) {
            $program = TrainingProgram::where('academic_year', $user->pref_academic_year)
                ->where('program_type', $user->pref_program_type)
                ->first();

            if ($program && $framework = $program->curriculumFrameworks()->first()) {
                $frameworkId = $framework->id;
            }
        }

        if (!$frameworkId) {
            return Subject::with(['prerequisites', 'relatedRelations'])->get();
        }

        $curriculumSubjects = \App\Models\CurriculumSubject::where('curriculum_framework_id', $frameworkId)
            ->with(['subject.prerequisites', 'subject.relatedRelations', 'semester'])
            ->get();

        $subjects = collect();
        foreach ($curriculumSubjects as $curriculumSubject) {
            if (!$curriculumSubject->subject) {
                continue;
            }

            $subject = $curriculumSubject->subject;
            $subject->assigned_semester_index = (int) ($curriculumSubject->semester?->name ?? $subject->semester_id ?? 1);
            $subject->setRelation('semester', $curriculumSubject->semester);
            $subjects->push($subject);
        }

        return $subjects->unique('id')->values();
    }
}
