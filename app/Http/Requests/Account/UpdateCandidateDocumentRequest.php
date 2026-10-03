<?php

namespace App\Http\Requests\Account;

use App\Enums\DocumentType;
use App\Enums\FileLifecycleStatus;
use App\Enums\FileVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidateDocumentRequest extends FormRequest
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
            'document_type' => ['nullable', Rule::enum(DocumentType::class)],
            'title' => ['nullable', 'string', 'max:120'],
            'visibility' => ['nullable', Rule::enum(FileVisibility::class)],
            'status' => ['nullable', Rule::enum(FileLifecycleStatus::class)],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'expiry_date.after_or_equal' => 'The expiry date cannot precede the issue date.',
        ];
    }
}
