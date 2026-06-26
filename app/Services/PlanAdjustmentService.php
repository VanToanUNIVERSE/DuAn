<?php

namespace App\Services;

use App\Models\StudyPlan;
use Illuminate\Support\Facades\DB;

class PlanAdjustmentService
{
    protected $studyPlanService;
    protected $revisionService;

    public function __construct(StudyPlanService $studyPlanService, StudyPlanRevisionService $revisionService)
    {
        $this->studyPlanService = $studyPlanService;
        $this->revisionService = $revisionService;
    }

    /**
     * Adjust the plan based on evaluation
     */
    public function adjustPlan(int $userId, int $currentPlanId, array $evaluationResult)
    {
        return DB::transaction(function () use ($userId, $currentPlanId, $evaluationResult) {
            $oldPlan = StudyPlan::with('semesters.subjects.subject')->find($currentPlanId);
            $planName = $oldPlan ? $oldPlan->name : 'Kế hoạch điều chỉnh';
            
            // Re-generate the plan using the suggested target semesters
            $newTargetSems = (int)($evaluationResult['suggested_sems'] ?? 8);
            $result = $this->studyPlanService->generatePlan($userId, $planName, $newTargetSems);
            $newPlanModel = $result['plan'];

            // Save revision and then delete old plan
            if ($oldPlan && $oldPlan->id !== $newPlanModel->id) {
                $this->revisionService->createRevision(
                    $userId,
                    $newPlanModel->id,
                    $evaluationResult['gpa'],
                    $evaluationResult['message'],
                    $oldPlan,
                    $newPlanModel
                );
                $oldPlan->delete();
            }

            return $newPlanModel;
        });
    }
}
