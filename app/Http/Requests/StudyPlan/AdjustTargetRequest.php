<?php

namespace App\Http\Requests\StudyPlan;

use Illuminate\Foundation\Http\FormRequest;

class AdjustTargetRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'target_semesters' => 'nullable|integer|in:6,7,8,9,10',
            'tc_per_sem'       => 'nullable|integer|min:12|max:22',
        ];
    }
}
