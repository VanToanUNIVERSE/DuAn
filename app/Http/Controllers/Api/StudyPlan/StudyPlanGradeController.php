<?php

namespace App\Http\Controllers\Api\StudyPlan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\StudyPlan\Concerns\HandlesStudyPlanDisplay;
use App\Http\Requests\StudyPlan\UpdateGradeRequest;
use App\Models\StudyPlan;
use App\Services\AcademicEvaluationService;
use Illuminate\Support\Facades\Auth;

class StudyPlanGradeController extends Controller
{
    use HandlesStudyPlanDisplay;

    public function __construct(protected AcademicEvaluationService $evaluationService) {}

    // POST /api/v1/study-plans/update-grade
    public function updateGrade(UpdateGradeRequest $request)
    {
        $userId        = Auth::id();
        $subjectId     = (int) $request->input('subject_id');
        $grade         = $request->input('grade');
        $planSubjectId = $request->input('plan_subject_id');

        $studyPlan = StudyPlan::with('semesters.subjects')
            ->where('id', $request->input('study_plan_id'))
            ->where('user_id', $userId)
            ->firstOrFail();

        $targetRow = null;
        foreach ($studyPlan->semesters as $sem) {
            foreach ($sem->subjects as $ss) {
                if ($planSubjectId && $ss->id == $planSubjectId) {
                    $targetRow = $ss; break 2;
                }
                if (!$planSubjectId && $ss->subject_id == $subjectId && !$ss->is_retake) {
                    $targetRow = $ss;
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
}
