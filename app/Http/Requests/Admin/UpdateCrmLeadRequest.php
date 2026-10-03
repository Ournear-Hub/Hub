<?php

namespace App\Http\Requests\Admin;

use App\Models\CrmLead;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCrmLeadRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'channel_partner_id' => ['nullable', 'integer', 'exists:channel_partners,id'],
            'confirm_duplicates' => ['sometimes', 'boolean'],
        ];
    }
}
