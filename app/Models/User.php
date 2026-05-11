<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'fullName',
        'student_code',
        // Cấu hình chương trình — lưu lựa chọn cuối của user trên trang gợi ý
        'pref_academic_year',
        'pref_program_type',
        'pref_current_semester',
        'pref_target_years',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    // Relationships
    public function grades()
    {
        return $this->hasMany(UserGrade::class);
    }

    public function studyPlans()
    {
        return $this->hasMany(StudyPlan::class);
    }
}
