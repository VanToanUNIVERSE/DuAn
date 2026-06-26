<?php

namespace App\Http\Requests\StudyPlan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'study_plan_id'   => 'required|exists:study_plans,id',
            'subject_id'      => 'required|exists:subjects,id',
            'grade'           => 'nullable|numeric|min:0|max:10',
            'plan_subject_id' => 'nullable|integer',
        ];
    }
}
