<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectiveGroup extends Model
{
    protected $fillable = ['curriculum_framework_id', 'name', 'required_credits'];

    public function curriculumFramework()
    {
        return $this->belongsTo(CurriculumFramework::class);
    }

    public function curriculumSubjects()
    {
        return $this->hasMany(CurriculumSubject::class);
    }
}
