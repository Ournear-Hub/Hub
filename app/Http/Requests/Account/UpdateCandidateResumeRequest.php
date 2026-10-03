<?php

namespace App\Http\Requests\Account;

use App\Enums\FileVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidateResumeRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:120'],
            'visibility' => ['nullable', Rule::enum(FileVisibility::class)],
        ];
    }
}
