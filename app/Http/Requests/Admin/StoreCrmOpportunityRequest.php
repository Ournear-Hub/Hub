<?php

namespace App\Http\Requests\Admin;

use App\Models\CrmLead;
use Illuminate\Foundation\Http\FormRequest;

class StoreCrmOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead instanceof CrmLead
            && $this->user()?->can('update', $lead) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
