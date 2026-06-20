<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillGroup extends Model
{
    protected $fillable = ['name', 'focus_area'];

    const FOCUS_AREAS = [
        'backend'  => 'Backend Development',
        'frontend' => 'Frontend Development',
        'ai'       => 'AI / Machine Learning',
        'data'     => 'Data Science / Analytics',
        'mobile'   => 'Mobile Development',
        'devops'   => 'DevOps / Cloud',
        'testing'  => 'Testing / QA',
        'security' => 'Cybersecurity',
        'core'     => 'Kiến thức nền tảng (Core)',
    ];

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }
}
