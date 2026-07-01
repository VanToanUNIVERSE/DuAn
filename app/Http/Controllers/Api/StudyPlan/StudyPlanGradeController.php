<?php

namespace App\Http\Controllers\Api\StudyPlan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\StudyPlan\Concerns\HandlesStudyPlanDisplay;
use App\Http\Requests\StudyPlan\UpdateGradeRequest;
use App\Models\StudyPlan;
use App\Services\AcademicEvaluationService;
use App\Services\Plan\PlanRevisionService;
use App\Services\StudyPlanService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudyPlanGradeController extends Controller
{
    use HandlesStudyPlanDisplay;

    public function __construct(
        protected AcademicEvaluationService $evaluationService,
        protected StudyPlanService $planService,
        protected PlanRevisionService $revisionService
    ) {}

    // POST /api/v1/study-plans/update-grade
    public function updateGrade(UpdateGradeRequest $request)
    {
        $userId        = Auth::id();
        $subjectId     = (int) $request->input('subject_id');
        $grade         = $request->input('grade');
        $planSubjectId = $request->input('plan_subject_id');

        // Khóa dòng study_plan để CHỜ nếu có request cập nhật điểm khác đang chạy
        // song song cho CÙNG kế hoạch (vd sinh viên điền nhanh nhiều môn liền nhau).
        // Nếu không khóa, 2 request đọc DB gần như cùng lúc đều không thấy kỳ học lại
        // vừa tạo của nhau → mỗi request tự tạo một kỳ MỚI trùng semester_index, khiến
        // môn rớt bị rải mỗi môn một kỳ và kéo-thả bị sai kỳ đích (đích tra theo
        // semester_index nên khi trùng số, chọn nhầm bản ghi).
        $retakeSemester = DB::transaction(function () use ($userId, $subjectId, $grade, $planSubjectId, $request, &$studyPlan) {
            $studyPlan = StudyPlan::with('semesters.subjects')
                ->where('id', $request->input('study_plan_id'))
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            // Tự sửa các kỳ trùng semester_index còn sót lại từ trước khi có khóa này.
            $this->planService->mergeDuplicateSemesters($studyPlan);
            $studyPlan->load('semesters.subjects');

            $targetRow      = null;
            $targetSemIndex = 1;
            foreach ($studyPlan->semesters as $sem) {
                foreach ($sem->subjects as $ss) {
                    if ($planSubjectId && $ss->id == $planSubjectId) {
                        $targetRow = $ss; $targetSemIndex = (int) $sem->semester_index; break 2;
                    }
                    if (!$planSubjectId && $ss->subject_id == $subjectId && !$ss->is_retake) {
                        $targetRow = $ss; $targetSemIndex = (int) $sem->semester_index;
                    }
                }
            }

            if (!$targetRow) {
                abort(404, 'Không tìm thấy môn trong kế hoạch.');
            }

            $targetRow->update([
                'subject_grade' => $grade,
                'is_completed'  => $grade !== null && $grade >= 5.0,
            ]);

            $studyPlan->load('semesters.subjects');
            $this->syncUserGrade($userId, $subjectId, $studyPlan);

            // ── Tự động xếp / gỡ HỌC LẠI theo kết quả ─────────────────────────
            $beforeRetake   = $this->revisionService->snapshot($studyPlan);
            $retakeSemester = null;
            if ($grade !== null && $grade < 5.0) {
                // Chỉ TỰ xếp học lại cho môn BẮT BUỘC. Môn TỰ CHỌN rớt → KHÔNG ép học lại,
                // để sinh viên tự quyết (học lại môn đó hoặc đổi sang môn khác trong nhóm).
                if (!$this->isElectiveSubject($userId, $subjectId)) {
                    $retakeSemester = $this->planService->scheduleRetake($studyPlan, $subjectId, $targetSemIndex, (float) $grade);
                }
            } else {
                // Đạt hoặc xóa điểm → gỡ học lại chưa chấm (nếu trước đó từng rớt rồi nay sửa lại)
                $this->planService->removeUngradedRetake($studyPlan, $subjectId);
            }

            // Ghi lịch sử khi việc rớt môn làm thay đổi kế hoạch (xếp thêm học lại)
            if ($retakeSemester !== null) {
                $subjectName = \App\Models\Subject::find($subjectId)?->name ?? 'môn';
                $this->revisionService->record(
                    $studyPlan,
                    "Rớt môn \"{$subjectName}\" → xếp học lại ở kỳ {$retakeSemester}",
                    $beforeRetake
                );
            }

            return $retakeSemester;
        });

        $updatedPlan = $this->attachGrades($studyPlan->load('semesters.subjects'), $userId);
        $currentSem  = $this->detectCurrentSemester($updatedPlan, $userId);
        $evaluation  = $this->evaluationService->evaluate(
            $userId,
            $studyPlan->mode ?? 'normal',
            $studyPlan->target_semesters ?? $studyPlan->target_semester_count ?? 8,
            $currentSem
        );

        return response()->json([
            'success'         => true,
            'data'            => $updatedPlan,
            'evaluation'      => $evaluation,
            'retake_semester' => $retakeSemester,
        ]);
    }
}
