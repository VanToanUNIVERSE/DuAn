<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StudyPlanService;
use App\Services\AcademicEvaluationService;
use App\Services\PlanAdjustmentService;
use App\Models\StudyPlan;
use App\Models\StudyPlanSemester;
use App\Models\StudyPlanSubject;
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

        // generatePlan() đã tự động: deactivate kế hoạch cũ + set is_active=true + is_saved=true
        $plan = $this->studyPlanService->generatePlan($userId, $name, $mode);

        $plan = $this->attachGrades($plan, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Study plan generated successfully',
            'data'    => $plan
        ]);
    }

    public function index(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();

        // Ư u tiên lấy kế hoạch is_active=true trước, fallback sang mới nhất
        $plan = StudyPlan::where('user_id', $userId)
            ->where('is_saved', true)
            ->where('is_active', true)
            ->first();

        if (!$plan) {
            $plan = StudyPlan::where('user_id', $userId)
                ->where('is_saved', true)
                ->orderBy('updated_at', 'desc')
                ->first();
        }

        $plans = $plan ? collect([$plan]) : collect();

        foreach ($plans as $p) {
            $this->attachGrades($p, $userId);
        }

        return response()->json([
            'success' => true,
            'data'    => $plans
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $userId = Auth::id();
        $plan = StudyPlan::where('id', $id)->where('user_id', $userId)->first();
        if (!$plan) {
            return response()->json(['success' => false, 'error' => 'Không tìm thấy kế hoạch'], 404);
        }
        
        $plan->delete(); // Soft delete

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa kế hoạch'
        ]);
    }

    public function getSavedPlans(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        $plans = StudyPlan::where('user_id', $userId)->where('is_saved', true)->orderBy('updated_at', 'desc')->get();
        // Only return basic info to list them
        return response()->json(['success' => true, 'data' => $plans]);
    }

    public function savePlan($id, Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        $plan = StudyPlan::where('id', $id)->where('user_id', $userId)->first();
        if (!$plan) {
            return response()->json(['success' => false, 'error' => 'Plan not found'], 404);
        }
        
        $plan->is_saved = true;
        if ($request->has('name') && !empty($request->name)) {
            $plan->name = $request->name;
        }
        $plan->save();

        return response()->json(['success' => true, 'message' => 'Lưu kế hoạch thành công']);
    }

    public function loadPlan($id, Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        $plan = StudyPlan::where('id', $id)->where('user_id', $userId)->first();
        if (!$plan) {
            return response()->json(['success' => false, 'error' => 'Plan not found'], 404);
        }
        
        $this->attachGrades($plan, $userId);
        return response()->json(['success' => true, 'data' => $plan]);
    }

    private function attachGrades($plan, $userId)
    {
        $plan->loadMissing('semesters.subjects.subject.relatedRelations', 'semesters.subjects.subject.prerequisites');
        $userGrades = UserGrade::where('user_id', $userId)->pluck('grade', 'subject_id')->toArray();

        // Thu thập các subject_id đang có retake row trong plan
        $subjectsWithRetake = [];
        foreach ($plan->semesters as $semester) {
            foreach ($semester->subjects as $ss) {
                if ($ss->is_retake) {
                    $subjectsWithRetake[$ss->subject_id] = true;
                }
            }
        }

        foreach ($plan->semesters as $semester) {
            foreach ($semester->subjects as $ss) {
                if ($ss->subject) {
                    // ── Mỗi row có điểm riêng (subject_grade) ──────────────────────────
                    $ss->grade         = $ss->subject_grade;  // đọc từ column mới
                    $ss->is_retake     = (bool) $ss->is_retake;
                    $ss->original_grade= $ss->original_grade;
                    $ss->subject_grade = $ss->subject_grade;

                    // Môn gốc đã có retake → readonly (không đồng bộ)
                    $ss->is_frozen = (!$ss->is_retake && isset($subjectsWithRetake[$ss->subject_id]));

                    $hasGradeForDisplay = $ss->grade !== null;
                    $ss->is_completed  = $hasGradeForDisplay && $ss->grade > 5.0;

                    $dependentCount = $ss->subject->relatedRelations->where('type', 'prerequisite')->count();
                    $ss->is_highly_recommended = $dependentCount >= 2
                        || in_array($ss->subject->requirement_type, ['completed_basic', 'completed_major']);

                    $prereqDetails    = [];
                    $passedSubjectIds = array_keys(array_filter($userGrades, fn($g) => $g > 5.0));
                    foreach ($ss->subject->prerequisites as $prereq) {
                        $prereqDetails[] = [
                            'id'        => $prereq->id,
                            'name'      => $prereq->name,
                            'is_passed' => in_array($prereq->id, $passedSubjectIds),
                        ];
                    }

                    $reqType = $ss->subject->requirement_type;
                    if ($reqType && $reqType !== 'none') {
                        $groupMap = [
                            'completed_basic'       => [1, 2, 3],
                            'completed_major'       => [4, 5],
                            'completed_specialized' => [6, 7],
                        ];
                        $allSubjects = \App\Models\Subject::all();
                        $implicit = isset($groupMap[$reqType])
                            ? $allSubjects->whereIn('program_group_id', $groupMap[$reqType])
                            : ($reqType === 'completed_all' ? $allSubjects->where('id', '!=', $ss->subject->id) : collect());

                        foreach ($implicit as $impSub) {
                            if (collect($prereqDetails)->contains('id', $impSub->id)) continue;
                            $prereqDetails[] = [
                                'id'        => $impSub->id,
                                'name'      => $impSub->name,
                                'is_passed' => in_array($impSub->id, $passedSubjectIds),
                            ];
                        }
                    }
                    $ss->subject->prerequisites_info = $prereqDetails;
                }
            }
        }
        return $plan;
    }

    /**
     * Tính và cập nhật UserGrade cho một subject trong plan.
     * UserGrade = max(original.subject_grade, retake.subject_grade)
     */
    private function syncUserGrade(int $userId, int $subjectId, $studyPlan): void
    {
        $origGrade   = null;
        $retakeGrade = null;

        foreach ($studyPlan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                if ($ss->subject_id == $subjectId) {
                    if (!$ss->is_retake && $ss->subject_grade !== null) {
                        $origGrade = $ss->subject_grade;
                    } elseif ($ss->is_retake && $ss->subject_grade !== null) {
                        $retakeGrade = $ss->subject_grade;
                    }
                }
            }
        }

        if ($origGrade === null && $retakeGrade === null) {
            UserGrade::where('user_id', $userId)->where('subject_id', $subjectId)->delete();
            return;
        }

        $bestGrade  = max(array_filter([$origGrade, $retakeGrade], fn($v) => $v !== null));
        $bestStatus = $bestGrade >= 5.0 ? 'passed' : 'failed';

        UserGrade::updateOrCreate(
            ['user_id' => $userId, 'subject_id' => $subjectId],
            ['grade' => $bestGrade, 'status' => $bestStatus]
        );
    }


    public function updateGrade(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized or missing user_id'], 401);
        }

        $request->validate([
            'subject_id'      => 'required|exists:subjects,id',
            'study_plan_id'   => 'required|exists:study_plans,id',
            'grade'           => 'nullable|numeric|min:0|max:10',
            'status'          => 'nullable|string|in:passed,failed',
            'plan_subject_id' => 'nullable|integer', // ID của StudyPlanSubject row cụ thể
        ]);

        $subjectId     = $request->input('subject_id');
        $grade         = $request->input('grade');  // null = xóa điểm
        $planSubjectId = $request->input('plan_subject_id');

        // Tải plan với toàn bộ subjects
        $studyPlan = StudyPlan::with('semesters.subjects')->find($request->input('study_plan_id'));
        if (!$studyPlan || $studyPlan->user_id != $userId) {
            return response()->json(['error' => 'Study plan not found'], 404);
        }

        // Tìm row cụ thể cần cập nhật
        $targetRow = null;
        $fromSemesterIndex = null;
        if ($planSubjectId) {
            foreach ($studyPlan->semesters as $sem) {
                foreach ($sem->subjects as $ss) {
                    if ($ss->id == $planSubjectId) {
                        $targetRow = $ss;
                        $fromSemesterIndex = $sem->semester_index;
                        break 2;
                    }
                }
            }
        }

        // Nếu không tìm thấy row cụ thể, fallback: tìm row đầu tiên có subject_id (không phải retake)
        if (!$targetRow) {
            foreach ($studyPlan->semesters as $sem) {
                foreach ($sem->subjects as $ss) {
                    if ($ss->subject_id == $subjectId && !$ss->is_retake) {
                        $targetRow = $ss;
                        $fromSemesterIndex = $sem->semester_index;
                        break 2;
                    }
                }
            }
        }

        if (!$targetRow) {
            return response()->json(['error' => 'Subject not found in plan'], 404);
        }

        // ── Cập nhật subject_grade cho row này ────────────────────────────────
        $isCompleted = ($grade !== null && $grade >= 5.0);
        $targetRow->update([
            'subject_grade' => $grade,
            'is_completed'  => $isCompleted,
        ]);

        // Reload để có dữ liệu mới
        $studyPlan->load('semesters.subjects');

        // ── Logic AUTO-CREATE / AUTO-DELETE retake ──────────────────────────
        if (!$targetRow->is_retake) {
            // Đây là môn GỐC
            if ($grade !== null && $grade < 5.0) {
                // Môn rớt → tự tạo retake ở kỳ tiếp theo (nếu chưa có)
                $hasRetake = false;
                foreach ($studyPlan->semesters as $sem) {
                    foreach ($sem->subjects as $ss) {
                        if ($ss->subject_id == $subjectId && $ss->is_retake) {
                            $hasRetake = true;
                            break 2;
                        }
                    }
                }

                if (!$hasRetake) {
                    // Tìm kỳ tiếp theo
                    $nextSem = $studyPlan->semesters
                        ->where('semester_index', '>', $fromSemesterIndex)
                        ->sortBy('semester_index')
                        ->first();

                    if (!$nextSem) {
                        // Tạo kỳ mới nếu cần
                        $maxIndex = $studyPlan->semesters->max('semester_index') ?? $fromSemesterIndex;
                        $nextSem = StudyPlanSemester::create([
                            'study_plan_id'    => $studyPlan->id,
                            'semester_index'   => $maxIndex + 1,
                            'expected_credits' => 0,
                        ]);
                    }

                    StudyPlanSubject::create([
                        'study_plan_semester_id' => $nextSem->id,
                        'subject_id'             => $subjectId,
                        'is_completed'           => false,
                        'is_retake'              => true,
                        'original_attempt_sem'   => $fromSemesterIndex,
                        'original_grade'         => $grade,
                        'subject_grade'          => null,
                    ]);

                    // Reload lại sau khi tạo retake
                    $studyPlan->load('semesters.subjects');
                }
            } elseif ($grade === null || $grade >= 5.0) {
                // Môn pass hoặc xóa điểm → tự xóa retake (nếu có)
                foreach ($studyPlan->semesters as $sem) {
                    foreach ($sem->subjects as $ss) {
                        if ($ss->subject_id == $subjectId && $ss->is_retake) {
                            $ss->delete();
                        }
                    }
                }
                $studyPlan->load('semesters.subjects');
            }
        }

        // ── Sync UserGrade = max(orig.subject_grade, retake.subject_grade) ───────
        $this->syncUserGrade($userId, $subjectId, $studyPlan);

        // ── Trả về plan đã cập nhật + evaluation ─────────────────────────
        $studyPlan->load('semesters.subjects');
        $updatedPlan = $this->attachGrades($studyPlan, $userId);

        $currentTargetSemesters = $studyPlan->target_semester_count ?? 8;
        $currentSem = 1;
        $gradedSubjectIds = UserGrade::where('user_id', $userId)->pluck('subject_id')->toArray();
        foreach ($studyPlan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                if (in_array($ss->subject_id, $gradedSubjectIds) && !$ss->is_retake) {
                    if ($sem->semester_index >= $currentSem) {
                        $currentSem = $sem->semester_index + 1;
                    }
                }
            }
        }

        $evaluation = $this->evaluationService->evaluate(
            $userId, $studyPlan->mode ?? 'normal', $currentTargetSemesters, $currentSem
        );

        return response()->json([
            'success'    => true,
            'evaluation' => $evaluation,
            'data'       => $updatedPlan,  // trả về plan để FE re-render
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

    /**
     * Thêm môn học lại (retake) vào học kỳ tiếp theo trong kế hoạch.
     * Môn gốc ở kỳ cũ giữ nguyên — chỉ thêm bản copy với is_retake=true.
     *
     * POST /api/v1/study-plans/add-retake
     */
    public function addRetake(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'study_plan_id'   => 'required|exists:study_plans,id',
            'subject_id'      => 'required|integer',
            'from_semester'   => 'required|integer|min:1',
            'original_grade'  => 'nullable|numeric|min:0|max:10',
        ]);

        $plan = StudyPlan::with(['semesters.subjects'])
            ->where('id', $request->study_plan_id)
            ->where('user_id', $userId)
            ->first();

        if (!$plan) {
            return response()->json(['error' => 'Study plan not found'], 404);
        }

        $fromSem    = (int) $request->from_semester;
        $subjectId  = (int) $request->subject_id;
        $origGrade  = $request->original_grade;

        // Tìm học kỳ tiếp theo (from_semester + 1 trở đi) có trong plan
        $targetSem = $plan->semesters
            ->where('semester_index', '>', $fromSem)
            ->sortBy('semester_index')
            ->first();

        if (!$targetSem) {
            // Nếu không có kỳ sau thì tạo mới
            $maxIndex = $plan->semesters->max('semester_index') ?? $fromSem;
            $targetSem = StudyPlanSemester::create([
                'study_plan_id'  => $plan->id,
                'semester_index' => $maxIndex + 1,
            ]);
        }

        // Tránh thêm trùng nếu đã có retake cho môn này ở kỳ đó
        $existing = StudyPlanSubject::where('study_plan_semester_id', $targetSem->id)
            ->where('subject_id', $subjectId)
            ->where('is_retake', true)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Môn này đã được thêm vào kế hoạch học lại ở kỳ ' . $targetSem->semester_index,
            ], 422);
        }

        StudyPlanSubject::create([
            'study_plan_semester_id' => $targetSem->id,
            'subject_id'             => $subjectId,
            'is_completed'           => false,
            'is_retake'              => true,
            'original_attempt_sem'   => $fromSem,
            'original_grade'         => $origGrade,
        ]);

        // Reload plan với đầy đủ thông tin
        $plan->load('semesters.subjects.subject');
        $plan = $this->attachGrades($plan, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm môn học lại vào học kỳ ' . $targetSem->semester_index . '.',
            'data'    => $plan,
            'retake_semester' => $targetSem->semester_index,
        ]);
    }

    public function applySuggestions(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'study_plan_id'        => 'required|exists:study_plans,id',
            'subject_ids'          => 'required|array',
            'target_semester_index'=> 'required|integer|min:1',
        ]);

        $plan = StudyPlan::with(['semesters.subjects'])
            ->where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)
            ->first();

        if (!$plan) {
            return response()->json(['error' => 'Study plan not found'], 404);
        }

        $targetSemesterIndex = $request->input('target_semester_index');
        $subjectIds          = $request->input('subject_ids');

        // Lấy các môn có grade = failed để nhận diện retake
        $failedGrades = UserGrade::where('user_id', $userId)
            ->whereIn('subject_id', $subjectIds)
            ->where('status', 'failed')
            ->get()
            ->keyBy('subject_id');

        // Áp dụng gợi ý (thêm vào plan + rải lại)
        $studyPlanService = app(\App\Services\StudyPlanService::class);
        $plan = $studyPlanService->applySuggestionsAndRedistribute($plan->id, $subjectIds, $targetSemesterIndex);

        // ── Nhận diện môn rớt để đánh dấu is_retake và gán original_grade ────
        if ($failedGrades->isNotEmpty()) {

            // Tìm kỳ gốc của từng môn rớt trong plan
            $plan->load('semesters.subjects');

            // Map: subject_id => semester_index của lần học gốc
            $originalSemMap = [];
            foreach ($plan->semesters as $sem) {
                foreach ($sem->subjects as $ss) {
                    if (isset($failedGrades[$ss->subject_id]) && !$ss->is_retake) {
                        // Kỳ thấp hơn target → đây là kỳ gốc
                        if ($sem->semester_index < $targetSemesterIndex) {
                            $originalSemMap[$ss->subject_id] = $sem->semester_index;
                        }
                    }
                }
            }

            $targetSem = $plan->semesters
                ->where('semester_index', $targetSemesterIndex)
                ->first();

            if ($targetSem) {
                foreach ($failedGrades as $subjectId => $failedGrade) {
                    StudyPlanSubject::where('study_plan_semester_id', $targetSem->id)
                        ->where('subject_id', $subjectId)
                        ->where('is_retake', false)
                        ->update([
                            'is_retake'            => true,
                            'original_grade'       => $failedGrade->grade,
                            'original_attempt_sem' => $originalSemMap[$subjectId] ?? null,
                            'subject_grade'        => null,  // điểm retake trống, chờ nhập
                        ]);
                }
            }
        }

        $plan->load('semesters.subjects');
        $plan = $this->attachGrades($plan, $userId);


        return response()->json([
            'success' => true,
            'message' => 'Áp dụng gợi ý và rải môn thành công.',
            'data'    => $plan,
        ]);
    }

    /**
     * Lấy kế hoạch học tập đang hoạt động (is_active = true) của sinh viên.
     * Đảm bảo mỗi sinh viên chỉ có 1 kế hoạch active tại mọi thời điểm.
     *
     * GET /api/v1/study-plans/active
     */
    public function getActivePlan(Request $request)
    {
        $userId = $request->input('user_id') ?? Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized or missing user_id'], 401);
        }

        $plan = StudyPlan::where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$plan) {
            // Fallback: không có active plan → trả về null (FE sẽ hiển thị wizard tạo mới)
            return response()->json(['success' => true, 'data' => null]);
        }

        $this->attachGrades($plan, $userId);

        return response()->json(['success' => true, 'data' => $plan]);
    }

    /**
     * Đổi chế độ học tập cho một kế hoạch hiện có (không tạo mới)
     * và tái phân bổ môn học từ học kỳ hiện tại trở đi.
     *
     * POST /api/v1/study-plans/{id}/change-mode
     */
    public function changeMode(Request $request, $id)
    {
        $request->validate([
            'mode' => 'required|in:slow,normal,fast'
        ]);

        $userId = Auth::id();
        $plan = StudyPlan::where('id', $id)->where('user_id', $userId)->first();

        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Study plan not found'], 404);
        }

        // Cập nhật mode
        $plan->update(['mode' => $request->mode]);

        // Cập nhật target_semester_count tương ứng với mode mới nếu muốn
        $currentSem = 1; // Mặc định nếu chưa có grade
        $progressService = new \App\Services\ProgressService();
        $progress = $progressService->evaluateProgress($userId);
        if ($progress && isset($progress['completed_semesters'])) {
             $currentSem = $progress['completed_semesters'] + 1;
        }

        // Gọi service tái phân bổ môn học
        $plan = $this->studyPlanService->applySuggestionsAndRedistribute($plan->id, [], $currentSem);

        // Nạp lại dữ liệu
        $plan = $this->attachGrades($plan, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Đã thay đổi chế độ học tập và rải lại môn thành công.',
            'data'    => $plan
        ]);
    }
}

