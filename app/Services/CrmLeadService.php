<?php

namespace App\Services;

use App\Enums\ChannelPartnerStatus;
use App\Enums\CrmLeadSource;
use App\Enums\CrmOpportunityStatus;
use App\Enums\RecruitmentActivityType;
use App\Models\ChannelPartner;
use App\Models\ContactEnquiry;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrmLeadService
{
    public function __construct(
        private CompanyDuplicateService $duplicates,
        private RecruitmentActivityService $activities,
        private AdminAlertService $alerts,
    ) {}

    /**
     * @param  array{
     *     company_name: string,
     *     contact_person?: string|null,
     *     email?: string|null,
     *     mobile?: string|null,
     *     website?: string|null,
     *     notes?: string|null,
     *     assigned_to?: int|null,
     *     employer_enquiry_id?: int|null,
     *     contact_enquiry_id?: int|null,
     *     employer_profile_id?: int|null,
     *     channel_partner_id?: int|null,
     *     source?: string|null,
     *     confirm_duplicates?: bool
     * }  $data
     */
    public function create(array $data, User $actor): CrmLead
    {
        $partner = $this->resolvePartner($data['channel_partner_id'] ?? null);

        $this->duplicates->assertClear(
            $data['company_name'] ?? null,
            $data['email'] ?? null,
            $data['website'] ?? null,
            $data['mobile'] ?? null,
            (bool) ($data['confirm_duplicates'] ?? false),
        );

        $lead = DB::transaction(function () use ($data, $actor, $partner) {
            $source = $data['source'] ?? ($partner ? CrmLeadSource::ChannelPartner : CrmLeadSource::Manual);
            $source = $source instanceof CrmLeadSource
                ? $source
                : CrmLeadSource::from((string) $source);

            if ($partner && $source === CrmLeadSource::Manual) {
                $source = CrmLeadSource::ChannelPartner;
            }

            $lead = CrmLead::query()->create([
                'company_name' => $data['company_name'],
                'contact_person' => $data['contact_person'] ?? null,
                'email' => $this->normalizeEmail($data['email'] ?? null),
                'mobile' => $data['mobile'] ?? null,
                'website' => $data['website'] ?? null,
                'source' => $source,
                'notes' => $data['notes'] ?? null,
                'employer_enquiry_id' => $data['employer_enquiry_id'] ?? null,
                'contact_enquiry_id' => $data['contact_enquiry_id'] ?? null,
                'employer_profile_id' => $data['employer_profile_id'] ?? null,
                'channel_partner_id' => $partner?->id,
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => $actor->id,
            ]);

            $opportunity = CrmOpportunity::query()->create([
                'crm_lead_id' => $lead->id,
                'employer_profile_id' => $lead->employer_profile_id,
                'title' => $lead->company_name.' opportunity',
                'status' => CrmOpportunityStatus::NewLead,
                'assigned_to' => $lead->assigned_to,
                'created_by' => $actor->id,
            ]);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::CrmLeadCreated,
                enquiry: $lead->enquiry,
                employer: $lead->employer,
                crmLead: $lead,
                crmOpportunity: $opportunity,
                body: 'CRM lead created: '.$lead->company_name,
            );

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::CrmOpportunityCreated,
                enquiry: $lead->enquiry,
                employer: $lead->employer,
                crmLead: $lead,
                crmOpportunity: $opportunity,
                body: 'CRM opportunity created at New Lead.',
                toStatus: CrmOpportunityStatus::NewLead->value,
            );

            return $lead->fresh(['opportunities', 'assignee', 'enquiry', 'employer', 'partner']) ?? $lead;
        });

        if ($lead->assigned_to) {
            $this->alerts->crmLeadAssigned($lead);
        }

        return $lead;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function createFromEnquiry(EmployerEnquiry $enquiry, User $actor, array $overrides = []): CrmLead
    {
        if (CrmLead::query()->where('employer_enquiry_id', $enquiry->id)->exists()) {
            throw ValidationException::withMessages([
                'employer_enquiry_id' => 'A CRM lead already exists for this employer enquiry.',
            ]);
        }

        return $this->create([
            'company_name' => $overrides['company_name'] ?? $enquiry->company_name,
            'contact_person' => $overrides['contact_person'] ?? $enquiry->contact_person,
            'email' => $overrides['email'] ?? $enquiry->email,
            'mobile' => $overrides['mobile'] ?? $enquiry->mobile_number,
            'notes' => $overrides['notes'] ?? $enquiry->additional_requirements,
            'assigned_to' => $overrides['assigned_to'] ?? null,
            'employer_enquiry_id' => $enquiry->id,
            'channel_partner_id' => $overrides['channel_partner_id'] ?? null,
            'source' => CrmLeadSource::EmployerEnquiry,
            'confirm_duplicates' => (bool) ($overrides['confirm_duplicates'] ?? false),
        ], $actor);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function createFromContactEnquiry(ContactEnquiry $enquiry, User $actor, array $overrides = []): CrmLead
    {
        if (CrmLead::query()->where('contact_enquiry_id', $enquiry->id)->exists()) {
            throw ValidationException::withMessages([
                'contact_enquiry_id' => 'A CRM lead already exists for this contact enquiry.',
            ]);
        }

        return $this->create([
            'company_name' => $overrides['company_name'] ?? ($enquiry->full_name ?: 'Contact enquiry'),
            'contact_person' => $overrides['contact_person'] ?? $enquiry->full_name,
            'email' => $overrides['email'] ?? $enquiry->email,
            'mobile' => $overrides['mobile'] ?? $enquiry->mobile_number,
            'notes' => $overrides['notes'] ?? $enquiry->message,
            'assigned_to' => $overrides['assigned_to'] ?? null,
            'contact_enquiry_id' => $enquiry->id,
            'channel_partner_id' => $overrides['channel_partner_id'] ?? null,
            'source' => CrmLeadSource::ContactEnquiry,
            'confirm_duplicates' => (bool) ($overrides['confirm_duplicates'] ?? false),
        ], $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CrmLead $lead, array $data, User $actor): CrmLead
    {
        $this->duplicates->assertClear(
            $data['company_name'] ?? $lead->company_name,
            array_key_exists('email', $data) ? $data['email'] : $lead->email,
            array_key_exists('website', $data) ? $data['website'] : $lead->website,
            array_key_exists('mobile', $data) ? $data['mobile'] : $lead->mobile,
            (bool) ($data['confirm_duplicates'] ?? false),
            $lead->id,
            $lead->employer_profile_id,
        );

        $previousAssignee = $lead->assigned_to;

        $partner = array_key_exists('channel_partner_id', $data)
            ? $this->resolvePartner($data['channel_partner_id'])
            : null;

        $lead->fill([
            'company_name' => $data['company_name'] ?? $lead->company_name,
            'contact_person' => array_key_exists('contact_person', $data) ? $data['contact_person'] : $lead->contact_person,
            'email' => array_key_exists('email', $data) ? $this->normalizeEmail($data['email']) : $lead->email,
            'mobile' => array_key_exists('mobile', $data) ? $data['mobile'] : $lead->mobile,
            'website' => array_key_exists('website', $data) ? $data['website'] : $lead->website,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $lead->notes,
            'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $lead->assigned_to,
            'channel_partner_id' => array_key_exists('channel_partner_id', $data)
                ? $partner?->id
                : $lead->channel_partner_id,
        ])->save();

        if ($partner && $lead->source === CrmLeadSource::Manual) {
            $lead->forceFill(['source' => CrmLeadSource::ChannelPartner])->save();
        }

        $fresh = $lead->fresh(['opportunities', 'assignee', 'enquiry', 'employer', 'partner']) ?? $lead;

        if ($fresh->assigned_to && (int) $fresh->assigned_to !== (int) $previousAssignee) {
            $this->alerts->crmLeadAssigned($fresh);
        }

        return $fresh;
    }

    public function assign(CrmLead $lead, ?int $assignedTo, User $actor): CrmLead
    {
        return $this->update($lead, ['assigned_to' => $assignedTo], $actor);
    }

    public function linkEmployer(CrmLead $lead, EmployerProfile $employer): CrmLead
    {
        $lead->forceFill(['employer_profile_id' => $employer->id])->save();
        $lead->opportunities()->whereNull('employer_profile_id')->update([
            'employer_profile_id' => $employer->id,
        ]);

        return $lead->fresh(['opportunities', 'employer']) ?? $lead;
    }

    private function resolvePartner(mixed $partnerId): ?ChannelPartner
    {
        if ($partnerId === null || $partnerId === '') {
            return null;
        }

        $partner = ChannelPartner::query()->find((int) $partnerId);
        if (! $partner instanceof ChannelPartner) {
            throw ValidationException::withMessages([
                'channel_partner_id' => 'The selected channel partner is invalid.',
            ]);
        }

        if ($partner->status === ChannelPartnerStatus::Suspended) {
            throw ValidationException::withMessages([
                'channel_partner_id' => 'A suspended channel partner cannot be attributed to a lead.',
            ]);
        }

        return $partner;
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' ? $email : null;
    }
}
