<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CurriculumSubject;
use App\Models\StudyPlan;
use App\Models\StudyPlanSemester;
use App\Models\StudyPlanSubject;
use App\Models\Subject;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\UserGrade;
use App\Services\AcademicEvaluationService;
use App\Services\ProgressService;
use App\Services\StudyPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudyPlanController extends Controller
{
    public function __construct(
        protected StudyPlanService        $planService,
        protected AcademicEvaluationService $evaluationService,
        protected ProgressService         $progressService,
    ) {}

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/study-plans/generate
    // ──────────────────────────────────────────────────────────────────────
    public function generate(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'name'             => 'required|string|max:120',
            'target_semesters' => 'nullable|integer|in:6,7,8,9,10',
        ]);

        $result = $this->planService->generatePlan(
            $userId,
            $request->input('name'),
            (int)$request->input('target_semesters', 8)
        );

        $plan = $this->attachGrades($result['plan'], $userId);

        return response()->json([
            'success'                => true,
            'data'                   => $plan,
            'tc_per_sem'             => $result['tc_per_sem'],
            'target_semesters'       => $result['target_semesters'],
            'over_semesters'         => $result['over_semesters'] ?? false,
            'over_semesters_count'   => $result['over_semesters_count'] ?? 0,
            'over_semesters_notice'  => $result['over_semesters_notice'] ?? null,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /api/v1/study-plans
    // ──────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $userId = Auth::id();

        $plan = StudyPlan::where('user_id', $userId)
            ->where('is_saved', true)
            ->where('is_active', true)
            ->first()
            ?? StudyPlan::where('user_id', $userId)
                ->where('is_saved', true)
                ->orderByDesc('updated_at')
                ->first();

        $plans = $plan ? collect([$this->attachGrades($plan, $userId)]) : collect();

        return response()->json(['success' => true, 'data' => $plans]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /api/v1/study-plans/active
    // ──────────────────────────────────────────────────────────────────────
    public function getActivePlan(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        $plan = StudyPlan::where('user_id', $userId)->where('is_active', true)->first();

        if ($plan) {
            // Tự động dọn retake trùng mỗi khi load plan (sửa data cũ nếu có)
            $this->planService->deduplicateRetakes($plan);
        }

        return response()->json([
            'success' => true,
            'data'    => $plan ? $this->attachGrades($plan, $userId) : null,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /api/v1/study-plans/saved
    // ──────────────────────────────────────────────────────────────────────
    public function getSavedPlans()
    {
        $plans = StudyPlan::where('user_id', Auth::id())
            ->where('is_saved', true)
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'mode', 'target_semester_count', 'is_active', 'updated_at']);

        return response()->json(['success' => true, 'data' => $plans]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /api/v1/study-plans/{id}/load
    // ──────────────────────────────────────────────────────────────────────
    public function loadPlan($id)
    {
        $userId = Auth::id();
        $plan   = StudyPlan::where('id', $id)->where('user_id', $userId)->firstOrFail();

        return response()->json(['success' => true, 'data' => $this->attachGrades($plan, $userId)]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/study-plans/{id}/save
    // ──────────────────────────────────────────────────────────────────────
    public function savePlan($id, Request $request)
    {
        $plan = StudyPlan::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $plan->update([
            'is_saved' => true,
            'name'     => $request->filled('name') ? $request->input('name') : $plan->name,
        ]);

        return response()->json(['success' => true, 'message' => 'Đã lưu kế hoạch.']);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DELETE /api/v1/study-plans/{id}
    // ──────────────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $plan = StudyPlan::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $plan->delete();

        return response()->json(['success' => true, 'message' => 'Đã xóa kế hoạch.']);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/study-plans/{id}/adjust-target
    // Thay thế change-mode: điều chỉnh mục tiêu tốt nghiệp và/hoặc TC/kỳ
    // ──────────────────────────────────────────────────────────────────────
    public function adjustTarget(Request $request, $id)
    {
        $request->validate([
            'target_semesters' => 'nullable|integer|in:6,7,8,9,10',
            'tc_per_sem'       => 'nullable|integer|min:12|max:22',
        ]);
        $userId = Auth::id();

        $plan = StudyPlan::where('id', $id)->where('user_id', $userId)->firstOrFail();

        $newTarget = (int)$request->input('target_semesters', $plan->target_semesters ?? 8);
        $newTc     = (int)$request->input('tc_per_sem', $plan->tc_per_sem ?? 18);
        $newMode   = $newTc >= 20 ? 'fast' : ($newTc <= 14 ? 'slow' : 'normal');

        $plan->update([
            'target_semesters' => $newTarget,
            'tc_per_sem'       => $newTc,
            'mode'             => $newMode,
        ]);

        $plan->load('semesters.subjects');
        $currentSem = $this->detectCurrentSemester($plan, $userId);
        $updated    = $this->planService->redistributeFrom($plan->fresh(), $currentSem);

        return response()->json([
            'success'          => true,
            'message'          => 'Đã cập nhật mục tiêu và rải lại lộ trình.',
            'data'             => $this->attachGrades($updated, $userId),
            'target_semesters' => $newTarget,
            'tc_per_sem'       => $newTc,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // GET /api/v1/study-plans/{id}/advisory
    // ──────────────────────────────────────────────────────────────────────
    public function advisory($id)
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        $plan = StudyPlan::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $data = $this->planService->computeAdvisory($plan, $userId);

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/study-plans/{id}/apply-advisory
    // ──────────────────────────────────────────────────────────────────────
    public function applyAdvisory(Request $request, $id)
    {
        $request->validate([
            'tc_per_sem'   => 'required|integer|min:12|max:22',
            'redistribute' => 'required|boolean',
        ]);
        $userId = Auth::id();
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        $plan    = StudyPlan::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $updated = $this->planService->applyAdvisory(
            $plan,
            $userId,
            (int)$request->input('tc_per_sem'),
            (bool)$request->input('redistribute')
        );

        return response()->json([
            'success' => true,
            'message' => $request->input('redistribute')
                ? 'Đã cập nhật TC/kỳ và rải lại lộ trình.'
                : 'Đã cập nhật TC/kỳ. Bạn có thể tự điều chỉnh thứ tự môn học.',
            'data'    => $this->attachGrades($updated, $userId),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/study-plans/update-grade
    // ──────────────────────────────────────────────────────────────────────
    public function updateGrade(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'study_plan_id'   => 'required|exists:study_plans,id',
            'subject_id'      => 'required|exists:subjects,id',
            'grade'           => 'nullable|numeric|min:0|max:10',
            'plan_subject_id' => 'nullable|integer',
        ]);

        $subjectId     = (int)$request->input('subject_id');
        $grade         = $request->input('grade'); // null = xóa điểm
        $planSubjectId = $request->input('plan_subject_id');

        $studyPlan = StudyPlan::with('semesters.subjects')
            ->where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)
            ->firstOrFail();

        // Tìm row cần cập nhật
        $targetRow         = null;
        $fromSemesterIndex = null;

        foreach ($studyPlan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                if ($planSubjectId && $ss->id == $planSubjectId) {
                    $targetRow = $ss; $fromSemesterIndex = $sem->semester_index; break 2;
                }
                if (!$planSubjectId && $ss->subject_id == $subjectId && !$ss->is_retake) {
                    $targetRow = $ss; $fromSemesterIndex = $sem->semester_index;
                }
            }
        }

        if (!$targetRow) {
            return response()->json(['error' => 'Không tìm thấy môn trong kế hoạch.'], 404);
        }

        $targetRow->update([
            'subject_grade' => $grade,
            'is_completed'  => $grade !== null && $grade >= 5.0,
        ]);

        $studyPlan->load('semesters.subjects');

        // Chỉ đồng bộ UserGrade, không tự động tạo/xóa môn học lại
        $this->syncUserGrade($userId, $subjectId, $studyPlan);

        $updatedPlan = $this->attachGrades($studyPlan->load('semesters.subjects'), $userId);
        $currentSem  = $this->detectCurrentSemester($updatedPlan, $userId);
        $evaluation  = $this->evaluationService->evaluate(
            $userId,
            $studyPlan->mode ?? 'normal',
            $studyPlan->target_semester_count ?? 8,
            $currentSem
        );

        return response()->json(['success' => true, 'data' => $updatedPlan, 'evaluation' => $evaluation]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/study-plans/move-subject  (drag & drop)
    // ──────────────────────────────────────────────────────────────────────
    public function moveSubject(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'study_plan_id'         => 'required|exists:study_plans,id',
            'subject_id'            => 'required|exists:subjects,id',
            'target_semester_index' => 'required|integer|min:1',
        ]);

        $plan = StudyPlan::with(['semesters.subjects.subject.prerequisites', 'semesters.subjects.subject.corequisites'])
            ->where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)
            ->firstOrFail();

        $subjectId   = (int)$request->input('subject_id');
        $targetSemIdx = (int)$request->input('target_semester_index');

        // Tìm môn cần di chuyển và lập map vị trí hiện tại
        $sourcePlanSubject = null;
        $subjectSemMap     = []; // [subject_id => semester_index]
        $targetSemId       = null;

        foreach ($plan->semesters as $sem) {
            if ($sem->semester_index === $targetSemIdx) $targetSemId = $sem->id;
            foreach ($sem->subjects as $ss) {
                $subjectSemMap[$ss->subject_id] = $sem->semester_index;
                if ($ss->subject_id === $subjectId && !$sourcePlanSubject) {
                    $sourcePlanSubject = $ss;
                }
            }
        }

        if (!$sourcePlanSubject) {
            return response()->json(['error' => 'Môn không tồn tại trong kế hoạch.'], 404);
        }
        if ($sourcePlanSubject->is_completed) {
            return response()->json(['error' => 'Không thể di chuyển môn đã hoàn thành.'], 422);
        }

        // Validate tiên quyết: đảm bảo tiên quyết ở kỳ nhỏ hơn target
        $subject = $sourcePlanSubject->subject;
        foreach ($subject->prerequisites ?? [] as $prereq) {
            $prereqSem = $subjectSemMap[$prereq->id] ?? null;

            // Chưa có trong plan → kiểm tra UserGrade
            if ($prereqSem === null) {
                $passed = UserGrade::where('user_id', $userId)
                    ->where('subject_id', $prereq->id)
                    ->where('grade', '>=', 5.0)->exists();
                if (!$passed) {
                    return response()->json([
                        'error' => "Chưa hoàn thành tiên quyết: «{$prereq->name}»."
                    ], 422);
                }
            } elseif ($prereqSem >= $targetSemIdx) {
                return response()->json([
                    'error' => "Tiên quyết «{$prereq->name}» đang ở Học kỳ {$prereqSem} — không thể kéo môn này lên Học kỳ {$targetSemIdx}."
                ], 422);
            }
        }

        // Tạo kỳ đích nếu chưa có
        if (!$targetSemId) {
            $targetSem   = StudyPlanSemester::create([
                'study_plan_id'    => $plan->id,
                'semester_index'   => $targetSemIdx,
                'expected_credits' => 0,
            ]);
            $targetSemId = $targetSem->id;
        }

        $sourcePlanSubject->update(['study_plan_semester_id' => $targetSemId]);

        // Kéo corequisites theo cùng học kỳ (BFS để xử lý chain A↔B↔C)
        $planSubjectMap  = []; // [subject_id => StudyPlanSubject]
        $plan->load('semesters.subjects.subject.corequisites');
        foreach ($plan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                $planSubjectMap[$ss->subject_id] = $ss;
            }
        }

        $movedCoreqNames = [];
        $coQueue  = [$subjectId];
        $coPulled = [$subjectId => true];

        while (!empty($coQueue)) {
            $checkId = array_shift($coQueue);
            $checkSs = $planSubjectMap[$checkId] ?? null;
            if (!$checkSs) continue;

            foreach ($checkSs->subject->corequisites ?? [] as $coreq) {
                if (isset($coPulled[$coreq->id])) continue;
                $coPulled[$coreq->id] = true;

                $coreqSs = $planSubjectMap[$coreq->id] ?? null;
                if (!$coreqSs || $coreqSs->is_completed) continue; // bỏ qua nếu không có trong plan hoặc đã pass

                $coreqSs->update(['study_plan_semester_id' => $targetSemId]);
                $movedCoreqNames[] = $coreq->name;
                $coQueue[]         = $coreq->id;
            }
        }

        // Cập nhật expected_credits cho tất cả kỳ
        $plan->load('semesters.subjects.subject');
        foreach ($plan->semesters as $sem) {
            $sem->update(['expected_credits' => $sem->subjects->sum(fn($ss) => $ss->subject?->credits ?? 0)]);
        }

        $message = 'Đã di chuyển môn học.';
        if (!empty($movedCoreqNames)) {
            $message .= ' Môn song hành đi theo: ' . implode(', ', $movedCoreqNames) . '.';
        }

        return response()->json([
            'success'          => true,
            'message'          => $message,
            'coreqs_moved'     => $movedCoreqNames,
            'data'             => $this->attachGrades($plan->load('semesters.subjects.subject'), $userId),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/study-plans/apply-suggestions
    // ──────────────────────────────────────────────────────────────────────
    public function applySuggestions(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'study_plan_id'         => 'required|exists:study_plans,id',
            'subject_ids'           => 'required|array|min:1',
            'subject_ids.*'         => 'integer|exists:subjects,id',
            'target_semester_index' => 'required|integer|min:1',
        ]);

        $plan = StudyPlan::where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)
            ->firstOrFail();

        $updated = $this->planService->redistributeFrom(
            $plan,
            (int)$request->input('target_semester_index'),
            $request->input('subject_ids')
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã áp dụng gợi ý và rải lại lộ trình.',
            'data'    => $this->attachGrades($updated, $userId),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/study-plans/{id}/dedup-retakes
    // ──────────────────────────────────────────────────────────────────────
    public function dedupRetakes($id)
    {
        $userId = Auth::id();
        $plan = StudyPlan::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $this->planService->deduplicateRetakes($plan);
        $plan->load('semesters.subjects');
        return response()->json([
            'success' => true,
            'message' => 'Đã dọn sạch môn học trùng lặp.',
            'data'    => $this->attachGrades($plan, $userId),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/study-plans/add-retake
    // ──────────────────────────────────────────────────────────────────────
    public function addRetake(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'study_plan_id'  => 'required|exists:study_plans,id',
            'subject_id'     => 'required|integer|exists:subjects,id',
            'from_semester'  => 'required|integer|min:1',
            'original_grade' => 'nullable|numeric|min:0|max:10',
        ]);

        $plan = StudyPlan::with('semesters.subjects')
            ->where('id', $request->study_plan_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $fromSem   = (int)$request->from_semester;
        $subjectId = (int)$request->subject_id;

        // Tìm kỳ tiếp theo, tạo mới nếu cần
        $targetSem = $plan->semesters->where('semester_index', '>', $fromSem)->sortBy('semester_index')->first()
            ?? StudyPlanSemester::create([
                'study_plan_id'  => $plan->id,
                'semester_index' => ($plan->semesters->max('semester_index') ?? $fromSem) + 1,
            ]);

        // Tránh tạo retake trùng
        $exists = StudyPlanSubject::where('study_plan_semester_id', $targetSem->id)
            ->where('subject_id', $subjectId)->where('is_retake', true)->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Môn này đã có trong kế hoạch học lại.'], 422);
        }

        StudyPlanSubject::create([
            'study_plan_semester_id' => $targetSem->id,
            'subject_id'             => $subjectId,
            'is_completed'           => false,
            'is_retake'              => true,
            'original_attempt_sem'   => $fromSem,
            'original_grade'         => $request->original_grade,
        ]);

        $plan->load('semesters.subjects.subject');
        return response()->json([
            'success'         => true,
            'message'         => "Đã thêm học lại vào Học kỳ {$targetSem->semester_index}.",
            'data'            => $this->attachGrades($plan, $userId),
            'retake_semester' => $targetSem->semester_index,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // POST /api/v1/study-plans/toggle-elective
    // Thêm hoặc xóa môn tự chọn khỏi plan semester (do sinh viên tự chọn)
    // ──────────────────────────────────────────────────────────────────────
    public function toggleElective(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        $request->validate([
            'study_plan_id'  => 'required|exists:study_plans,id',
            'subject_id'     => 'required|integer|exists:subjects,id',
            'semester_index' => 'required|integer|min:1',
            'action'         => 'required|in:add,remove',
        ]);

        $plan = StudyPlan::with('semesters.subjects.subject')
            ->where('id', $request->study_plan_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $subjectId   = (int) $request->subject_id;
        $semesterIdx = (int) $request->semester_index;

        $sem = $plan->semesters->firstWhere('semester_index', $semesterIdx);
        if (!$sem) {
            return response()->json(['error' => 'Không tìm thấy học kỳ.'], 404);
        }

        if ($request->action === 'remove') {
            StudyPlanSubject::where('study_plan_semester_id', $sem->id)
                ->where('subject_id', $subjectId)
                ->delete();
        } else {
            // Kiểm tra môn đã có chưa
            $already = StudyPlanSubject::where('study_plan_semester_id', $sem->id)
                ->where('subject_id', $subjectId)->exists();
            if ($already) {
                return response()->json(['error' => 'Môn này đã có trong học kỳ.'], 422);
            }

            // Kiểm tra giới hạn TC của nhóm tự chọn
            $subject = Subject::findOrFail($subjectId);
            $user    = User::find($userId);
            $frameworkId = null;
            if ($user?->pref_academic_year && $user?->pref_program_type) {
                $program = TrainingProgram::where('academic_year', $user->pref_academic_year)
                    ->where('program_type', $user->pref_program_type)->first();
                $frameworkId = $program?->curriculumFrameworks()->first()?->id;
            }

            $eg = null;
            if ($frameworkId) {
                $eg = \App\Models\ElectiveGroup::where('curriculum_framework_id', $frameworkId)
                    ->whereHas('subjects', fn($q) => $q->where('subjects.id', $subjectId))
                    ->first();
            }

            if ($eg) {
                $groupSubjectIds = $eg->subjects()->pluck('subjects.id')->toArray();
                $currentCr = StudyPlanSubject::where('study_plan_semester_id', $sem->id)
                    ->whereIn('subject_id', $groupSubjectIds)
                    ->join('subjects', 'subjects.id', '=', 'study_plan_subjects.subject_id')
                    ->sum('subjects.credits');
                if ($currentCr + $subject->credits > $eg->required_credits) {
                    return response()->json([
                        'error' => "Nhóm tự chọn này chỉ cần {$eg->required_credits} TC. Bỏ chọn một môn trước khi thêm."
                    ], 422);
                }
            }

            StudyPlanSubject::create([
                'study_plan_semester_id' => $sem->id,
                'subject_id'             => $subjectId,
                'is_completed'           => false,
                'is_retake'              => false,
            ]);
        }

        $plan->load('semesters.subjects.subject');
        return response()->json([
            'success' => true,
            'data'    => $this->attachGrades($plan, $userId),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Gắn thêm thông tin điểm, tiên quyết, trạng thái vào từng StudyPlanSubject.
     * Tránh N+1 bằng cách cache Subject::all() và UserGrades ra ngoài vòng lặp.
     */
    private function attachGrades(StudyPlan $plan, int $userId): StudyPlan
    {
        $plan->loadMissing('semesters.subjects.subject.prerequisites', 'semesters.subjects.subject.corequisites', 'semesters.subjects.subject.relatedRelations');

        $userGrades     = UserGrade::where('user_id', $userId)->pluck('grade', 'subject_id')->toArray();
        $passedSet      = array_filter($userGrades, fn($g) => $g !== null && $g >= 5.0);
        $passedIds      = array_keys($passedSet);

        $allSubjectsMap = Subject::all()->keyBy('id');

        // Load elective group info từ curriculum_subject (1 query cho toàn plan)
        $electiveGroupMap = $this->loadElectiveGroupMap($userId, $plan);
        // Load tất cả members của mỗi elective group (cho group frame ở frontend)
        $allElectiveGroups = $this->loadAllElectiveGroupSubjects($userId);

        foreach ($plan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                if (!$ss->subject) continue;

                $ss->grade        = $ss->subject_grade;
                $ss->is_completed = $ss->grade !== null && $ss->grade >= 5.0;
                $ss->is_failed    = $ss->grade !== null && $ss->grade < 5.0;

                $ss->subject->prerequisites_info = $this->buildPrereqDetails($ss->subject, $passedIds, $allSubjectsMap);

                $dependentCount = $ss->subject->relatedRelations->where('type', 'prerequisite')->count();
                $ss->is_highly_recommended = $dependentCount >= 2
                    || in_array($ss->subject->requirement_type, ['completed_basic', 'completed_major']);

                // Attach elective group info
                $eg = $electiveGroupMap[$ss->subject_id] ?? null;
                $ss->subject->elective_group_id         = $eg?->elective_group_id;
                $ss->subject->elective_group_name       = $eg?->electiveGroup?->name;
                $ss->subject->elective_required_credits = $eg?->electiveGroup?->required_credits;
            }

            // Build elective_groups: nhóm đầy đủ (các môn được chọn + phương án thay thế)
            $semGroupPlanIds = []; // group_id → [subject_ids in plan]
            foreach ($sem->subjects as $ss) {
                $eg = $electiveGroupMap[$ss->subject_id] ?? null;
                if ($eg?->elective_group_id) {
                    $semGroupPlanIds[$eg->elective_group_id][] = $ss->subject_id;
                }
            }

            $semElectiveGroups = [];
            foreach ($semGroupPlanIds as $gid => $planSubjectIds) {
                $groupData = $allElectiveGroups[$gid] ?? null;
                if (!$groupData) continue;

                $options = array_map(fn($m) => [
                    'id'       => $m->id,
                    'name'     => $m->name,
                    'code'     => $m->subject_code ?? '',
                    'credits'  => (int) ($m->credits ?? 0),
                    'selected' => in_array($m->id, $planSubjectIds),
                ], $groupData['subjects']);

                $semElectiveGroups[] = [
                    'id'               => $gid,
                    'name'             => $groupData['name'],
                    'required_credits' => $groupData['required_credits'],
                    'options'          => $options,
                ];
            }

            $sem->setAttribute('elective_groups', $semElectiveGroups);
        }

        return $plan;
    }

    private function loadElectiveGroupMap(int $userId, StudyPlan $plan): array
    {
        $user = User::find($userId);
        if (!$user?->pref_academic_year || !$user?->pref_program_type) return [];

        $program = TrainingProgram::where('academic_year', $user->pref_academic_year)
            ->where('program_type', $user->pref_program_type)
            ->first();
        if (!$program) return [];

        $frameworkId = $program->curriculumFrameworks()->first()?->id;
        if (!$frameworkId) return [];

        $subjectIds = $plan->semesters->flatMap(fn($s) => $s->subjects->pluck('subject_id'))->unique()->toArray();

        return CurriculumSubject::where('curriculum_framework_id', $frameworkId)
            ->whereIn('subject_id', $subjectIds)
            ->whereNotNull('elective_group_id')
            ->with('electiveGroup')
            ->get()
            ->keyBy('subject_id')
            ->all();
    }

    /**
     * Load tất cả subjects của mỗi ElectiveGroup cho framework của user.
     * Trả về: [group_id => ['id', 'name', 'required_credits', 'subjects'[]]]
     */
    private function loadAllElectiveGroupSubjects(int $userId): array
    {
        $user = User::find($userId);
        if (!$user?->pref_academic_year || !$user?->pref_program_type) return [];

        $program = TrainingProgram::where('academic_year', $user->pref_academic_year)
            ->where('program_type', $user->pref_program_type)->first();
        if (!$program) return [];

        $frameworkId = $program->curriculumFrameworks()->first()?->id;
        if (!$frameworkId) return [];

        $groups = [];
        \App\Models\ElectiveGroup::where('curriculum_framework_id', $frameworkId)
            ->with('subjects:id,name,subject_code,credits')
            ->each(function ($eg) use (&$groups) {
                $groups[$eg->id] = [
                    'id'               => $eg->id,
                    'name'             => $eg->name,
                    'required_credits' => $eg->required_credits,
                    'subjects'         => $eg->subjects->all(),
                ];
            });

        return $groups;
    }

    /**
     * Build danh sách tiên quyết (tường minh + nhóm) cho một môn.
     * Dùng $allSubjectsMap đã cache sẵn, không query DB thêm.
     */
    private function buildPrereqDetails(object $subject, array $passedIds, \Illuminate\Support\Collection $allSubjectsMap): array
    {
        $details = [];

        // Tiên quyết tường minh (phải học trước)
        foreach ($subject->prerequisites ?? [] as $prereq) {
            $details[$prereq->id] = [
                'id'        => $prereq->id,
                'name'      => $prereq->name,
                'is_passed' => in_array($prereq->id, $passedIds),
                'type'      => 'explicit',
            ];
        }

        // Song hành (phải học cùng kỳ)
        foreach ($subject->corequisites ?? [] as $coreq) {
            $details['co_' . $coreq->id] = [
                'id'        => $coreq->id,
                'name'      => $coreq->name,
                'is_passed' => in_array($coreq->id, $passedIds),
                'type'      => 'corequisite',
            ];
        }

        // Tiên quyết nhóm
        $req = $subject->requirement_type ?? null;
        if ($req && $req !== 'none') {
            $groupLabel = match ($req) {
                'completed_basic'       => 'Đại cương',
                'completed_major'       => 'Cơ sở ngành',
                'completed_specialized' => 'Chuyên ngành',
                'completed_all'         => 'Toàn bộ',
                default                 => $req,
            };
            $details["_group_{$req}"] = [
                'id'        => null,
                'name'      => "Hoàn thành khối {$groupLabel}",
                'is_passed' => false, // simplified — không check từng môn trong group ở đây
                'type'      => 'group',
            ];
        }

        return array_values($details);
    }

    /**
     * Đồng bộ UserGrade từ điểm trong kế hoạch.
     */
    private function syncUserGrade(int $userId, int $subjectId, StudyPlan $plan): void
    {
        $grade = null;
        foreach ($plan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                if ($ss->subject_id === $subjectId && $ss->subject_grade !== null) {
                    $grade = $ss->subject_grade;
                    break 2;
                }
            }
        }

        if ($grade === null) {
            UserGrade::where('user_id', $userId)->where('subject_id', $subjectId)->delete();
            return;
        }

        UserGrade::updateOrCreate(
            ['user_id' => $userId, 'subject_id' => $subjectId],
            ['grade' => $grade, 'status' => $grade >= 5.0 ? 'pass' : 'fail']
        );
    }

    /**
     * Tìm học kỳ hiện tại = học kỳ đầu tiên còn môn chưa hoàn thành.
     */
    private function detectCurrentSemester(StudyPlan $plan, int $userId): int
    {
        // Ưu tiên SemesterHistory
        $lastHistory = \App\Models\SemesterHistory::where('user_id', $userId)->max('semester_number');
        if ($lastHistory) return (int)$lastHistory + 1;

        // Fallback: học kỳ đầu tiên có môn chưa done
        foreach ($plan->semesters->sortBy('semester_index') as $sem) {
            $hasIncomplete = $sem->subjects->some(fn($ss) => !$ss->is_completed && !$ss->is_retake);
            if ($hasIncomplete) return (int)$sem->semester_index;
        }

        return 1;
    }
}
