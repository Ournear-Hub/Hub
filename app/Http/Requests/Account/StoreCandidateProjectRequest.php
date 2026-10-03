<?php

namespace App\Http\Requests\Account;

use App\Models\CandidateProfile;
use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateProjectRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function projectAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'role' => filled($this->input('role')) ? trim((string) $this->input('role')) : null,
            'company' => filled($this->input('company')) ? trim((string) $this->input('company')) : null,
            'description' => filled($this->input('description')) ? trim((string) $this->input('description')) : null,
            'started_on' => $this->input('started_on') ?: null,
            'ended_on' => $this->input('ended_on') ?: null,
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (['role', 'company', 'description', 'started_on', 'ended_on'] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $merge[$field] = null;
            }
        }
        $this->merge($merge);
    }
}
