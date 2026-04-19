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
}
