<?php

namespace App\Http\Requests\Account;

use App\Models\CandidateProfile;
use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateEducationRequest extends FormRequest
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
            'institution' => ['required', 'string', 'max:255'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'field' => ['nullable', 'string', 'max:255'],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function educationAttributes(): array
    {
        return [
            'institution' => trim((string) $this->input('institution')),
            'qualification' => filled($this->input('qualification')) ? trim((string) $this->input('qualification')) : null,
            'field' => filled($this->input('field')) ? trim((string) $this->input('field')) : null,
            'started_on' => $this->input('started_on') ?: null,
            'ended_on' => $this->input('ended_on') ?: null,
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['qualification', 'field', 'started_on', 'ended_on'] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $merge[$field] = null;
            }
        }
        $this->merge($merge);
    }
}
