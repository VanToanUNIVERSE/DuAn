<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'credits',
        'subject_type_id',
        'skill_group_id',
        'semester_id',
    ];

    public function subjectType()
    {
        return $this->belongsTo(SubjectType::class);
    }

    public function skillGroup()
    {
        return $this->belongsTo(SkillGroup::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function grades()
    {
        return $this->hasMany(UserGrade::class);
    }

    public function studyPlanSubjects()
    {
        return $this->hasMany(StudyPlanSubject::class);
    }

    // Các môn là tiên quyết/song hành của môn này
    public function relations()
    {
        return $this->hasMany(SubjectRelation::class);
    }

    // Các môn mà môn này là tiên quyết/song hành
    public function relatedRelations()
    {
        return $this->hasMany(SubjectRelation::class, 'related_subject_id');
    }

    // DS môn tiên quyết (prerequisite) của môn này
    public function prerequisites()
    {
        return $this->belongsToMany(
            Subject::class,
            'subject_relations',
            'subject_id',
            'related_subject_id'
        )->wherePivot('type', 'prerequisite');
    }

    // DS môn học song hành (corequisite) của môn này
    public function corequisites()
    {
        return $this->belongsToMany(
            Subject::class,
            'subject_relations',
            'subject_id',
            'related_subject_id'
        )->wherePivot('type', 'corequisite');
    }
}
