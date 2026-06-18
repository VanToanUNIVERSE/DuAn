<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'mode',
        'target_semester_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(StudyPlanSemester::class)->orderBy('semester_index');
    }
}
