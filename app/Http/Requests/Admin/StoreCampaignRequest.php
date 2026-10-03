<?php

namespace App\Http\Requests\Admin;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Campaign::class) === true;
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
            'status' => ['nullable', Rule::enum(CampaignStatus::class)],
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
