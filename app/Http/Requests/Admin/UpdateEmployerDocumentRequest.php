<?php

namespace App\Http\Requests\Admin;

use App\Enums\EmployerDocumentType;
use App\Enums\FileLifecycleStatus;
use App\Models\EmployerDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document instanceof EmployerDocument
            && $this->user()?->can('update', $document) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['nullable', Rule::enum(EmployerDocumentType::class)],
            'title' => ['nullable', 'string', 'max:120'],
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
