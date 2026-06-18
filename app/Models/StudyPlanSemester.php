<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyPlanSemester extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_plan_id',
        'semester_index',
        'expected_credits',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class, 'study_plan_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(StudyPlanSubject::class);
    }
}
