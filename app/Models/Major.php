<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    protected $fillable = ['name', 'faculty_id'];

    // Chuyên ngành thuộc một khoa
    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    // Một chuyên ngành có nhiều lớp
    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'major_id');
    }
}
