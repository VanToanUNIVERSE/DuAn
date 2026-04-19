<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingProgram extends Model
{
    protected $fillable = [
        'program_name',
        'education_level',
        'program_code',
        'program_type',
        'program_duration',
        'academic_year',
    ];

    public function curriculumFrameworks()
    {
        return $this->hasMany(CurriculumFramework::class);
    }
}
