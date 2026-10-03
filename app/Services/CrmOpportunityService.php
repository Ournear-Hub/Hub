<?php

namespace App\Services;

use App\Enums\CrmOpportunityStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\SecurityActivityType;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\EmployerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrmOpportunityService
{
    public function __construct(
        private CompanyDuplicateService $duplicates,
        private CrmLeadService $leads,
        private UserManagementService $users,
        private AttributionService $attributions,
        private RecruitmentActivityService $activities,
        private AdminAlertService $alerts,
        private SecurityAuditService $audit,
    ) {}

    /**
     * @param  array{title?: string|null, notes?: string|null, assigned_to?: int|null}  $data
     */
    public function create(CrmLead $lead, array $data, User $actor): CrmOpportunity
    {
        $opportunity = CrmOpportunity::query()->create([
            'crm_lead_id' => $lead->id,
            'employer_profile_id' => $lead->employer_profile_id,
            'title' => $data['title'] ?? ($lead->company_name.' opportunity'),
            'status' => CrmOpportunityStatus::NewLead,
            'notes' => $data['notes'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? $lead->assigned_to,
            'created_by' => $actor->id,
        ]);

        $this->activities->record(
            actor: $actor,
            type: RecruitmentActivityType::CrmOpportunityCreated,
            enquiry: $lead->enquiry,
            employer: $lead->employer,
            crmLead: $lead,
            crmOpportunity: $opportunity,
            body: 'CRM opportunity created: '.$opportunity->title,
            toStatus: CrmOpportunityStatus::NewLead->value,
        );

        return $opportunity->fresh(['lead', 'assignee', 'employer']) ?? $opportunity;
    }

    /**
     * @param  array{status: string, notes?: string|null}  $data
     */
    public function changeStatus(CrmOpportunity $opportunity, array $data, User $actor): CrmOpportunity
    {
        $status = $data['status'] instanceof CrmOpportunityStatus
            ? $data['status']
            : CrmOpportunityStatus::from((string) $data['status']);

        if ($status->requiresEmployer() && ! $opportunity->employer_profile_id && ! $opportunity->lead?->employer_profile_id) {
            throw ValidationException::withMessages([
                'status' => 'Converted and Active Client require a linked EmployerProfile. Convert the opportunity first.',
            ]);
        }

        $from = $opportunity->status instanceof CrmOpportunityStatus
            ? $opportunity->status->value
            : (string) $opportunity->status;

        $opportunity->fill([
            'status' => $status,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $opportunity->notes,
            'converted_at' => $status === CrmOpportunityStatus::Converted
                ? ($opportunity->converted_at ?? now())
                : $opportunity->converted_at,
        ])->save();

        $fresh = $opportunity->fresh(['lead', 'employer', 'assignee']) ?? $opportunity;

        $this->activities->record(
            actor: $actor,
            type: RecruitmentActivityType::CrmOpportunityStageChanged,
            enquiry: $fresh->lead?->enquiry,
            employer: $fresh->employer ?? $fresh->lead?->employer,
            crmLead: $fresh->lead,
            crmOpportunity: $fresh,
            body: 'CRM opportunity moved from '.$from.' to '.$status->value.'.',
            fromStatus: $from,
            toStatus: $status->value,
        );

        $this->alerts->crmOpportunityStageChanged($fresh);

        return $fresh;
    }

    /**
     * @param  array{
     *     employer_profile_id?: int|null,
     *     create_employer?: bool,
     *     email?: string|null,
     *     confirm_duplicates?: bool
     * }  $data
     */
    public function convert(CrmOpportunity $opportunity, array $data, User $actor): CrmOpportunity
    {
        $lead = $opportunity->lead ?? $opportunity->lead()->first();
        if (! $lead instanceof CrmLead) {
            throw ValidationException::withMessages([
                'crm_lead_id' => 'This opportunity is missing its lead.',
            ]);
        }

        $employer = $this->resolveEmployer($lead, $data, $actor);

        return DB::transaction(function () use ($opportunity, $lead, $employer, $actor) {
            $from = $opportunity->status instanceof CrmOpportunityStatus
                ? $opportunity->status->value
                : (string) $opportunity->status;

            $this->leads->linkEmployer($lead, $employer);
            $lead->refresh();

            if ($lead->channel_partner_id) {
                $lead->loadMissing('partner');
                if ($lead->partner) {
                    $this->attributions->confirmFromConversion(
                        $lead->partner,
                        $employer,
                        $lead,
                        $opportunity,
                        $actor,
                    );
                }
            }

            $opportunity->forceFill([
                'employer_profile_id' => $employer->id,
                'status' => CrmOpportunityStatus::Converted,
                'converted_at' => $opportunity->converted_at ?? now(),
            ])->save();

            $fresh = $opportunity->fresh(['lead.employer', 'employer', 'assignee']) ?? $opportunity;

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::CrmConverted,
                enquiry: $lead->enquiry,
                employer: $employer,
                crmLead: $lead,
                crmOpportunity: $fresh,
                body: 'CRM opportunity converted to employer '.$employer->displayCompany().'.',
                fromStatus: $from,
                toStatus: CrmOpportunityStatus::Converted->value,
            );

            $this->audit->record(
                SecurityActivityType::CrmConverted,
                'CRM opportunity converted to employer '.$employer->displayCompany().'.',
                $actor,
                $employer,
                [
                    'crm_lead_id' => $lead->id,
                    'crm_opportunity_id' => $fresh->id,
                    'employer_profile_id' => $employer->id,
                ],
            );

            $this->alerts->crmConverted($fresh);

            return $fresh;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CrmOpportunity $opportunity, array $data, User $actor): CrmOpportunity
    {
        $previousAssignee = $opportunity->assigned_to;

        $opportunity->fill([
            'title' => $data['title'] ?? $opportunity->title,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $opportunity->notes,
            'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $opportunity->assigned_to,
        ])->save();

        if (isset($data['status'])) {
            return $this->changeStatus($opportunity, $data, $actor);
        }

        $fresh = $opportunity->fresh(['lead', 'employer', 'assignee']) ?? $opportunity;

        if ($fresh->assigned_to && (int) $fresh->assigned_to !== (int) $previousAssignee) {
            $this->alerts->crmOpportunityAssigned($fresh);
        }

        return $fresh;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveEmployer(CrmLead $lead, array $data, User $actor): EmployerProfile
    {
        if (! empty($data['employer_profile_id'])) {
            $employer = EmployerProfile::query()->with('user')->find($data['employer_profile_id']);
            if (! $employer instanceof EmployerProfile) {
                throw ValidationException::withMessages([
                    'employer_profile_id' => 'The selected employer profile is invalid.',
                ]);
            }

            return $employer;
        }

        if (! ($data['create_employer'] ?? false)) {
            throw ValidationException::withMessages([
                'employer_profile_id' => 'Link an existing employer or create a new EmployerProfile.',
            ]);
        }

        $email = $this->conversionEmail($lead, $data);
        $existing = User::query()
            ->where('email', $email)
            ->with('employerProfile')
            ->first();

        if ($existing instanceof User && $existing->isEmployer() && $existing->employerProfile instanceof EmployerProfile) {
            throw ValidationException::withMessages([
                'create_employer' => 'An employer already uses this email. Link the existing EmployerProfile instead of creating another company.',
                'employer_profile_id' => 'Link employer #'.$existing->employerProfile->id.' instead of creating a new account.',
            ]);
        }

        if ($existing instanceof User) {
            throw ValidationException::withMessages([
                'email' => 'This email already belongs to an existing account. Link that employer if one exists.',
            ]);
        }

        $this->duplicates->assertClear(
            $lead->company_name,
            $email,
            $lead->website,
            $lead->mobile,
            (bool) ($data['confirm_duplicates'] ?? false),
            $lead->id,
        );

        return $this->users->createEmployerClient($actor, [
            'name' => $lead->contact_person ?: $lead->company_name,
            'email' => $email,
            'mobile' => $lead->mobile,
            'company_name' => $lead->company_name,
            'contact_person' => $lead->contact_person,
            'mobile_number' => $lead->mobile,
            'company_website' => $lead->website,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function conversionEmail(CrmLead $lead, array $data): string
    {
        $email = strtolower(trim((string) ($data['email'] ?? $lead->email ?? '')));
        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'An email is required to create an employer account.',
            ]);
        }

        return $email;
    }
}
