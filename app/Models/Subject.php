<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    const REQUIREMENT_TYPES = [
        'none'            => 'Không yêu cầu',
        'completed_basic' => 'Đã hoàn thành đại cương',
        'completed_major' => 'Đã hoàn thành cơ sở ngành',
        'completed_specialized' => 'Đã hoàn thành chuyên ngành',
        'completed_all'   => 'Đã hoàn thành tất cả',
        'min_credits'     => 'Đủ tối thiểu tín chỉ',
    ];

    protected $fillable = [
        'subject_code',
        'name',
        'credits',
        'subject_type_id',
        'skill_group_id',
        'program_group_id',
        'semester_id',
        'note',
        'requirement_type',
    ];

    public function subjectType()
    {
        return $this->belongsTo(SubjectType::class);
    }

    public function skillGroup()
    {
        return $this->belongsTo(SkillGroup::class);
    }

    public function programGroup()
    {
        return $this->belongsTo(ProgramGroup::class);
    }

    // Môn học thuộc các chương trình đào tạo (thông qua bảng trung gian curriculum_subject)
    public function curriculumFrameworks()
    {
        return $this->belongsToMany(
            CurriculumFramework::class,
            'curriculum_subject',
            'subject_id',
            'curriculum_framework_id'
        )->using(CurriculumSubject::class)->withPivot('semester_id')->withTimestamps();
    }

    // Danh sách học kỳ mà môn này được phân công
    public function assignedSemesters()
    {
        return $this->belongsToMany(
            Semester::class,
            'curriculum_subject',
            'subject_id',
            'semester_id'
        )->using(CurriculumSubject::class)->withPivot('curriculum_framework_id')->withTimestamps();
    }

    public function grades()
    {
        return $this->hasMany(UserGrade::class);
    }

    // Removed obsolete studyPlanSubjects relationship

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
