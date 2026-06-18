<?php

namespace App\Services;

use App\Models\StudyPlan;
use App\Models\StudyPlanRevision;

class StudyPlanRevisionService
{
    /**
     * Create a new revision snapshot
     */
    public function createRevision(int $userId, int $planId, float $gpa, string $reason, ?StudyPlan $oldPlanModel, ?StudyPlan $newPlanModel)
    {
        $oldData = $oldPlanModel ? $oldPlanModel->load('semesters.subjects.subject')->toArray() : null;
        $newData = $newPlanModel ? $newPlanModel->load('semesters.subjects.subject')->toArray() : null;

        return StudyPlanRevision::create([
            'user_id' => $userId,
            'study_plan_id' => $planId,
            'gpa_at_revision' => $gpa,
            'reason' => $reason,
            'old_plan_data' => $oldData,
            'new_plan_data' => $newData,
        ]);
    }
}
