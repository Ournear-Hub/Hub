<?php

namespace App\Http\Requests\Admin;

use App\Models\Replacement;
use Illuminate\Foundation\Http\FormRequest;

class IdentifyReplacementCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $replacement = $this->route('replacement');

        return $replacement instanceof Replacement
            && $this->user()?->can('update', $replacement) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'application_id' => ['required', 'integer', 'exists:applications,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
