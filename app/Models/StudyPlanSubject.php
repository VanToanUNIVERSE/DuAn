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
        'is_retake',
        'original_attempt_sem',
        'original_grade',
        'subject_grade',   // điểm riêng của row này (độc lập giữa gốc và retake)
    ];

    protected $casts = [
        'is_retake'     => 'boolean',
        'is_frozen'     => 'boolean',
        'original_grade'=> 'float',
        'subject_grade' => 'float',
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
