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

            // Evaluate progress to check GPA
            $progressService = new \App\Services\ProgressService();
            $progress = $progressService->evaluateProgress($userId);
            
            // Check for low GPA and force slow mode
            if ($progress['current_gpa'] > 0 && $progress['current_gpa'] < 2.0) {
                $mode = 'slow';
            }

            $maxCredits = $this->getMaxCreditsByMode($mode);
            
            // Fetch all grades and keep only the highest grade per subject
            $allUserGrades = UserGrade::where('user_id', $userId)->get();
            $userGrades = $allUserGrades->groupBy('subject_id')->map(function ($grades) {
                return $grades->sortByDesc('grade')->first();
            })->values();

            $passedSubjectIds = $userGrades->where('status', 'passed')->pluck('subject_id')->toArray();
            $failedSubjectIds = $userGrades->where('status', 'failed')->pluck('subject_id')->toArray();

            // Lấy chương trình khung của sinh viên
            $user = \App\Models\User::find($userId);
            $academicYear = $user->pref_academic_year;
            $programType = $user->pref_program_type;
            
            $frameworkId = null;
            if ($academicYear && $programType) {
                $program = \App\Models\TrainingProgram::where('academic_year', $academicYear)
                    ->where('program_type', $programType)
                    ->first();
                if ($program && $framework = $program->curriculumFrameworks()->first()) {
                    $frameworkId = $framework->id;
                }
            }

            if ($frameworkId) {
                $curriculumSubjects = \App\Models\CurriculumSubject::where('curriculum_framework_id', $frameworkId)
                    ->with(['subject.prerequisites', 'subject.relatedRelations', 'semester'])
                    ->get();
                $allSubjects = collect();
                foreach ($curriculumSubjects as $cs) {
                    if ($cs->subject) {
                        $sub = $cs->subject;
                        $sub->assigned_semester_index = (int) ($cs->semester->name ?? $sub->semester_id ?? 1);
                        $allSubjects->push($sub);
                    }
                }
            } else {
                $allSubjects = Subject::with(['prerequisites', 'relatedRelations'])->get();
                foreach ($allSubjects as $sub) {
                    $sub->assigned_semester_index = (int) $sub->semester_id;
                }
            }
            
            // Không lọc bỏ các môn đã học, để bản kế hoạch hiển thị toàn bộ lộ trình 4 năm
            $remainingSubjects = clone $allSubjects;

            $plannedSubjectIds = []; // Bắt đầu rỗng để mô phỏng lại toàn bộ lộ trình từ đầu
            
            $plan = StudyPlan::create([
                'user_id' => $userId,
                'name' => $name,
                'mode' => $mode,
            ]);

            $semesterIndex = 1;

            $basicGroupIds = \App\Models\ProgramGroup::where('name', 'like', '%Đại cương%')
                ->orWhere('name', 'like', '%Anh văn%')
                ->pluck('id')->toArray();
            $majorGroupIds = \App\Models\ProgramGroup::where('name', 'like', '%Cơ sở ngành%')
                ->pluck('id')->toArray();
            $specializedGroupIds = \App\Models\ProgramGroup::where('name', 'like', '%Chuyên ngành%')
                ->pluck('id')->toArray();
            
            while ($remainingSubjects->count() > 0) {
                // Find available subjects for this semester
                $availableSubjects = $remainingSubjects->filter(function ($subject) use ($plannedSubjectIds, $semesterIndex, $basicGroupIds, $majorGroupIds, $specializedGroupIds, $allSubjects) {
                    // Check semester availability (offered_in)
                    $isOddSemester = ($semesterIndex % 2) !== 0;
                    if ($isOddSemester && $subject->offered_in === '2') {
                        return false; // Subject only offered in even semesters
                    }
                    if (!$isOddSemester && $subject->offered_in === '1') {
                        return false; // Subject only offered in odd semesters
                    }

                    // Check if all prerequisites are met in planned/passed subjects
                    foreach ($subject->prerequisites as $prereq) {
                        if (!in_array($prereq->id, $plannedSubjectIds)) {
                            return false;
                        }
                    }

                    // Check implicit prerequisites (requirement_type)
                    $reqType = $subject->requirement_type;
                    if ($reqType && $reqType !== 'none') {
                        if ($reqType === 'completed_all') {
                            $otherSubjectIds = $allSubjects->pluck('id')->reject(fn($id) => $id == $subject->id)->toArray();
                            foreach ($otherSubjectIds as $id) {
                                if (!in_array($id, $plannedSubjectIds)) return false;
                            }
                        } elseif ($reqType === 'completed_basic') {
                            $basicSubjectIds = $allSubjects->whereIn('program_group_id', $basicGroupIds)->pluck('id')->toArray();
                            foreach ($basicSubjectIds as $id) {
                                if (!in_array($id, $plannedSubjectIds)) return false;
                            }
                        } elseif ($reqType === 'completed_major') {
                            $majorSubjectIds = $allSubjects->whereIn('program_group_id', $majorGroupIds)->pluck('id')->toArray();
                            foreach ($majorSubjectIds as $id) {
                                if (!in_array($id, $plannedSubjectIds)) return false;
                            }
                        } elseif ($reqType === 'completed_specialized') {
                            $specializedSubjectIds = $allSubjects->whereIn('program_group_id', $specializedGroupIds)->pluck('id')->toArray();
                            foreach ($specializedSubjectIds as $id) {
                                if (!in_array($id, $plannedSubjectIds)) return false;
                            }
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
                $availableSubjects = $availableSubjects->sortByDesc(function ($subject) use ($failedSubjectIds, $semesterIndex, $mode) {
                    $score = 0;
                    if (in_array($subject->id, $failedSubjectIds)) $score += 100; // Failed subjects first
                    if ($subject->requirement_type && $subject->requirement_type !== 'none') $score += 30; // Core subjects
                    $score += (5 * $subject->relatedRelations->where('type', 'prerequisite')->count()); // Unlocks more subjects
                    
                    if ($mode === 'normal' || $mode === 'slow') {
                        $assignedSem = $subject->assigned_semester_index;
                        if ($assignedSem) {
                            if ($assignedSem > $semesterIndex) {
                                $score -= (($assignedSem - $semesterIndex) * 100); // Phạt nặng nếu học vượt
                            } elseif ($assignedSem == $semesterIndex) {
                                $score += 200; // Ưu tiên cực cao nếu đúng học kỳ khung
                            } else {
                                $score += 150; // Ưu tiên rất cao để trả nợ môn cũ
                            }
                        }
                    }

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
                            'is_completed' => in_array($subject->id, $passedSubjectIds),
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
