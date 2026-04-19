<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyPlanSubject extends Model
{
    protected $fillable = [
        'subject_id',
        'study_plan_id',
        'semester',
    ];

    public function studyPlan()
    {
        return $this->belongsTo(StudyPlan::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
