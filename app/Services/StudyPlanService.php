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
     * @param int|null $dynamicTargetSems Target semesters for dynamic mode
     * @return StudyPlan
     */
    public function generatePlan(int $userId, string $name, string $mode = 'normal', ?int $dynamicTargetSems = null): StudyPlan
    {
        return DB::transaction(function () use ($userId, $name, $mode, $dynamicTargetSems) {
            // ═══ QUAN TRỌNG: Deactivate tất cả kế hoạch cũ của user ═══
            // Đảm bảo mỗi sinh viên chỉ có DUY NHẤT 1 kế hoạch is_active = true
            StudyPlan::where('user_id', $userId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Evaluate progress to check GPA
            $progressService = new \App\Services\ProgressService();
            $progress = $progressService->evaluateProgress($userId);
            
            // Check for low GPA and force slow mode if not already dynamic
            if ($mode !== 'dynamic' && $progress['current_gpa'] > 0 && $progress['current_gpa'] < 2.0) {
                $mode = 'slow';
            }

            $maxCredits = $this->getMaxCreditsByMode($mode);
            
            // Fetch all grades and keep only the highest grade per subject
            $allUserGrades = UserGrade::where('user_id', $userId)->get();
            $userGrades = $allUserGrades->groupBy('subject_id')->map(function ($grades) {
                return $grades->sortByDesc('grade')->first();
            })->values();

            $passedSubjectIds = $userGrades->filter(function($g) { return $g->grade > 5.0 || in_array($g->status, ['passed', 'pass']); })->pluck('subject_id')->toArray();
            $failedSubjectIds = $userGrades->filter(function($g) { return ($g->grade <= 5.0 && $g->grade !== null) || in_array($g->status, ['failed', 'fail']); })->pluck('subject_id')->toArray();
            $gradedSubjectIds = $userGrades->filter(function($g) { return $g->grade !== null || $g->status !== null; })->pluck('subject_id')->toArray();

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
            
            $currentSem = 1;
            foreach ($gradedSubjectIds as $pid) {
                $sub = $allSubjects->firstWhere('id', $pid);
                if ($sub && isset($sub->assigned_semester_index)) {
                    if ($sub->assigned_semester_index >= $currentSem) {
                        $currentSem = $sub->assigned_semester_index + 1;
                    }
                }
            }

            // Không lọc bỏ các môn đã học, để bản kế hoạch hiển thị toàn bộ lộ trình 4 năm
            $remainingSubjects = clone $allSubjects;

            $plannedSubjectIds = []; // Bắt đầu rỗng để mô phỏng lại toàn bộ lộ trình từ đầu
            
            $targetSemsToSave = $dynamicTargetSems ?? match($mode) {
                'fast' => 6,
                'slow' => 10,
                default => 8
            };

            $plan = StudyPlan::create([
                'user_id'               => $userId,
                'name'                  => $name,
                'mode'                  => $mode,
                'target_semester_count' => $targetSemsToSave,
                'is_active'             => true,   // Kế hoạch mới luôn là active
                'is_saved'              => true,   // Tự động lưu ngay khi tạo
            ]);


            $semesterIndex = 1;

            // Xây dựng lại các học kỳ đã qua từ SemesterHistory
            $histories = \App\Models\SemesterHistory::where('user_id', $userId)
                ->with('items.subject')
                ->orderBy('semester_number')
                ->get();

            $historySubjectIds = []; // Danh sách tất cả các môn đã có trong lịch sử (bao gồm cả pass và fail để tránh lặp nếu không cần thiết)
            $lastHistorySemNumber = 0;

            foreach ($histories as $history) {
                $semNumber = $history->semester_number;
                $lastHistorySemNumber = max($lastHistorySemNumber, $semNumber);
                
                $semester = StudyPlanSemester::create([
                    'study_plan_id' => $plan->id,
                    'semester_index' => $semNumber,
                    'expected_credits' => $history->total_credits,
                ]);

                foreach ($history->items as $item) {
                    if ($item->subject) {
                        StudyPlanSubject::create([
                            'study_plan_semester_id' => $semester->id,
                            'subject_id' => $item->subject_id,
                            'is_completed' => $item->status === 'pass',
                        ]);
                        $plannedSubjectIds[] = $item->subject_id;
                        $historySubjectIds[] = $item->subject_id;
                    }
                }
            }

            if ($lastHistorySemNumber > 0) {
                $semesterIndex = $lastHistorySemNumber + 1;
            }

            // Xóa các môn đã pass hoặc đã có trong lịch sử khỏi remainingSubjects
            // Ngoại trừ các môn fail trong lịch sử (để được học lại)
            $remainingSubjects = clone $allSubjects;
            $remainingSubjects = $remainingSubjects->reject(function ($s) use ($passedSubjectIds, $historySubjectIds, $failedSubjectIds) {
                if (in_array($s->id, $passedSubjectIds)) {
                    // Nếu môn đã pass và ĐÃ nằm trong lịch sử rồi, thì bỏ qua không rải lại nữa
                    if (in_array($s->id, $historySubjectIds)) {
                        return true;
                    }
                    // Nếu môn đã pass nhưng chưa có trong lịch sử (nhập lẻ), vẫn giữ lại để rải
                    return false;
                }
                
                // Môn chưa pass
                if (in_array($s->id, $historySubjectIds)) {
                    // Nếu môn nằm trong lịch sử mà chưa pass (tức là fail), ta phải rải lại để học lại!
                    return false;
                }
                
                return false;
            });

            // Nếu mode là normal VÀ sinh viên chưa có điểm nào (người dùng mới),
            // copy 100% từ chương trình khung gốc để giữ đúng lộ trình chuẩn mực của trường.
            if ($mode === 'normal' && empty($gradedSubjectIds) && $histories->isEmpty()) {
                $groupedSubjects = $remainingSubjects->groupBy('assigned_semester_index')->sortKeys();
                $maxSemesterIndex = 0;
                
                foreach ($groupedSubjects as $semIndex => $subjectsForThisSemester) {
                    $currentSemesterCredits = $subjectsForThisSemester->sum('credits');
                    $maxSemesterIndex = max($maxSemesterIndex, $semIndex);
                    
                    $semester = StudyPlanSemester::create([
                        'study_plan_id' => $plan->id,
                        'semester_index' => $semIndex,
                        'expected_credits' => $currentSemesterCredits,
                    ]);

                    foreach ($subjectsForThisSemester as $subject) {
                        StudyPlanSubject::create([
                            'study_plan_semester_id' => $semester->id,
                            'subject_id' => $subject->id,
                            'is_completed' => false,
                        ]);
                    }
                }
                
                $plan->update(['target_semester_count' => $maxSemesterIndex]);
                return $plan->load('semesters.subjects.subject');
            }

            // Sử dụng Greedy Algorithm cho:

            $basicGroupIds = \App\Models\ProgramGroup::where('name', 'like', '%Đại cương%')
                ->orWhere('name', 'like', '%Anh văn%')
                ->pluck('id')->toArray();
            $majorGroupIds = \App\Models\ProgramGroup::where('name', 'like', '%Cơ sở ngành%')
                ->pluck('id')->toArray();
            $specializedGroupIds = \App\Models\ProgramGroup::where('name', 'like', '%Chuyên ngành%')
                ->pluck('id')->toArray();
            
            $maxCreditsModeLimit = $this->getMaxCreditsByMode($mode);
            while ($remainingSubjects->count() > 0) {
                // Tính toán để rải đều số tín chỉ
                $unpassedCredits = 0;
                foreach ($remainingSubjects as $rs) {
                    if (!in_array($rs->id, $passedSubjectIds)) {
                        $unpassedCredits += $rs->credits;
                    }
                }
                
                if ($unpassedCredits > 0) {
                    $targetTotalSems = $dynamicTargetSems ?? match ($mode) {
                        'fast' => 6,
                        'slow' => 10,
                        default => 8,
                    };
                    $estimatedSems = max(1, $targetTotalSems - $semesterIndex + 1);
                    // Số tín chỉ mục tiêu để chia đều
                    $maxCredits = ceil($unpassedCredits / $estimatedSems);
                    
                    if ($maxCredits > $maxCreditsModeLimit) {
                        $maxCredits = $maxCreditsModeLimit; // Đảm bảo không vượt quá giới hạn tuyệt đối của mode
                    }
                    
                    // Thêm một chút linh hoạt (buffer) để dễ xếp môn (ví dụ môn 3 chỉ làm lố 1 chỉ)
                    $maxCredits += 2; 
                } else {
                    $maxCredits = $this->getMaxCreditsByMode($mode);
                }

                // Find available subjects for this semester
                $availableSubjects = $remainingSubjects->filter(function ($subject) use ($passedSubjectIds, $plannedSubjectIds, $semesterIndex, $basicGroupIds, $majorGroupIds, $specializedGroupIds, $allSubjects, $user, $currentSem) {
                    // Check semester availability (offered_in)
                    $isOddSemester = ($semesterIndex % 2) !== 0;
                    if ($isOddSemester && $subject->offered_in === '2') {
                        return false; // Subject only offered in even semesters
                    }
                    if (!$isOddSemester && $subject->offered_in === '1') {
                        return false; // Subject only offered in odd semesters
                    }

                    // Cố định các môn đã Pass vào đúng học kỳ chuẩn của chương trình khung (nếu môn này chưa có trong lịch sử)
                    if (in_array($subject->id, $passedSubjectIds)) {
                        // Nếu semesterIndex hiện tại nhỏ hơn học kỳ chuẩn, chờ đến học kỳ chuẩn mới rải
                        $assignedSem = $subject->assigned_semester_index ?? 1;
                        if ($assignedSem > $semesterIndex) {
                            return false;
                        }
                        // Nếu đã tới hoặc qua học kỳ chuẩn, cứ xếp môn này vào học kỳ hiện tại (trả nợ/xếp bù)
                        return true; 
                    } else {
                        // Các môn CHƯA HỌC (chưa pass) thì không được xếp vào học kỳ trong quá khứ
                        if ($semesterIndex < $currentSem) {
                            return false;
                        }
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
                $availableSubjects = $availableSubjects->sortByDesc(function ($subject) use ($failedSubjectIds, $semesterIndex, $mode, $basicGroupIds, $majorGroupIds) {
                    $score = 0;
                    if (in_array($subject->program_group_id, $basicGroupIds)) {
                        $score += 200; // Ưu tiên Đại cương hoàn thành sớm nhất
                    } elseif (in_array($subject->program_group_id, $majorGroupIds)) {
                        $score += 150; // Ưu tiên Cơ sở ngành hoàn thành sớm để mở khóa Đồ án
                    }
                    if (in_array($subject->id, $failedSubjectIds)) $score += 100; // Failed subjects first
                    if ($subject->requirement_type && $subject->requirement_type !== 'none') $score += 30; // Core subjects
                    $score += (50 * $subject->relatedRelations->where('type', 'prerequisite')->count()); // Unlocks more subjects
                    
                    if (stripos($subject->name, 'Đồ án') !== false || stripos($subject->name, 'Thực tập') !== false) {
                        $score += 300; // Ưu tiên xếp các môn Đồ án, Thực tập sớm nhất có thể nếu đủ điều kiện
                    }
                    
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
                $actualSemesterCredits = 0;
                $subjectsForThisSemester = [];

                foreach ($availableSubjects as $key => $subject) {
                    $isPassed = in_array($subject->id, $passedSubjectIds);

                    if ($isPassed) {
                        // Môn đã pass thì LUÔN LUÔN được đưa vào học kỳ cố định của nó
                        $subjectsForThisSemester[] = $subject;
                        $actualSemesterCredits += $subject->credits;
                        
                        $remainingSubjects = $remainingSubjects->reject(function ($s) use ($subject) {
                            return $s->id === $subject->id;
                        });
                        continue;
                    }

                    // Môn chưa học: 
                    // 1. Phải thỏa mãn số tín chỉ phân bổ đều (currentSemesterCredits)
                    // 2. TỔNG số tín chỉ thực tế của học kỳ (bao gồm cả môn đã pass) KHÔNG được vượt quá giới hạn tuyệt đối của Mode
                    if ($currentSemesterCredits + $subject->credits <= $maxCredits && 
                        $actualSemesterCredits + $subject->credits <= $maxCreditsModeLimit) {
                        
                        $subjectsForThisSemester[] = $subject;
                        $currentSemesterCredits += $subject->credits;
                        $actualSemesterCredits += $subject->credits;
                        
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
                        'expected_credits' => $actualSemesterCredits,
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
        if ($mode === 'dynamic') {
            return 25; // Flexible max for dynamic calculations
        }
        return match ($mode) {
            'fast' => 22,
            'slow' => 14,
            default => 18,
        };
    }

    private function getSubjectsForUserCurriculum(int $userId)
    {
        $user = \App\Models\User::find($userId);
        $frameworkId = null;

        if ($user && $user->pref_academic_year && $user->pref_program_type) {
            $program = \App\Models\TrainingProgram::where('academic_year', $user->pref_academic_year)
                ->where('program_type', $user->pref_program_type)
                ->first();

            if ($program && $framework = $program->curriculumFrameworks()->first()) {
                $frameworkId = $framework->id;
            }
        }

        if (!$frameworkId) {
            $subjects = Subject::with(['prerequisites', 'relatedRelations'])->get();
            foreach ($subjects as $subject) {
                $subject->assigned_semester_index = (int) ($subject->semester_id ?? 1);
            }

            return $subjects;
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
            $subjects->push($subject);
        }

        return $subjects->unique('id')->values();
    }

    private function canScheduleSubject($subject, array $plannedSubjectIds, int $semesterIndex, array $basicGroupIds, array $majorGroupIds, array $specializedGroupIds, $allProgramSubjects): bool
    {
        $isOddSemester = ($semesterIndex % 2) !== 0;
        if ($isOddSemester && $subject->offered_in === '2') {
            return false;
        }
        if (!$isOddSemester && $subject->offered_in === '1') {
            return false;
        }

        foreach ($subject->prerequisites as $prereq) {
            if (!in_array($prereq->id, $plannedSubjectIds)) {
                return false;
            }
        }

        $reqType = $subject->requirement_type;
        if (!$reqType || $reqType === 'none') {
            return true;
        }

        if ($reqType === 'completed_all') {
            $requiredIds = $allProgramSubjects->pluck('id')->reject(fn($id) => $id == $subject->id)->toArray();
        } elseif ($reqType === 'completed_basic') {
            $requiredIds = $allProgramSubjects->whereIn('program_group_id', $basicGroupIds)->pluck('id')->toArray();
        } elseif ($reqType === 'completed_major') {
            $requiredIds = $allProgramSubjects->whereIn('program_group_id', $majorGroupIds)->pluck('id')->toArray();
        } elseif ($reqType === 'completed_specialized') {
            $requiredIds = $allProgramSubjects->whereIn('program_group_id', $specializedGroupIds)->pluck('id')->toArray();
        } else {
            $requiredIds = [];
        }

        foreach ($requiredIds as $id) {
            if (!in_array($id, $plannedSubjectIds)) {
                return false;
            }
        }

        return true;
    }

    public function applySuggestionsAndRedistribute(int $planId, array $suggestedSubjectIds, int $targetSemesterIndex): StudyPlan
    {
        return DB::transaction(function () use ($planId, $suggestedSubjectIds, $targetSemesterIndex) {
            $plan = StudyPlan::with(['semesters.subjects.subject'])->findOrFail($planId);
            $userId = $plan->user_id;
            $mode = $plan->mode;
            $maxCredits = $this->getMaxCreditsByMode($mode);

            // Fetch passed/failed subjects
            $allUserGrades = UserGrade::where('user_id', $userId)->get();
            $userGrades = $allUserGrades->groupBy('subject_id')->map(function ($grades) {
                return $grades->sortByDesc('grade')->first();
            })->values();

            $passedSubjectIds = $userGrades->filter(function($g) { return $g->grade > 5.0 || in_array($g->status, ['passed', 'pass']); })->pluck('subject_id')->toArray();
            $failedSubjectIds = $userGrades->filter(function($g) { return ($g->grade <= 5.0 && $g->grade !== null) || in_array($g->status, ['failed', 'fail']); })->pluck('subject_id')->toArray();
            $allProgramSubjects = $this->getSubjectsForUserCurriculum($userId);
            $allowedSubjectIds = $allProgramSubjects->pluck('id')->toArray();
            $suggestedSubjectIds = array_values(array_unique(array_intersect($suggestedSubjectIds, $allowedSubjectIds)));

            // We now trust $targetSemesterIndex from the frontend because the frontend bug was fixed.

            // Lấy program_group_ids cho greedy
            $basicGroupIds = \App\Models\ProgramGroup::where('name', 'like', '%Đại cương%')
                ->orWhere('name', 'like', '%Anh văn%')
                ->pluck('id')->toArray();
            $majorGroupIds = \App\Models\ProgramGroup::where('name', 'like', '%Cơ sở ngành%')
                ->pluck('id')->toArray();
            $specializedGroupIds = \App\Models\ProgramGroup::where('name', 'like', '%Chuyên ngành%')
                ->pluck('id')->toArray();

            // Gather past subjects (semesters < targetSemesterIndex)
            $plannedSubjectIds = [];
            foreach ($plan->semesters as $sem) {
                if ($sem->semester_index < $targetSemesterIndex) {
                    foreach ($sem->subjects as $ss) {
                        $plannedSubjectIds[] = $ss->subject_id;
                    }
                }
            }

            // Get remaining subjects to distribute (semesters >= targetSemesterIndex)
            $remainingSubjectIds = [];
            $semestersToDelete = [];
            foreach ($plan->semesters as $sem) {
                if ($sem->semester_index >= $targetSemesterIndex) {
                    $semestersToDelete[] = $sem->id;
                    foreach ($sem->subjects as $ss) {
                        $remainingSubjectIds[] = $ss->subject_id;
                    }
                }
            }
            
            // Lọc bỏ những suggestions đã nằm trong quá khứ NHƯNG KHÔNG PHẢI MÔN RỚT
            $validSuggestedSubjectIds = [];
            foreach ($suggestedSubjectIds as $subId) {
                if (in_array($subId, $plannedSubjectIds) && !in_array($subId, $failedSubjectIds)) {
                    // Đã có trong kế hoạch cũ, và chưa rớt (tức là chưa học tới hoặc đã pass)
                    // Không gợi ý thêm vào học kỳ mục tiêu (để tránh lặp môn)
                    continue;
                }
                $validSuggestedSubjectIds[] = $subId;
            }
            $suggestedSubjectIds = $validSuggestedSubjectIds;

            // Đảm bảo suggestions cũng nằm trong mảng cần phân bổ
            $remainingSubjectIds = array_unique(array_merge($remainingSubjectIds, $suggestedSubjectIds));
            
            // Xóa các học kỳ từ targetSemesterIndex trở đi
            StudyPlanSubject::whereIn('study_plan_semester_id', $semestersToDelete)->delete();
            StudyPlanSemester::whereIn('id', $semestersToDelete)->delete();

            // Load đầy đủ thông tin của các remaining subjects
            $allSubjects = $allProgramSubjects->whereIn('id', $remainingSubjectIds)->values();
            
            // Gắn assigned_semester_index để dùng cho greedy (nếu cần, nhưng rải môn có thể bỏ qua assigned vì đã ở giai đoạn sau, cứ cho mặc định là 1)
            foreach ($allSubjects as $sub) {
                $sub->assigned_semester_index = (int) ($sub->assigned_semester_index ?? $sub->semester_id ?? 1);
            }
            
            $remainingSubjects = clone $allSubjects;
            $semesterIndex = $targetSemesterIndex;

            // Xử lý riêng học kỳ targetSemesterIndex: CHỈ CHỨA CÁC MÔN ĐƯỢC GỢI Ý
            $targetSubjects = collect();
            $targetCredits = 0;
            $targetCandidates = $remainingSubjects->whereIn('id', $suggestedSubjectIds)->sortByDesc(function ($subject) use ($failedSubjectIds) {
                $score = 0;
                if (in_array($subject->id, $failedSubjectIds)) $score += 100;
                if ($subject->requirement_type && $subject->requirement_type !== 'none') $score += 30;
                $score += (5 * $subject->relatedRelations->where('type', 'prerequisite')->count());
                return $score;
            });

            foreach ($targetCandidates as $subject) {
                // Bỏ qua các ràng buộc xếp môn thông thường vì danh sách gợi ý đã được 
                // RecommendationService kiểm duyệt kỹ lưỡng (đã check điều kiện tiên quyết, 
                // số tín chỉ, và học kỳ mở môn). Bắt buộc phải xếp vào học kỳ mục tiêu.
                $targetSubjects->push($subject);
                $targetCredits += $subject->credits;
            }
            if ($targetSubjects->count() > 0) {
                $actualSemesterCredits = $targetCredits;
                
                $semester = StudyPlanSemester::create([
                    'study_plan_id' => $plan->id,
                    'semester_index' => $semesterIndex,
                    'expected_credits' => $actualSemesterCredits,
                ]);

                foreach ($targetSubjects as $subject) {
                    StudyPlanSubject::create([
                        'study_plan_semester_id' => $semester->id,
                        'subject_id' => $subject->id,
                        'is_completed' => in_array($subject->id, $passedSubjectIds),
                    ]);
                    $plannedSubjectIds[] = $subject->id;
                }
                
                // Xóa khỏi remaining
                $targetSubjectIds = $targetSubjects->pluck('id')->toArray();
                $remainingSubjects = $remainingSubjects->reject(function ($s) use ($targetSubjectIds) {
                    return in_array($s->id, $targetSubjectIds);
                });
                $semesterIndex++;
            }

            // Tiếp tục vòng lặp greedy cho các môn còn lại
            $maxCreditsModeLimit = $this->getMaxCreditsByMode($mode);
            while ($remainingSubjects->count() > 0) {
                // Tính toán để rải đều số tín chỉ
                $unpassedCredits = 0;
                foreach ($remainingSubjects as $rs) {
                    if (!in_array($rs->id, $passedSubjectIds)) {
                        $unpassedCredits += $rs->credits;
                    }
                }
                
                if ($unpassedCredits > 0) {
                    $targetTotalSems = $dynamicTargetSems ?? match ($mode) {
                        'fast' => 6,
                        'slow' => 10,
                        default => 8,
                    };
                    $estimatedSems = max(1, $targetTotalSems - $semesterIndex + 1);
                    $maxCredits = ceil($unpassedCredits / $estimatedSems);
                    if ($maxCredits > 30) {
                        $maxCredits = 30;
                    }
                    $maxCredits += 2; 
                } else {
                    $maxCredits = $this->getMaxCreditsByMode($mode);
                }

                $availableSubjects = $remainingSubjects->filter(function ($subject) use ($plannedSubjectIds, $semesterIndex, $basicGroupIds, $majorGroupIds, $specializedGroupIds, $allProgramSubjects) {
                    return $this->canScheduleSubject($subject, $plannedSubjectIds, $semesterIndex, $basicGroupIds, $majorGroupIds, $specializedGroupIds, $allProgramSubjects);
                });

                if ($availableSubjects->count() === 0) {
                    Log::warning("Cannot resolve prerequisites in redistribute for User {$userId}");
                    break;
                }

                $availableSubjects = $availableSubjects->sortByDesc(function ($subject) use ($failedSubjectIds, $semesterIndex, $mode, $basicGroupIds, $majorGroupIds) {
                    $score = 0;
                    if (in_array($subject->program_group_id, $basicGroupIds)) {
                        $score += 200;
                    } elseif (in_array($subject->program_group_id, $majorGroupIds)) {
                        $score += 150;
                    }
                    if (in_array($subject->id, $failedSubjectIds)) $score += 100;
                    if ($subject->requirement_type && $subject->requirement_type !== 'none') $score += 30;
                    $score += (50 * $subject->relatedRelations->where('type', 'prerequisite')->count());
                    
                    if (stripos($subject->name, 'Đồ án') !== false || stripos($subject->name, 'Thực tập') !== false) {
                        $score += 300; // Ưu tiên xếp các môn Đồ án, Thực tập sớm nhất có thể nếu đủ điều kiện
                    }
                    
                    if ($mode === 'normal' || $mode === 'slow') {
                        $assignedSem = $subject->assigned_semester_index;
                        if ($assignedSem) {
                            if ($assignedSem > $semesterIndex) {
                                $score -= (($assignedSem - $semesterIndex) * 100);
                            } elseif ($assignedSem == $semesterIndex) {
                                $score += 200;
                            } else {
                                $score += 150;
                            }
                        }
                    }
                    return $score;
                });

                $currentSemesterCredits = 0;
                $actualSemesterCredits = 0;
                $subjectsForThisSemester = [];

                foreach ($availableSubjects as $key => $subject) {
                    $isPassed = in_array($subject->id, $passedSubjectIds);

                    if ($isPassed) {
                        $subjectsForThisSemester[] = $subject;
                        $actualSemesterCredits += $subject->credits;
                        $remainingSubjects = $remainingSubjects->reject(function ($s) use ($subject) {
                            return $s->id === $subject->id;
                        });
                        continue;
                    }

                    if ($currentSemesterCredits + $subject->credits <= $maxCredits && 
                        $actualSemesterCredits + $subject->credits <= $maxCreditsModeLimit) {
                        
                        $subjectsForThisSemester[] = $subject;
                        $currentSemesterCredits += $subject->credits;
                        $actualSemesterCredits += $subject->credits;
                        
                        $remainingSubjects = $remainingSubjects->reject(function ($s) use ($subject) {
                            return $s->id === $subject->id;
                        });
                    }
                }

                if (count($subjectsForThisSemester) > 0) {
                    $semester = StudyPlanSemester::create([
                        'study_plan_id' => $plan->id,
                        'semester_index' => $semesterIndex,
                        'expected_credits' => $actualSemesterCredits,
                    ]);

                    foreach ($subjectsForThisSemester as $subject) {
                        StudyPlanSubject::create([
                            'study_plan_semester_id' => $semester->id,
                            'subject_id' => $subject->id,
                            'is_completed' => in_array($subject->id, $passedSubjectIds),
                        ]);
                        $plannedSubjectIds[] = $subject->id;
                    }
                    $semesterIndex++;
                } else {
                    break; 
                }
            }

            $plan->update(['target_semester_count' => $semesterIndex - 1]);
            return $plan->load('semesters.subjects.subject');
        });
    }

    /**
     * Đổi chế độ học của kế hoạch hiện tại và phân bổ lại môn học.
     *
     * Không tạo kế hoạch mới — chỉ cập nhật mode + target_semester_count
     * và tái tính phân bổ môn từ học kỳ hiện tại trở đi.
     *
     * Các mode:
     *  - slow    : Học nhẹ  (~14 TC/kỳ), kéo dài lộ trình
     *  - normal  : Cân bằng (~18 TC/kỳ), 4 năm tiêu chuẩn
     *  - fast    : Tăng tốc (~22 TC/kỳ), rút ngắn lộ trình
     *
     * @param int    $planId     ID kế hoạch cần đổi mode
     * @param string $newMode    Mode mới: slow | normal | fast
     * @param int    $userId     User ID để verify ownership
     * @return StudyPlan
     */
    public function changeMode(int $planId, string $newMode, int $userId): StudyPlan
    {
        $plan = StudyPlan::where('id', $planId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->firstOrFail();

        // Credits giới hạn theo mode mới
        $newMaxCredits = match($newMode) {
            'fast'  => 22,
            'slow'  => 14,
            default => 18,
        };

        // Tính số kỳ cần thiết dựa trên mode mới
        $progressService  = new \App\Services\ProgressService();
        $progress         = $progressService->evaluateProgress($userId);
        $remainingCredits = $progress['remaining_credits'];

        // Số học kỳ tối thiểu để hoàn thành với mode mới
        $newTargetSems = (int) ceil($remainingCredits / $newMaxCredits);
        $completedSems = $progress['completed_semesters'];
        $totalSems     = $completedSems + max(1, $newTargetSems);

        return DB::transaction(function () use ($plan, $newMode, $newMaxCredits, $totalSems, $userId) {
            // Cập nhật mode và số kỳ mục tiêu
            $plan->update([
                'mode'                  => $newMode,
                'target_semester_count' => $totalSems,
            ]);

            // Xóa các học kỳ chưa hoàn thành trong kế hoạch (giữ lại kỳ đã pass)
            foreach ($plan->semesters as $sem) {
                $allCompleted = $sem->subjects->every(fn($s) => $s->is_completed);
                if (!$allCompleted) {
                    $sem->subjects()->delete();
                    $sem->delete();
                }
            }

            // Tái tạo kế hoạch với mode mới — sử dụng lại engine generatePlan
            // bằng cách gọi generatePlan với cùng tên nhưng mode mới
            $newPlan = $this->generatePlan($userId, $plan->name, $newMode, $totalSems);

            return $newPlan;
        });
    }
}

