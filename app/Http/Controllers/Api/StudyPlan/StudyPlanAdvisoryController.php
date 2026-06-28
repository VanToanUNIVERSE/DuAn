<?php

namespace App\Http\Controllers\Api\StudyPlan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\StudyPlan\Concerns\HandlesStudyPlanDisplay;
use App\Http\Requests\StudyPlan\AdjustTargetRequest;
use App\Http\Requests\StudyPlan\ApplyAdvisoryRequest;
use App\Models\StudyPlan;
use App\Services\Plan\AdvisoryService;
use App\Services\StudyPlanService;
use Illuminate\Support\Facades\Auth;

class StudyPlanAdvisoryController extends Controller
{
    use HandlesStudyPlanDisplay;

    public function __construct(
        protected AdvisoryService $advisoryService,
        protected StudyPlanService $planService
    ) {}

    // GET /api/v1/study-plans/{id}/advisory
    public function advisory($id)
    {
        $userId = Auth::id();
        $plan   = StudyPlan::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $data   = $this->advisoryService->computeAdvisory($plan, $userId);

        return response()->json(['success' => true, 'data' => $data]);
    }

    // POST /api/v1/study-plans/{id}/apply-advisory
    public function applyAdvisory(ApplyAdvisoryRequest $request, $id)
    {
        $userId  = Auth::id();
        $plan    = StudyPlan::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $updated = $this->advisoryService->applyAdvisory(
            $plan,
            $userId,
            (int) $request->input('tc_per_sem'),
            (bool) $request->input('redistribute')
        );

        return response()->json([
            'success' => true,
            'message' => $request->input('redistribute')
                ? 'Đã cập nhật TC/kỳ và rải lại lộ trình.'
                : 'Đã cập nhật TC/kỳ. Bạn có thể tự điều chỉnh thứ tự môn học.',
            'data'    => $this->attachGrades($updated, $userId),
        ]);
    }

    // POST /api/v1/study-plans/{id}/adjust-target
    public function adjustTarget(AdjustTargetRequest $request, $id)
    {
        $userId = Auth::id();
        $plan   = StudyPlan::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $plan->load('semesters.subjects.subject');

        $newTarget  = (int) $request->input('target_semesters', $plan->target_semesters ?? 8);
        $currentSem = $this->detectCurrentSemester($plan, $userId);

        // Khi client chỉ đổi mục tiêu (không gửi tc_per_sem), tính lại TC/kỳ từ chính
        // số tín chỉ còn lại trong kế hoạch chia đều cho số kỳ còn lại trong mục tiêu.
        // Nếu không: số kỳ không co/giãn theo target, hoặc trần quá cao làm kỳ cuối bị đói.
        $newTc = $request->filled('tc_per_sem')
            ? (int) $request->input('tc_per_sem')
            : $this->planService->recommendTcPerSemForPlan($plan, $currentSem, $newTarget);

        $newMode = $newTc >= 20 ? 'fast' : ($newTc <= 14 ? 'slow' : 'normal');

        $plan->update([
            'target_semesters' => $newTarget,
            'tc_per_sem'       => $newTc,
            'mode'             => $newMode,
        ]);

        $updated = $this->planService->redistributeFrom($plan->fresh(), $currentSem);

        return response()->json([
            'success'          => true,
            'message'          => 'Đã cập nhật mục tiêu và rải lại lộ trình.',
            'data'             => $this->attachGrades($updated, $userId),
            'target_semesters' => $newTarget,
            'tc_per_sem'       => $newTc,
        ]);
    }
}
