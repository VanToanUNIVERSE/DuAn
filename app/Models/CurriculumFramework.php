<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurriculumFramework extends Model
{
    protected $fillable = [
        'training_program_id',
        'number_of_semesters',
        'total_credits',
    ];

    public function trainingProgram()
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }

    // Môn học được phân công trong chương trình này
    public function subjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'curriculum_subject',
            'curriculum_framework_id',
            'subject_id'
        )->using(\App\Models\CurriculumSubject::class)->withPivot('semester_id')->withTimestamps();
    }
}
