<?php

namespace App\Http\Requests\Account;

use App\Models\CandidateProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmploymentHistoryRequest extends FormRequest
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
            'company' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'industry_id' => ['nullable', 'integer', Rule::exists('industries', 'id')],
            'started_on' => ['required', 'date'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'is_current' => ['sometimes', 'boolean'],
            'is_internship' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function historyAttributes(): array
    {
        $isInternship = $this->boolean('is_internship');
        $isCurrent = $isInternship ? false : $this->boolean('is_current');
        $description = trim((string) $this->input('description', ''));

        return [
            'company' => trim((string) $this->input('company')),
            'designation' => trim((string) $this->input('designation')),
            'industry_id' => filled($this->input('industry_id')) ? (int) $this->input('industry_id') : null,
            'started_on' => $this->input('started_on'),
            'ended_on' => $isCurrent ? null : $this->input('ended_on'),
            'is_current' => $isCurrent,
            'is_internship' => $isInternship,
            'description' => $description === '' ? null : $description,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('is_current')) {
                return;
            }

            if (! filled($this->input('ended_on'))) {
                $validator->errors()->add('ended_on', 'Add an end date, or mark this as current employment.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->exists('is_current')) {
            $merge['is_current'] = $this->boolean('is_current');
        }

        foreach (['industry_id', 'ended_on', 'description'] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        $this->merge($merge);
    }
}
