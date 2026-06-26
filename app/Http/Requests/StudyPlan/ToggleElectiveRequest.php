<?php

namespace App\Http\Requests\StudyPlan;

use Illuminate\Foundation\Http\FormRequest;

class ToggleElectiveRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'study_plan_id'  => 'required|exists:study_plans,id',
            'subject_id'     => 'required|integer|exists:subjects,id',
            'semester_index' => 'required|integer|min:1',
            'action'         => 'required|in:add,remove',
        ];
    }
}
