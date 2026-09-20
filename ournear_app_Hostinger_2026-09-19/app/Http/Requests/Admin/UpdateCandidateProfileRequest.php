<?php

namespace App\Http\Requests\Admin;

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
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['location_id', 'experience_range_id', 'qualification_id', 'mobile'] as $field) {
            if ($this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        $this->merge($merge);
    }
}
