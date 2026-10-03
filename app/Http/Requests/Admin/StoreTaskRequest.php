<?php

namespace App\Http\Requests\Admin;

use App\Models\Task;
use App\Support\StaffOptions;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Task::class) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
            'assigned_to' => StaffOptions::assignedToRules(),
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'job_id' => ['nullable', 'integer', 'exists:job_listings,id'],
            'candidate_profile_id' => ['nullable', 'integer', 'exists:candidate_profiles,id'],
            'employer_profile_id' => ['nullable', 'integer', 'exists:employer_profiles,id'],
            'employer_enquiry_id' => ['nullable', 'integer', 'exists:employer_enquiries,id'],
            'crm_lead_id' => ['nullable', 'integer', 'exists:crm_leads,id'],
            'crm_opportunity_id' => ['nullable', 'integer', 'exists:crm_opportunities,id'],
            'channel_partner_id' => ['nullable', 'integer', 'exists:channel_partners,id'],
            'commission_entry_id' => ['nullable', 'integer', 'exists:commission_entries,id'],
            'investor_id' => ['nullable', 'integer', 'exists:investors,id'],
            'investment_opportunity_id' => ['nullable', 'integer', 'exists:investment_opportunities,id'],
            'investor_interest_id' => ['nullable', 'integer', 'exists:investor_interests,id'],
            'campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'referral_link_id' => ['nullable', 'integer', 'exists:referral_links,id'],
            'referral_id' => ['nullable', 'integer', 'exists:referrals,id'],
        ];
    }
}
