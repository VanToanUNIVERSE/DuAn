<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyPlanRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'study_plan_id',
        'gpa_at_revision',
        'reason',
        'old_plan_data',
        'new_plan_data',
    ];

    protected $casts = [
        'old_plan_data' => 'array',
        'new_plan_data' => 'array',
    ];
}
