<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SemesterHistory extends Model
{
    protected $fillable = [
        'user_id',
        'semester_number',
        'academic_year',
        'program_type',
        'total_credits',
        'passed_credits',
        'gpa',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'gpa'          => 'float',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SemesterHistoryItem::class)->with('subject');
    }
}
