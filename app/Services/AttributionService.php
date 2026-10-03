<?php

namespace App\Services;

use App\Enums\EmployerAttributionStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\SecurityActivityType;
use App\Models\ChannelPartner;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\EmployerAttribution;
use App\Models\EmployerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttributionService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private AdminAlertService $alerts,
        private SecurityAuditService $audit,
    ) {}

    /**
     * @param  array{
     *     channel_partner_id: int,
     *     employer_profile_id: int,
     *     crm_lead_id?: int|null,
     *     crm_opportunity_id?: int|null,
     *     notes?: string|null
     * }  $data
     */
    public function create(array $data, User $actor): EmployerAttribution
    {
        $partner = ChannelPartner::query()->find($data['channel_partner_id']);
        $employer = EmployerProfile::query()->find($data['employer_profile_id']);

        if (! $partner instanceof ChannelPartner) {
            throw ValidationException::withMessages([
                'channel_partner_id' => 'The selected channel partner is invalid.',
            ]);
        }

        if (! $employer instanceof EmployerProfile) {
            throw ValidationException::withMessages([
                'employer_profile_id' => 'The selected employer is invalid.',
            ]);
        }

        $this->assertNoConfirmedConflict($employer, $partner);

        return DB::transaction(function () use ($data, $actor, $partner, $employer) {
            $attribution = EmployerAttribution::query()->firstOrNew([
                'channel_partner_id' => $partner->id,
                'employer_profile_id' => $employer->id,
            ]);

            $attribution->fill([
                'crm_lead_id' => $data['crm_lead_id'] ?? $attribution->crm_lead_id,
                'crm_opportunity_id' => $data['crm_opportunity_id'] ?? $attribution->crm_opportunity_id,
                'status' => $attribution->exists && $attribution->isConfirmed()
                    ? EmployerAttributionStatus::Confirmed
                    : EmployerAttributionStatus::Pending,
                'notes' => $data['notes'] ?? $attribution->notes,
                'created_by' => $attribution->created_by ?? $actor->id,
            ])->save();

            return $attribution->fresh(['partner', 'employer', 'lead']) ?? $attribution;
        });
    }

    public function confirmFromConversion(
        ChannelPartner $partner,
        EmployerProfile $employer,
        CrmLead $lead,
        CrmOpportunity $opportunity,
        User $actor,
    ): EmployerAttribution {
        $this->assertNoConfirmedConflict($employer, $partner);

        return DB::transaction(function () use ($partner, $employer, $lead, $opportunity, $actor) {
            $attribution = EmployerAttribution::query()->firstOrNew([
                'channel_partner_id' => $partner->id,
                'employer_profile_id' => $employer->id,
            ]);

            $attribution->fill([
                'crm_lead_id' => $lead->id,
                'crm_opportunity_id' => $opportunity->id,
                'created_by' => $attribution->created_by ?? $actor->id,
            ]);

            return $this->markConfirmed($attribution, $actor, $employer, $partner, $lead, $opportunity);
        });
    }

    public function confirm(EmployerAttribution $attribution, User $actor): EmployerAttribution
    {
        $attribution->loadMissing(['partner', 'employer', 'lead', 'opportunity']);
        $employer = $attribution->employer;
        $partner = $attribution->partner;

        if (! $employer instanceof EmployerProfile || ! $partner instanceof ChannelPartner) {
            throw ValidationException::withMessages([
                'attribution' => 'This attribution is missing its partner or employer.',
            ]);
        }

        $this->assertNoConfirmedConflict($employer, $partner, $attribution->id);

        return DB::transaction(function () use ($attribution, $actor, $employer, $partner) {
            return $this->markConfirmed(
                $attribution,
                $actor,
                $employer,
                $partner,
                $attribution->lead,
                $attribution->opportunity,
            );
        });
    }

    public function dispute(EmployerAttribution $attribution, User $actor, ?string $notes = null): EmployerAttribution
    {
        if (! $attribution->isConfirmed()) {
            throw ValidationException::withMessages([
                'status' => 'Only a confirmed attribution can be disputed.',
            ]);
        }

        $attribution->forceFill([
            'status' => EmployerAttributionStatus::Disputed,
            'notes' => $notes ?: $attribution->notes,
        ])->save();

        $fresh = $attribution->fresh(['partner', 'employer', 'lead']) ?? $attribution;

        $this->activities->record(
            actor: $actor,
            type: RecruitmentActivityType::AttributionDisputed,
            employer: $fresh->employer,
            crmLead: $fresh->lead,
            channelPartner: $fresh->partner,
            body: 'Employer attribution disputed for '.($fresh->employer?->displayCompany() ?? 'employer').'.',
            fromStatus: EmployerAttributionStatus::Confirmed->value,
            toStatus: EmployerAttributionStatus::Disputed->value,
        );

        $this->alerts->attributionDisputed($fresh);

        return $fresh;
    }

    public function void(EmployerAttribution $attribution, User $actor): EmployerAttribution
    {
        $from = $attribution->status->value;
        $attribution->forceFill([
            'status' => EmployerAttributionStatus::Void,
        ])->save();

        return $attribution->fresh(['partner', 'employer']) ?? $attribution;
    }

    private function markConfirmed(
        EmployerAttribution $attribution,
        User $actor,
        EmployerProfile $employer,
        ChannelPartner $partner,
        ?CrmLead $lead,
        ?CrmOpportunity $opportunity,
    ): EmployerAttribution {
        $from = $attribution->status instanceof EmployerAttributionStatus
            ? $attribution->status->value
            : (string) $attribution->status;

        $attribution->forceFill([
            'status' => EmployerAttributionStatus::Confirmed,
            'confirmed_at' => $attribution->confirmed_at ?? now(),
            'confirmed_by' => $actor->id,
            'crm_lead_id' => $lead?->id ?? $attribution->crm_lead_id,
            'crm_opportunity_id' => $opportunity?->id ?? $attribution->crm_opportunity_id,
        ])->save();

        $fresh = $attribution->fresh(['partner', 'employer', 'lead']) ?? $attribution;

        $this->activities->record(
            actor: $actor,
            type: RecruitmentActivityType::AttributionConfirmed,
            employer: $employer,
            crmLead: $lead,
            crmOpportunity: $opportunity,
            channelPartner: $partner,
            body: 'Employer '.$employer->displayCompany().' attributed to partner '.$partner->display_name.'.',
            fromStatus: $from,
            toStatus: EmployerAttributionStatus::Confirmed->value,
        );

        $this->audit->record(
            SecurityActivityType::AttributionConfirmed,
            'Employer '.$employer->displayCompany().' attributed to '.$partner->display_name.'.',
            $actor,
            $employer,
            [
                'channel_partner_id' => $partner->id,
                'employer_attribution_id' => $fresh->id,
                'crm_lead_id' => $lead?->id,
            ],
        );

        return $fresh;
    }

    private function assertNoConfirmedConflict(
        EmployerProfile $employer,
        ChannelPartner $partner,
        ?int $ignoreId = null,
    ): void {
        $query = EmployerAttribution::query()
            ->where('employer_profile_id', $employer->id)
            ->where('status', EmployerAttributionStatus::Confirmed->value)
            ->where('channel_partner_id', '!=', $partner->id);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        $conflict = $query->with('partner:id,display_name')->first();
        if ($conflict instanceof EmployerAttribution) {
            throw ValidationException::withMessages([
                'employer_profile_id' => 'This employer is already confirmed to another channel partner. Do not merge companies; dispute or void the existing attribution first.',
                'channel_partner_id' => 'Conflicting confirmed attribution exists for partner '.($conflict->partner?->display_name ?? '#'.$conflict->channel_partner_id).'.',
            ]);
        }
    }
}
