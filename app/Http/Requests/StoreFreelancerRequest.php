<?php

namespace App\Http\Requests;

use App\Enums\FreelancerGender;
use App\Enums\NoticePeriod;
use App\Enums\PreferredWorkType;
use App\Http\Requests\Concerns\ProtectsPublicForms;
use App\Support\CandidateFileRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFreelancerRequest extends FormRequest
{
    use ProtectsPublicForms;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->publicProtectionRules(),
            'submission_token' => ['required', 'string', 'size:40'],
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:40', 'regex:/^[0-9+\s().-]{8,40}$/'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'gender' => ['nullable', Rule::enum(FreelancerGender::class)],
            'date_of_birth' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'current_location_id' => ['required', 'integer', Rule::exists('locations', 'id')->where('is_active', true)],
            'preferred_location_ids' => ['nullable', 'array'],
            'preferred_location_ids.*' => ['integer', Rule::exists('locations', 'id')->where('is_active', true)],
            'primary_skill' => ['required', 'string', 'max:255'],
            'current_designation' => ['nullable', 'string', 'max:255'],
            'current_company' => ['nullable', 'string', 'max:255'],
            'industry_id' => ['required', 'integer', Rule::exists('industries', 'id')->where('is_active', true)],
            'job_category_id' => ['nullable', 'integer', Rule::exists('job_categories', 'id')->where('is_active', true)],
            'experience_range_id' => ['required', 'integer', Rule::exists('experience_ranges', 'id')->where('is_active', true)],
            'employment_type_id' => ['nullable', 'integer', Rule::exists('employment_types', 'id')->where('is_active', true)],
            'preferred_work_type' => ['nullable', Rule::enum(PreferredWorkType::class)],
            'expected_rate' => ['nullable', 'string', 'max:100'],
            'notice_period' => ['nullable', Rule::enum(NoticePeriod::class)],
            'availability' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'string', 'max:2000'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'resume' => CandidateFileRules::optionalResume(),
            'certificate' => [
                'nullable',
                'file',
                'max:5120',
                'mimes:pdf,doc,docx,jpg,jpeg,png',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png',
            ],
            'consent' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullable = ['gender', 'date_of_birth', 'current_designation', 'current_company', 'job_category_id', 'employment_type_id', 'preferred_work_type', 'expected_rate', 'notice_period', 'availability', 'skills', 'summary'];
        $emptyToNull = [];
        foreach ($nullable as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $emptyToNull[$field] = null;
            }
        }

        $this->merge([
            ...$emptyToNull,
            'email' => strtolower(trim((string) $this->input('email'))),
            'name' => trim((string) $this->input('name')),
            'mobile' => trim((string) $this->input('mobile')),
            'primary_skill' => trim((string) $this->input('primary_skill')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...CandidateFileRules::resumeMessages('resume'),
            ...CandidateFileRules::documentMessages('certificate'),
            'consent.accepted' => 'Please confirm that we may contact you about this freelancer partnership.',
            'mobile.regex' => 'Enter a valid mobile number.',
        ];
    }
}
