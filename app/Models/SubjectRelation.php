<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectRelation extends Model
{
    protected $fillable = [
        'subject_id',
        'related_subject_id',
        'type',  // 'prerequisite' | 'corequisite'
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function relatedSubject()
    {
        return $this->belongsTo(Subject::class, 'related_subject_id');
    }
}
