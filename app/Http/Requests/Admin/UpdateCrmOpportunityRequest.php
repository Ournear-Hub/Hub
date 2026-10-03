<?php

namespace App\Http\Requests\Admin;

use App\Enums\CrmOpportunityStatus;
use App\Models\CrmOpportunity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCrmOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $opportunity = $this->route('opportunity');

        return $opportunity instanceof CrmOpportunity
            && $this->user()?->can('update', $opportunity) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'required', Rule::enum(CrmOpportunityStatus::class)],
        ];
    }
}
