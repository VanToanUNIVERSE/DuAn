<?php

namespace App\Http\Requests\StudyPlan;

use Illuminate\Foundation\Http\FormRequest;

class AddRetakeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'study_plan_id'  => 'required|exists:study_plans,id',
            'subject_id'     => 'required|integer|exists:subjects,id',
            'from_semester'  => 'required|integer|min:1',
            'original_grade' => 'nullable|numeric|min:0|max:10',
        ];
    }
}
