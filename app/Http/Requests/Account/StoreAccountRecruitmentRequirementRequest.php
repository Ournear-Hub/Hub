<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRecruitmentRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessHiringWorkspace() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'designation' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:255'],
            'job_category' => ['nullable', 'string', 'max:255'],
            'number_of_candidates' => ['required', 'integer', 'min:1', 'max:9999'],
            'qualifications' => ['nullable', 'string', 'max:5000'],
            'skills' => ['nullable', 'string', 'max:5000'],
            'experience_range' => ['nullable', 'string', 'max:255'],
            'work_location' => ['required', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'hiring_timeline' => ['nullable', 'string', 'max:255'],
            'additional_requirements' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
