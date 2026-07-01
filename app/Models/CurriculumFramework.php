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

    public function electiveGroups()
    {
        return $this->hasMany(ElectiveGroup::class);
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

    /**
     * Tổng tín chỉ thực tế phải hoàn thành của khung:
     * - môn không thuộc nhóm tự chọn được tính đầy đủ;
     * - mỗi nhóm tự chọn chỉ tính số tín chỉ bắt buộc của nhóm.
     *
     * Không dùng cột total_credits vì dữ liệu cũ có thể chưa được đồng bộ sau khi
     * thêm/bớt môn hoặc thay đổi nhóm tự chọn.
     */
    public function calculatedTotalCredits(): int
    {
        $assignments = CurriculumSubject::where('curriculum_framework_id', $this->id)
            ->with('subject')
            ->get()
            ->unique('subject_id');

        $mandatoryCredits = $assignments
            ->whereNull('elective_group_id')
            ->sum(fn ($assignment) => (int) ($assignment->subject?->credits ?? 0));

        $electiveCredits = (int) $this->electiveGroups()->sum('required_credits');

        return (int) $mandatoryCredits + $electiveCredits;
    }
}
