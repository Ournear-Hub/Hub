<?php

namespace App\Http\Requests\Admin;

use App\Models\CandidateProfile;
use Illuminate\Foundation\Http\FormRequest;

class RecordTalentInterestRequest extends FormRequest
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
            'note' => ['required', 'string', 'max:1000'],
        ];
    }

    public function note(): string
    {
        return trim((string) $this->input('note'));
    }
}
