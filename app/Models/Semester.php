<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $fillable = [
        'curriculum_framework_id',
        'name',
        'total_credits',
    ];

    public function curriculumFramework()
    {
        return $this->belongsTo(CurriculumFramework::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }
}
