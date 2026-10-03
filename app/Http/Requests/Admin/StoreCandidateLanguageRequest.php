<?php

namespace App\Http\Requests\Admin;

use App\Enums\LanguageProficiency;
use App\Models\CandidateProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCandidateLanguageRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:80'],
            'proficiency' => ['nullable', Rule::enum(LanguageProficiency::class)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function languageAttributes(): array
    {
        return [
            'name' => trim((string) $this->input('name')),
            'proficiency' => $this->input('proficiency') ?: null,
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('proficiency') && $this->input('proficiency') === '') {
            $this->merge(['proficiency' => null]);
        }
    }
}
