<?php

namespace App\Http\Requests\Admin;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        return $campaign instanceof Campaign && $this->user()?->can('update', $campaign) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['summary', 'notes', 'starts_at', 'ends_at'] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
