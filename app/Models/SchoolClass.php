<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lớp sinh viên. Đặt tên model là SchoolClass vì "Class" là từ khóa của PHP;
 * bảng vẫn là `classes`.
 */
class SchoolClass extends Model
{
    protected $table = 'classes';

    protected $fillable = ['name', 'major_id', 'cohort'];

    // Lớp thuộc một chuyên ngành
    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    // Một lớp có nhiều sinh viên
    public function students()
    {
        return $this->hasMany(User::class, 'class_id');
    }
}
