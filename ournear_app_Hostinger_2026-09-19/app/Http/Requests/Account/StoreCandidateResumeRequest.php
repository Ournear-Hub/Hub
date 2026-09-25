<?php

namespace App\Http\Requests\Account;

use App\Enums\FileVisibility;
use App\Support\CandidateFileRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCandidateResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCandidate() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => CandidateFileRules::resume(),
            'title' => ['nullable', 'string', 'max:120'],
            'visibility' => ['nullable', Rule::enum(FileVisibility::class)],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return CandidateFileRules::resumeMessages();
    }
}
