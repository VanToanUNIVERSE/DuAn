<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CurriculumSubject extends Pivot
{
    protected $table = 'curriculum_subject';

    public $incrementing = true;

    protected $fillable = [
        'curriculum_framework_id',
        'semester_id',
        'subject_id',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function curriculumFramework()
    {
        return $this->belongsTo(CurriculumFramework::class);
    }
}
