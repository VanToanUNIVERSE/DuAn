<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyPlanRevision extends Model
{
    protected $fillable = [
        'user_id',
        'study_plan_id',
        'gpa_at_revision',
        'reason',
        'old_plan_data',
        'new_plan_data',
    ];

    protected $casts = [
        'gpa_at_revision' => 'float',
        'old_plan_data'   => 'array',
        'new_plan_data'   => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class);
    }
}
