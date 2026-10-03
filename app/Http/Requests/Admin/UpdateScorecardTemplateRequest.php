<?php

namespace App\Http\Requests\Admin;

use App\Models\ScorecardTemplate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateScorecardTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('scorecardTemplate');

        return $template instanceof ScorecardTemplate
            && $this->user()?->can('update', $template) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.key' => ['nullable', 'string', 'max:64'],
            'criteria.*.label' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
