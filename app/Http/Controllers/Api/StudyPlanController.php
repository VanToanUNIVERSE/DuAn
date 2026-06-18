<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StudyPlanService;
use App\Services\AcademicEvaluationService;
use App\Services\PlanAdjustmentService;
use App\Models\StudyPlan;
use App\Models\UserGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudyPlanController extends Controller
{
    protected $studyPlanService;
    protected $evaluationService;
    protected $adjustmentService;

    public function __construct(
        StudyPlanService $studyPlanService,
        AcademicEvaluationService $evaluationService,
        PlanAdjustmentService $adjustmentService
    ) {
        $this->studyPlanService = $studyPlanService;
        $this->evaluationService = $evaluationService;
        $this->adjustmentService = $adjustmentService;
    }

    public function generate(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized or missing user_id'], 401);
        }

        $request->validate([
            'name' => 'required|string',
            'mode' => 'nullable|string|in:normal,fast,slow',
        ]);

        $mode = $request->input('mode', 'normal');
        $name = $request->input('name');

        $plan = $this->studyPlanService->generatePlan($userId, $name, $mode);
        $plan = $this->attachGrades($plan, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Study plan generated successfully',
            'data' => $plan
        ]);
    }

    public function index(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        $plans = StudyPlan::where('user_id', $userId)->get();
        foreach ($plans as $plan) {
            $this->attachGrades($plan, $userId);
        }
        return response()->json(['success' => true, 'data' => $plans]);
    }

    private function attachGrades($plan, $userId)
    {
        $plan->loadMissing('semesters.subjects.subject.relatedRelations', 'semesters.subjects.subject.prerequisites');
        $userGrades = UserGrade::where('user_id', $userId)->pluck('grade', 'subject_id')->toArray();

        foreach ($plan->semesters as $semester) {
            foreach ($semester->subjects as $ss) {
                if ($ss->subject) {
                    if (isset($userGrades[$ss->subject_id])) {
                        $ss->grade = $userGrades[$ss->subject_id];
                    } else {
                        $ss->grade = null;
                    }

                    // Tính điểm ưu tiên cơ bản để giao diện (FE) biết môn này có quan trọng không
                    $dependentCount = $ss->subject->relatedRelations->where('type', 'prerequisite')->count();
                    $ss->is_highly_recommended = $dependentCount >= 2 || in_array($ss->subject->requirement_type, ['completed_basic', 'completed_major']);

                    // Gắn thông tin tiên quyết cho Modal
                    $prereqDetails = [];
                    // 1. Tiên quyết cứng
                    $passedSubjectIds = array_keys(array_filter($userGrades, function ($g) {
                        return $g > 5.0; }));
                    foreach ($ss->subject->prerequisites as $prereq) {
                        $isPassed = in_array($prereq->id, $passedSubjectIds);
                        $prereqDetails[] = [
                            'id' => $prereq->id,
                            'name' => $prereq->name,
                            'is_passed' => $isPassed
                        ];
                    }

                    // 2. Tiên quyết ngầm định
                    $reqType = $ss->subject->requirement_type;
                    if ($reqType && $reqType !== 'none') {
                        $basicGroupIds = [1, 2, 3];
                        $majorGroupIds = [4, 5];
                        $specializedGroupIds = [6, 7];

                        $allSubjects = \App\Models\Subject::all();
                        $implicitPrereqSubjects = collect();

                        if ($reqType === 'completed_basic') {
                            $implicitPrereqSubjects = $allSubjects->whereIn('program_group_id', $basicGroupIds);
                        } elseif ($reqType === 'completed_major') {
                            $implicitPrereqSubjects = $allSubjects->whereIn('program_group_id', $majorGroupIds);
                        } elseif ($reqType === 'completed_specialized') {
                            $implicitPrereqSubjects = $allSubjects->whereIn('program_group_id', $specializedGroupIds);
                        } elseif ($reqType === 'completed_all') {
                            $implicitPrereqSubjects = $allSubjects->where('id', '!=', $ss->subject->id);
                        }

                        foreach ($implicitPrereqSubjects as $impSub) {
                            if (collect($prereqDetails)->contains('id', $impSub->id))
                                continue;
                            $isPassed = in_array($impSub->id, $passedSubjectIds);
                            $prereqDetails[] = [
                                'id' => $impSub->id,
                                'name' => $impSub->name,
                                'is_passed' => $isPassed
                            ];
                        }
                    }
                    $ss->subject->prerequisites_info = $prereqDetails;
                }
            }
        }
        return $plan;
    }

    public function updateGrade(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized or missing user_id'], 401);
        }

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'study_plan_id' => 'required|exists:study_plans,id',
            'grade' => 'nullable|numeric|min:0|max:10',
            'status' => 'nullable|string|in:passed,failed'
        ]);

        if ($request->input('grade') === null) {
            UserGrade::where('user_id', $userId)
                ->where('subject_id', $request->input('subject_id'))
                ->delete();

            $studyPlan = StudyPlan::with('semesters.subjects')->find($request->input('study_plan_id'));
            if ($studyPlan && $studyPlan->user_id == $userId) {
                foreach ($studyPlan->semesters as $semester) {
                    foreach ($semester->subjects as $planSubject) {
                        if ($planSubject->subject_id == $request->input('subject_id')) {
                            $planSubject->update(['is_completed' => false]);
                        }
                    }
                }
            }
        } else {
            UserGrade::updateOrCreate(
                ['user_id' => $userId, 'subject_id' => $request->input('subject_id')],
                ['grade' => $request->input('grade'), 'status' => $request->input('status')]
            );

            $studyPlan = StudyPlan::with('semesters.subjects')->find($request->input('study_plan_id'));
            if ($studyPlan && $studyPlan->user_id == $userId) {
                foreach ($studyPlan->semesters as $semester) {
                    foreach ($semester->subjects as $planSubject) {
                        if ($planSubject->subject_id == $request->input('subject_id')) {
                            $planSubject->update(['is_completed' => ($request->input('status') === 'passed')]);
                        }
                    }
                }
            }
        }

        $evaluation = $this->evaluationService->evaluate($userId, $studyPlan->mode ?? 'normal');

        return response()->json([
            'success' => true,
            'evaluation' => $evaluation
        ]);
    }

    public function adjust(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'study_plan_id' => 'required|exists:study_plans,id',
            'evaluation' => 'required|array'
        ]);

        $newPlan = $this->adjustmentService->adjustPlan($userId, $request->input('study_plan_id'), $request->input('evaluation'));
        $newPlan = $this->attachGrades($newPlan, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Study plan adjusted successfully',
            'data' => $newPlan
        ]);
    }

    public function moveSubject(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'study_plan_id' => 'required|exists:study_plans,id',
            'subject_id' => 'required|exists:subjects,id',
            'target_semester_index' => 'required|integer|min:1'
        ]);

        $plan = StudyPlan::with(['semesters.subjects.subject.prerequisites', 'semesters.subjects.subject.corequisites'])->where('id', $request->input('study_plan_id'))->where('user_id', $userId)->first();
        if (!$plan) {
            return response()->json(['error' => 'Study plan not found'], 404);
        }

        $targetSemesterIndex = $request->input('target_semester_index');
        $subjectId = $request->input('subject_id');

        // Tìm kiếm môn học trong plan
        $sourcePlanSubject = null;
        $sourceSemesterIndex = null;
        $allPlanSubjects = [];
        $targetSemesterId = null;

        foreach ($plan->semesters as $sem) {
            if ($sem->semester_index == $targetSemesterIndex) {
                $targetSemesterId = $sem->id;
            }
            foreach ($sem->subjects as $ss) {
                $allPlanSubjects[$ss->subject_id] = $sem->semester_index;
                if ($ss->subject_id == $subjectId) {
                    $sourcePlanSubject = $ss;
                    $sourceSemesterIndex = $sem->semester_index;
                }
            }
        }

        if (!$sourcePlanSubject) {
            return response()->json(['error' => 'Subject not found in plan'], 404);
        }

        if ($sourcePlanSubject->is_completed) {
            return response()->json(['error' => 'Không thể di chuyển môn đã hoàn thành'], 400);
        }

        if (!$targetSemesterId) {
            // Nếu học kỳ đích chưa có, tạo mới
            $targetSemester = \App\Models\StudyPlanSemester::create([
                'study_plan_id' => $plan->id,
                'semester_index' => $targetSemesterIndex,
                'expected_credits' => 0
            ]);
            $targetSemesterId = $targetSemester->id;
        }

        // Validate Prerequisites
        $subject = $sourcePlanSubject->subject;
        foreach ($subject->prerequisites as $prereq) {
            if (isset($allPlanSubjects[$prereq->id])) {
                $prereqSemIndex = $allPlanSubjects[$prereq->id];
                if ($targetSemesterIndex <= $prereqSemIndex) {
                    return response()->json([
                        'error' => "Môn tiên quyết \"{$prereq->name}\" đang ở Học kỳ {$prereqSemIndex}. Không thể học môn này ở Học kỳ {$targetSemesterIndex}."
                    ], 400);
                }
            } else {
                // Môn tiên quyết không có trong plan (có thể đã qua hoặc chưa thêm)
                // Ta kiểm tra UserGrade xem đã đậu chưa
                $hasPassed = UserGrade::where('user_id', $userId)
                    ->where('subject_id', $prereq->id)
                    ->where('grade', '>', 5)
                    ->exists();
                if (!$hasPassed) {
                    return response()->json([
                        'error' => "Chưa hoàn thành môn tiên quyết: {$prereq->name}"
                    ], 400);
                }
            }
        }

        // Gather corequisites to move together
        $subjectsToMove = [$sourcePlanSubject];
        foreach ($subject->corequisites as $coreq) {
            foreach ($plan->semesters as $sem) {
                foreach ($sem->subjects as $ss) {
                    if ($ss->subject_id == $coreq->id && !$ss->is_completed) {
                        $subjectsToMove[] = $ss;
                    }
                }
            }
        }

        // Thực hiện di chuyển
        foreach ($subjectsToMove as $ss) {
            $ss->update(['study_plan_semester_id' => $targetSemesterId]);
        }

        // Cập nhật lại expected_credits
        $plan->load('semesters.subjects.subject');
        foreach ($plan->semesters as $sem) {
            $credits = $sem->subjects->sum(function ($ss) {
                return $ss->subject ? $ss->subject->credits : 0;
            });
            $sem->update(['expected_credits' => $credits]);
        }

        $plan = $this->attachGrades($plan, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Di chuyển môn học thành công.',
            'data' => $plan
        ]);
    }

    public function applySuggestions(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'study_plan_id' => 'required|exists:study_plans,id',
            'subject_ids' => 'required|array',
            'target_semester_index' => 'required|integer|min:1'
        ]);

        $plan = StudyPlan::with(['semesters.subjects'])->where('id', $request->input('study_plan_id'))->where('user_id', $userId)->first();
        if (!$plan) {
            return response()->json(['error' => 'Study plan not found'], 404);
        }

        $targetSemesterIndex = $request->input('target_semester_index');
        $subjectIds = $request->input('subject_ids');

        // Khởi tạo service để gọi hàm applySuggestionsAndRedistribute
        $studyPlanService = app(\App\Services\StudyPlanService::class);
        $plan = $studyPlanService->applySuggestionsAndRedistribute($plan->id, $subjectIds, $targetSemesterIndex);

        $plan = $this->attachGrades($plan, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Áp dụng gợi ý và rải môn thành công.',
            'data' => $plan
        ]);
    }
}
