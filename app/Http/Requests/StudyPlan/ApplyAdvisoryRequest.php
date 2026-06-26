<?php

namespace App\Http\Requests\StudyPlan;

use Illuminate\Foundation\Http\FormRequest;

class ApplyAdvisoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tc_per_sem'   => 'required|integer|min:12|max:22',
            'redistribute' => 'required|boolean',
        ];
    }
}
