<?php

namespace App\Http\Requests\StudyPlan;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'             => 'required|string|max:120',
            'target_semesters' => 'nullable|integer|in:6,7,8,9,10',
        ];
    }
}
