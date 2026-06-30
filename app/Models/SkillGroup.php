<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillGroup extends Model
{
    protected $fillable = ['name', 'focus_area'];

    // Định hướng chuyên ngành của CTĐT ngành CNTT (theo báo cáo mục 2.2.2).
    // Mỗi skill group được gán 1 định hướng để cộng điểm ưu tiên cho môn cùng nhóm.
    const FOCUS_AREAS = [
        'software'    => 'Phát triển phần mềm',
        'data'        => 'Khoa học dữ liệu & Trí tuệ nhân tạo',
        'security'    => 'An toàn thông tin & Hệ thống',
        'application' => 'Công nghệ ứng dụng',
    ];

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }
}
