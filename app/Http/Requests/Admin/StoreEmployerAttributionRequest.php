<?php

namespace App\Http\Requests\Admin;

use App\Models\EmployerAttribution;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployerAttributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmployerAttribution::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel_partner_id' => ['required', 'integer', 'exists:channel_partners,id'],
            'employer_profile_id' => ['required', 'integer', 'exists:employer_profiles,id'],
            'crm_lead_id' => ['nullable', 'integer', 'exists:crm_leads,id'],
            'crm_opportunity_id' => ['nullable', 'integer', 'exists:crm_opportunities,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
