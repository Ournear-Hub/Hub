<?php

namespace App\Http\Requests\Admin;

use App\Models\CandidateProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCandidateCertificationRequest extends FormRequest
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
        $profile = $this->route('candidate');
        $profileId = $profile instanceof CandidateProfile ? $profile->id : 0;

        return [
            'name' => ['required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'issued_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:issued_on'],
            'candidate_document_id' => [
                'nullable',
                'integer',
                Rule::exists('candidate_documents', 'id')->where('candidate_profile_id', $profileId),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function certificationAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'issuer' => filled($this->input('issuer')) ? trim((string) $this->input('issuer')) : null,
            'issued_on' => $this->input('issued_on') ?: null,
            'expires_on' => $this->input('expires_on') ?: null,
            'candidate_document_id' => filled($this->input('candidate_document_id'))
                ? (int) $this->input('candidate_document_id')
                : null,
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['issuer', 'issued_on', 'expires_on', 'candidate_document_id'] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        $this->merge($merge);
    }
}
