<?php

namespace App\Http\Controllers\Api\StudyPlan;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\StudyPlan\Concerns\HandlesStudyPlanDisplay;
use App\Http\Requests\StudyPlan\GeneratePlanRequest;
use App\Models\StudyPlan;
use App\Services\StudyPlanService;
use Illuminate\Support\Facades\Auth;

class StudyPlanController extends Controller
{
    use HandlesStudyPlanDisplay;

    public function __construct(protected StudyPlanService $planService) {}

    // GET /api/v1/study-plans
    public function index()
    {
        $userId = Auth::id();

        $plan = StudyPlan::where('user_id', $userId)
            ->where('is_saved', true)->where('is_active', true)->first()
            ?? StudyPlan::where('user_id', $userId)
                ->where('is_saved', true)->orderByDesc('updated_at')->first();

        $plans = $plan ? collect([$this->attachGrades($plan, $userId)]) : collect();

        return response()->json(['success' => true, 'data' => $plans]);
    }

    // GET /api/v1/study-plans/active
    public function getActivePlan()
    {
        $userId = Auth::id();
        $plan   = StudyPlan::where('user_id', $userId)->where('is_active', true)->first();

        if ($plan) {
            $this->planService->deduplicateRetakes($plan);
        }

        return response()->json([
            'success' => true,
            'data'    => $plan ? $this->attachGrades($plan, $userId) : null,
        ]);
    }

    // POST /api/v1/study-plans/generate
    public function generate(GeneratePlanRequest $request)
    {
        $userId = Auth::id();
        $result = $this->planService->generatePlan(
            $userId,
            $request->input('name'),
            (int) $request->input('target_semesters', 8)
        );

        return response()->json([
            'success'               => true,
            'data'                  => $this->attachGrades($result['plan'], $userId),
            'tc_per_sem'            => $result['tc_per_sem'],
            'target_semesters'      => $result['target_semesters'],
            'over_semesters'        => $result['over_semesters'] ?? false,
            'over_semesters_count'  => $result['over_semesters_count'] ?? 0,
            'over_semesters_notice' => $result['over_semesters_notice'] ?? null,
        ]);
    }

    // GET /api/v1/study-plans/saved
    public function getSavedPlans()
    {
        $plans = StudyPlan::where('user_id', Auth::id())
            ->where('is_saved', true)
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'mode', 'target_semester_count', 'is_active', 'updated_at']);

        return response()->json(['success' => true, 'data' => $plans]);
    }

    // GET /api/v1/study-plans/{id}/load
    public function loadPlan($id)
    {
        $userId = Auth::id();
        $plan   = StudyPlan::where('id', $id)->where('user_id', $userId)->firstOrFail();

        return response()->json(['success' => true, 'data' => $this->attachGrades($plan, $userId)]);
    }

    // POST /api/v1/study-plans/{id}/save
    public function savePlan($id, \Illuminate\Http\Request $request)
    {
        $plan = StudyPlan::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $plan->update([
            'is_saved' => true,
            'name'     => $request->filled('name') ? $request->input('name') : $plan->name,
        ]);

        return response()->json(['success' => true, 'message' => 'Đã lưu kế hoạch.']);
    }

    // DELETE /api/v1/study-plans/{id}
    public function destroy($id)
    {
        StudyPlan::where('id', $id)->where('user_id', Auth::id())->firstOrFail()->delete();

        return response()->json(['success' => true, 'message' => 'Đã xóa kế hoạch.']);
    }
}
