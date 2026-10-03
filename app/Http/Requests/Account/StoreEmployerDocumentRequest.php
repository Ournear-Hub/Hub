<?php

namespace App\Http\Requests\Account;

use App\Enums\EmployerDocumentType;
use App\Support\EmployerFileRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessHiringWorkspace() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => EmployerFileRules::document(),
            'document_type' => ['required', Rule::enum(EmployerDocumentType::class)],
            'title' => ['nullable', 'string', 'max:120'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(EmployerFileRules::documentMessages(), [
            'expiry_date.after_or_equal' => 'The expiry date cannot precede the issue date.',
        ]);
    }
}
