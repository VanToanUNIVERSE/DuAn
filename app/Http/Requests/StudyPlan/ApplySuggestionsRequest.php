<?php

namespace App\Http\Requests\StudyPlan;

use Illuminate\Foundation\Http\FormRequest;

class ApplySuggestionsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'study_plan_id'         => 'required|exists:study_plans,id',
            'subject_ids'           => 'required|array|min:1',
            'subject_ids.*'         => 'integer|exists:subjects,id',
            'target_semester_index' => 'required|integer|min:1',
        ];
    }
}
