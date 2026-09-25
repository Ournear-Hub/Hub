<?php

namespace App\Http\Requests\Account;

use App\Models\CandidateProfile;
use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateSkillRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:1', 'max:64'],
        ];
    }

    public function skillName(): string
    {
        return trim((string) $this->input('name', ''));
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('name')) {
            $this->merge([
                'name' => trim(preg_replace('/\s+/', ' ', (string) $this->input('name')) ?? ''),
            ]);
        }
    }
}
