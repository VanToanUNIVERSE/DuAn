<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyPlan extends Model
{
    protected $fillable = [
        'user_id',
        'target_year',
        'credits_per_semester',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studyPlanSubjects()
    {
        return $this->hasMany(StudyPlanSubject::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'study_plan_subjects')
                    ->withPivot('semester')
                    ->withTimestamps();
    }
}
