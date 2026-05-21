<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SemesterHistoryItem extends Model
{
    protected $fillable = [
        'semester_history_id',
        'subject_id',
        'grade',
        'status',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function semesterHistory()
    {
        return $this->belongsTo(SemesterHistory::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
