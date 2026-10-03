<?php

namespace App\Http\Requests\Account;

use App\Enums\NoticePeriod;
use App\Models\CandidateProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfessionalProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $profile = $user?->candidateProfile;

        return $user?->isCandidate() === true
            && $profile instanceof CandidateProfile
            && $user->can('update', $profile);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_designation' => ['nullable', 'string', 'max:255'],
            'current_company' => ['nullable', 'string', 'max:255'],
            'industry_id' => ['nullable', 'integer', Rule::exists('industries', 'id')],
            'job_category_id' => ['nullable', 'integer', Rule::exists('job_categories', 'id')],
            'current_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'expected_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'notice_period' => ['nullable', Rule::enum(NoticePeriod::class)],
            'employment_preference' => ['nullable', 'string', 'max:2000'],
            'experience' => ['nullable', 'string', 'max:255'],
            'experience_range_id' => ['nullable', 'integer', Rule::exists('experience_ranges', 'id')],
            'qualification' => ['nullable', 'string', 'max:255'],
            'qualification_id' => ['nullable', 'integer', Rule::exists('qualifications', 'id')],
            'skills' => ['sometimes', 'array'],
            'skills.*' => ['string', 'max:64'],
            'preferred_location_ids' => ['sometimes', 'array'],
            'preferred_location_ids.*' => ['integer', Rule::exists('locations', 'id')],
            'preferred_employment_type_ids' => ['sometimes', 'array'],
            'preferred_employment_type_ids.*' => ['integer', Rule::exists('employment_types', 'id')],
            'willing_to_relocate' => ['sometimes', 'boolean'],
            'availability' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function professionalAttributes(): array
    {
        $nullableStrings = [
            'current_designation',
            'current_company',
            'notice_period',
            'employment_preference',
            'experience',
            'qualification',
            'availability',
        ];

        $attributes = [];

        foreach ($nullableStrings as $field) {
            if (! $this->fieldWasSubmitted($field)) {
                continue;
            }

            $value = trim((string) $this->input($field, ''));
            $attributes[$field] = $value === '' ? null : $value;
        }

        foreach (['industry_id', 'job_category_id', 'experience_range_id', 'qualification_id'] as $field) {
            if (! $this->fieldWasSubmitted($field)) {
                continue;
            }

            $value = $this->input($field);
            $attributes[$field] = filled($value) ? (int) $value : null;
        }

        foreach (['current_salary', 'expected_salary'] as $field) {
            if (! $this->fieldWasSubmitted($field)) {
                continue;
            }

            $value = $this->input($field);
            $attributes[$field] = filled($value) ? $value : null;
        }

        if ($this->fieldWasSubmitted('skills')) {
            $attributes['skills'] = array_values(array_filter(
                array_map(fn ($name) => trim((string) $name), $this->input('skills', [])),
                fn ($name) => $name !== '',
            ));
        }

        if ($this->fieldWasSubmitted('preferred_location_ids')) {
            $attributes['preferred_location_ids'] = array_values(array_unique(array_map(
                'intval',
                $this->input('preferred_location_ids', []),
            )));
        }

        if ($this->fieldWasSubmitted('preferred_employment_type_ids')) {
            $attributes['preferred_employment_type_ids'] = array_values(array_unique(array_map(
                'intval',
                $this->input('preferred_employment_type_ids', []),
            )));
        }

        if ($this->fieldWasSubmitted('willing_to_relocate')) {
            $attributes['willing_to_relocate'] = $this->boolean('willing_to_relocate');
        }

        return $attributes;
    }

    private function fieldWasSubmitted(string $field): bool
    {
        return array_key_exists($field, $this->all());
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach ([
            'industry_id',
            'job_category_id',
            'experience_range_id',
            'qualification_id',
            'current_salary',
            'expected_salary',
            'notice_period',
            'current_designation',
            'current_company',
            'employment_preference',
            'experience',
            'qualification',
            'availability',
        ] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        $this->merge($merge);
    }
}
