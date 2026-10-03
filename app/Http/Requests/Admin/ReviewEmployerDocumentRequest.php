<?php

namespace App\Http\Requests\Admin;

use App\Models\EmployerDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewEmployerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document instanceof EmployerDocument
            && $this->user()?->can('verify', $document) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['verify', 'reject'])],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
