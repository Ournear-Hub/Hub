<?php

namespace App\Http\Requests\Admin;

use App\Models\EmployerDocument;
use App\Support\EmployerFileRules;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceEmployerDocumentRequest extends FormRequest
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
            'file' => EmployerFileRules::document(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return EmployerFileRules::documentMessages();
    }
}
