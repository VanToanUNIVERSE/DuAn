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
     * @param int|null $currentSemester
     * @param int|null $studyPlanId
     * @return array
     */
    public function getRecommendations(int $userId, ?int $currentSemester = null, ?int $studyPlanId = null): array
    {
        // 1. Get user's grades
        $userGrades = UserGrade::where('user_id', $userId)->get();
        $passedSubjectIds = $userGrades->filter(function ($grade) {
            return $grade->grade >= 5.0 || in_array($grade->status, ['passed', 'pass']);
        })->pluck('subject_id')->toArray();
        $failedSubjectIds = $userGrades->filter(function ($grade) {
            return ($grade->grade !== null && $grade->grade < 5.0) || in_array($grade->status, ['failed', 'fail']);
        })->pluck('subject_id')->toArray();
        $gradedSubjectIds = $userGrades->filter(function($grade) {
            return $grade->grade !== null || $grade->status !== null;
        })->pluck('subject_id')->toArray();

        $user = \App\Models\User::find($userId);
        // K(s) — Định hướng kỹ năng cá nhân do sinh viên CHỌN (chuyên ngành hướng tới).
        // Môn thuộc nhóm kỹ năng tương ứng với định hướng sẽ được cộng điểm ưu tiên (Bảng 2.8/2.9).
        $skillFocus = $user?->pref_skill_focus;
        $allSubjects = $this->getSubjectsForUserCurriculum($user);

        if (!$currentSemester) {
            $currentSemester = 1;
            foreach ($gradedSubjectIds as $pid) {
                $sub = $allSubjects->firstWhere('id', $pid);
                if ($sub && isset($sub->assigned_semester_index)) {
                    if ($sub->assigned_semester_index >= $currentSemester) {
                        $currentSemester = $sub->assigned_semester_index + 1;
                    }
                }
            }
        }

        // Get currently retaking subjects from active study plan
        $currentRetakeSubjectIds = [];
        $activePlan = null;
        if ($studyPlanId) {
            $activePlan = \App\Models\StudyPlan::find($studyPlanId);
        } else {
            $activePlan = \App\Models\StudyPlan::where('user_id', $userId)
                ->where('is_active', true)
                ->first();
        }

        if ($activePlan) {
            $currentRetakeSubjectIds = \App\Models\StudyPlanSubject::whereHas('semester', function ($query) use ($activePlan) {
                $query->where('study_plan_id', $activePlan->id);
            })
            ->where('is_retake', true)
            ->pluck('subject_id')
            ->toArray();
        }

        // Load nhóm môn học một lần để tránh N+1 khi kiểm tra tiên quyết ngầm định
        $basicGroupIds       = \App\Models\ProgramGroup::where('name', 'like', '%Đại cương%')
            ->orWhere('name', 'like', '%Anh văn%')
            ->pluck('id')->toArray();
        $majorGroupIds       = \App\Models\ProgramGroup::where('name', 'like', '%Cơ sở ngành%')
            ->pluck('id')->toArray();
        $specializedGroupIds = \App\Models\ProgramGroup::where('name', 'like', '%Chuyên ngành%')
            ->pluck('id')->toArray();

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

            $isFailedSubject = in_array($subject->id, $failedSubjectIds);

            // Môn rớt: sinh viên đã học qua → điều kiện tiên quyết đã được đáp ứng trước đó
            // Luôn cho phép học lại, không bỏ qua dù prerequisite check không đủ
            if ($isFailedSubject) {
                $hasUnmetPrerequisite = false;
            }

            $subject->prerequisites_info = $prereqDetails;
            $subject->can_study = !$hasUnmetPrerequisite;
            $subject->is_retake_candidate = $isFailedSubject;

            if ($hasUnmetPrerequisite) {
                continue;
            }

            // Calculate Score
            $score = 0;
            $reasons = [];

            // Môn rớt: ưu tiên tuyệt đối — phải học lại ngay
            if ($isFailedSubject) {
                $score += 100;
                $reasons[] = 'Cần học lại (đã rớt)';
            }

            // +50 if it's already added as retake row in plan
            if (in_array($subject->id, $currentRetakeSubjectIds)) {
                $score += 30;
                $reasons[] = 'Đã thêm vào kế hoạch học lại';
            }

            // Phân loại bắt buộc/tự chọn theo đúng cột is_elective. requirement_type
            // mô tả điều kiện hoàn thành, không phải loại môn học.
            if (!$subject->is_elective) {
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
                    $reasons[] = 'Đúng học kỳ chuẩn';
                }
            }

            // K(s): +40 nếu môn thuộc nhóm kỹ năng tương ứng với định hướng sinh viên đã chọn (Bảng 2.9)
            if ($skillFocus
                && $subject->skillGroup
                && $subject->skillGroup->focus_area === $skillFocus) {
                $score += 40;
                $gname = $subject->skillGroup->name ?? 'nhóm này';
                $reasons[] = "Phù hợp định hướng của bạn (nhóm {$gname})";
            }

            $recommendations[] = [
                'subject'        => $subject,
                'score'          => $score,
                'reasons'        => $reasons,
                'dependent_count'=> $subject->relatedRelations->where('type', 'prerequisite')->count(),
                'is_failed'      => $isFailedSubject,
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
            return Subject::with(['prerequisites', 'corequisites', 'relatedRelations', 'skillGroup'])->get();
        }

        $curriculumSubjects = \App\Models\CurriculumSubject::where('curriculum_framework_id', $frameworkId)
            ->with([
                'subject.prerequisites',
                'subject.corequisites',
                'subject.relatedRelations',
                'subject.skillGroup',
                'semester',
            ])
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
