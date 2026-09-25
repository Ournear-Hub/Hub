<?php

namespace App\Http\Requests\Account;

use App\Enums\DocumentType;
use App\Enums\FileVisibility;
use App\Support\CandidateFileRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCandidateDocumentRequest extends FormRequest
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
            'file' => CandidateFileRules::document(),
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'title' => ['nullable', 'string', 'max:120'],
            'visibility' => ['nullable', Rule::enum(FileVisibility::class)],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(CandidateFileRules::documentMessages(), [
            'expiry_date.after_or_equal' => 'The expiry date cannot precede the issue date.',
        ]);
    }
}
