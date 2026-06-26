<?php

namespace App\Http\Controllers\Api\StudyPlan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\StudyPlan\Concerns\HandlesStudyPlanDisplay;
use App\Http\Requests\StudyPlan\AddRetakeRequest;
use App\Http\Requests\StudyPlan\ApplySuggestionsRequest;
use App\Http\Requests\StudyPlan\MoveSubjectRequest;
use App\Http\Requests\StudyPlan\ToggleElectiveRequest;
use App\Models\ElectiveGroup;
use App\Models\StudyPlan;
use App\Models\StudyPlanSemester;
use App\Models\StudyPlanSubject;
use App\Models\Subject;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\UserGrade;
use App\Services\StudyPlanService;
use Illuminate\Support\Facades\Auth;

class StudyPlanSubjectController extends Controller
{
    use HandlesStudyPlanDisplay;

    public function __construct(protected StudyPlanService $planService) {}

    // POST /api/v1/study-plans/move-subject
    public function moveSubject(MoveSubjectRequest $request)
    {
        $userId      = Auth::id();
        $subjectId   = (int) $request->input('subject_id');
        $targetSemIdx = (int) $request->input('target_semester_index');

        $plan = StudyPlan::with(['semesters.subjects.subject.prerequisites', 'semesters.subjects.subject.corequisites'])
            ->where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)
            ->firstOrFail();

        $sourcePlanSubject = null;
        $subjectSemMap     = [];
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

        foreach ($sourcePlanSubject->subject->prerequisites ?? [] as $prereq) {
            $prereqSem = $subjectSemMap[$prereq->id] ?? null;
            if ($prereqSem === null) {
                $passed = UserGrade::where('user_id', $userId)
                    ->where('subject_id', $prereq->id)
                    ->where('grade', '>=', 5.0)->exists();
                if (!$passed) {
                    return response()->json(['error' => "Chưa hoàn thành tiên quyết: «{$prereq->name}»."], 422);
                }
            } elseif ($prereqSem >= $targetSemIdx) {
                return response()->json([
                    'error' => "Tiên quyết «{$prereq->name}» đang ở Học kỳ {$prereqSem} — không thể kéo môn này lên Học kỳ {$targetSemIdx}."
                ], 422);
            }
        }

        if (!$targetSemId) {
            $targetSemId = StudyPlanSemester::create([
                'study_plan_id'    => $plan->id,
                'semester_index'   => $targetSemIdx,
                'expected_credits' => 0,
            ])->id;
        }

        $sourcePlanSubject->update(['study_plan_semester_id' => $targetSemId]);

        // Kéo corequisites theo (BFS)
        $plan->load('semesters.subjects.subject.corequisites');
        $planSubjectMap = [];
        foreach ($plan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                $planSubjectMap[$ss->subject_id] = $ss;
            }
        }

        $movedCoreqNames = [];
        $coQueue  = [$subjectId];
        $coPulled = [$subjectId => true];

        while (!empty($coQueue)) {
            $checkSs = $planSubjectMap[array_shift($coQueue)] ?? null;
            if (!$checkSs) continue;

            foreach ($checkSs->subject->corequisites ?? [] as $coreq) {
                if (isset($coPulled[$coreq->id])) continue;
                $coPulled[$coreq->id] = true;
                $coreqSs = $planSubjectMap[$coreq->id] ?? null;
                if (!$coreqSs || $coreqSs->is_completed) continue;

                $coreqSs->update(['study_plan_semester_id' => $targetSemId]);
                $movedCoreqNames[] = $coreq->name;
                $coQueue[]         = $coreq->id;
            }
        }

        $plan->load('semesters.subjects.subject');
        foreach ($plan->semesters as $sem) {
            $sem->update(['expected_credits' => $sem->subjects->sum(fn($ss) => $ss->subject?->credits ?? 0)]);
        }

        $message = 'Đã di chuyển môn học.';
        if (!empty($movedCoreqNames)) {
            $message .= ' Môn song hành đi theo: ' . implode(', ', $movedCoreqNames) . '.';
        }

        return response()->json([
            'success'      => true,
            'message'      => $message,
            'coreqs_moved' => $movedCoreqNames,
            'data'         => $this->attachGrades($plan->load('semesters.subjects.subject'), $userId),
        ]);
    }

    // POST /api/v1/study-plans/apply-suggestions
    public function applySuggestions(ApplySuggestionsRequest $request)
    {
        $userId  = Auth::id();
        $plan    = StudyPlan::where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)->firstOrFail();

        $updated = $this->planService->redistributeFrom(
            $plan,
            (int) $request->input('target_semester_index'),
            $request->input('subject_ids')
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã áp dụng gợi ý và rải lại lộ trình.',
            'data'    => $this->attachGrades($updated, $userId),
        ]);
    }

    // POST /api/v1/study-plans/add-retake
    public function addRetake(AddRetakeRequest $request)
    {
        $userId    = Auth::id();
        $fromSem   = (int) $request->input('from_semester');
        $subjectId = (int) $request->input('subject_id');

        $plan = StudyPlan::with('semesters.subjects')
            ->where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)->firstOrFail();

        $targetSem = $plan->semesters->where('semester_index', '>', $fromSem)->sortBy('semester_index')->first()
            ?? StudyPlanSemester::create([
                'study_plan_id'  => $plan->id,
                'semester_index' => ($plan->semesters->max('semester_index') ?? $fromSem) + 1,
            ]);

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
            'original_grade'         => $request->input('original_grade'),
        ]);

        $plan->load('semesters.subjects.subject');

        return response()->json([
            'success'         => true,
            'message'         => "Đã thêm học lại vào Học kỳ {$targetSem->semester_index}.",
            'data'            => $this->attachGrades($plan, $userId),
            'retake_semester' => $targetSem->semester_index,
        ]);
    }

    // POST /api/v1/study-plans/toggle-elective
    public function toggleElective(ToggleElectiveRequest $request)
    {
        $userId      = Auth::id();
        $subjectId   = (int) $request->input('subject_id');
        $semesterIdx = (int) $request->input('semester_index');

        $plan = StudyPlan::with('semesters.subjects.subject')
            ->where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)->firstOrFail();

        $sem = $plan->semesters->firstWhere('semester_index', $semesterIdx);
        if (!$sem) {
            return response()->json(['error' => 'Không tìm thấy học kỳ.'], 404);
        }

        if ($request->input('action') === 'remove') {
            StudyPlanSubject::where('study_plan_semester_id', $sem->id)
                ->where('subject_id', $subjectId)->delete();
        } else {
            $already = StudyPlanSubject::where('study_plan_semester_id', $sem->id)
                ->where('subject_id', $subjectId)->exists();

            if ($already) {
                return response()->json(['error' => 'Môn này đã có trong học kỳ.'], 422);
            }

            $subject     = Subject::findOrFail($subjectId);
            $frameworkId = $this->resolveFrameworkId($userId);
            $eg          = null;

            if ($frameworkId) {
                $eg = ElectiveGroup::where('curriculum_framework_id', $frameworkId)
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

    // POST /api/v1/study-plans/{id}/dedup-retakes
    public function dedupRetakes($id)
    {
        $userId = Auth::id();
        $plan   = StudyPlan::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $this->planService->deduplicateRetakes($plan);
        $plan->load('semesters.subjects');

        return response()->json([
            'success' => true,
            'message' => 'Đã dọn sạch môn học trùng lặp.',
            'data'    => $this->attachGrades($plan, $userId),
        ]);
    }
}
