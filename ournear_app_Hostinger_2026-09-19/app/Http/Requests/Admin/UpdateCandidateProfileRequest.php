<?php

namespace App\Http\Requests\Admin;

use App\Enums\NoticePeriod;
use App\Models\CandidateProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->route('candidate');

        return $profile instanceof CandidateProfile && $this->user()?->can('update', $profile) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'experience_range_id' => ['nullable', 'integer', Rule::exists('experience_ranges', 'id')],
            'qualification_id' => ['nullable', 'integer', Rule::exists('qualifications', 'id')],
            'current_designation' => ['nullable', 'string', 'max:255'],
            'current_company' => ['nullable', 'string', 'max:255'],
            'industry_id' => ['nullable', 'integer', Rule::exists('industries', 'id')],
            'job_category_id' => ['nullable', 'integer', Rule::exists('job_categories', 'id')],
            'current_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'expected_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'notice_period' => ['nullable', Rule::enum(NoticePeriod::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach ([
            'location_id',
            'experience_range_id',
            'qualification_id',
            'mobile',
            'current_designation',
            'current_company',
            'industry_id',
            'job_category_id',
            'current_salary',
            'expected_salary',
            'notice_period',
        ] as $field) {
            if ($this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        $this->merge($merge);
    }
}
