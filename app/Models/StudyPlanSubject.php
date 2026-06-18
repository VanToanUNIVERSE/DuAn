<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyPlanSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_plan_semester_id',
        'subject_id',
        'is_completed',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(StudyPlanSemester::class, 'study_plan_semester_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
