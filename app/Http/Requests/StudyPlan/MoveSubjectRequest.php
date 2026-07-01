<?php

namespace App\Http\Requests\StudyPlan;

use Illuminate\Foundation\Http\FormRequest;

class MoveSubjectRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'study_plan_id'         => 'required|exists:study_plans,id',
            'subject_id'            => 'required|exists:subjects,id',
            'plan_subject_id'       => 'nullable|integer|exists:study_plan_subjects,id',
            'target_semester_index' => 'required|integer|min:1',
        ];
    }
}
