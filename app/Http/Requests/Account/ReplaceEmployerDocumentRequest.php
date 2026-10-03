<?php

namespace App\Http\Requests\Account;

use App\Support\EmployerFileRules;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceEmployerDocumentRequest extends FormRequest
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
