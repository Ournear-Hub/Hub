<?php

namespace App\Services;

use App\Enums\ChannelPartnerStatus;
use App\Enums\PartnerAgreementStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\SecurityActivityType;
use App\Models\ChannelPartner;
use App\Models\ChannelPartnerAgreement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChannelPartnerService
{
    public function __construct(
        private UserManagementService $users,
        private RecruitmentActivityService $activities,
        private AdminAlertService $alerts,
        private SecurityAuditService $audit,
    ) {}

    /**
     * @param  array{
     *     name?: string,
     *     email: string,
     *     mobile?: string|null,
     *     display_name: string,
     *     contact_person?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function create(array $data, User $actor): ChannelPartner
    {
        return DB::transaction(function () use ($data, $actor) {
            $displayName = trim((string) $data['display_name']);
            $user = $this->users->createPartnerIdentity($actor, [
                'name' => $data['name'] ?? ($data['contact_person'] ?? $displayName),
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
            ]);

            $partner = ChannelPartner::query()->create([
                'user_id' => $user->id,
                'status' => ChannelPartnerStatus::Pending,
                'display_name' => $displayName,
                'contact_person' => $data['contact_person'] ?? $user->name,
                'email' => strtolower(trim($data['email'])),
                'mobile' => $data['mobile'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::PartnerCreated,
                channelPartner: $partner,
                body: 'Channel partner created: '.$partner->display_name,
                toStatus: ChannelPartnerStatus::Pending->value,
            );

            return $partner->fresh(['user', 'agreements']) ?? $partner;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ChannelPartner $partner, array $data, User $actor): ChannelPartner
    {
        $partner->fill([
            'display_name' => $data['display_name'] ?? $partner->display_name,
            'contact_person' => array_key_exists('contact_person', $data) ? $data['contact_person'] : $partner->contact_person,
            'email' => array_key_exists('email', $data) ? $data['email'] : $partner->email,
            'mobile' => array_key_exists('mobile', $data) ? $data['mobile'] : $partner->mobile,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $partner->notes,
        ])->save();

        if ($partner->user && (isset($data['contact_person']) || isset($data['display_name']) || isset($data['mobile']))) {
            $partner->user->fill([
                'name' => $data['contact_person'] ?? $data['display_name'] ?? $partner->user->name,
                'mobile' => array_key_exists('mobile', $data) ? $data['mobile'] : $partner->user->mobile,
            ])->save();
        }

        return $partner->fresh(['user', 'agreements']) ?? $partner;
    }

    public function approve(ChannelPartner $partner, User $actor): ChannelPartner
    {
        if ($partner->status === ChannelPartnerStatus::Active) {
            return $partner;
        }

        $hasActiveAgreement = $partner->agreements()
            ->where('status', PartnerAgreementStatus::Active->value)
            ->exists();

        if (! $hasActiveAgreement) {
            throw ValidationException::withMessages([
                'status' => 'Activate a partner agreement before approving this channel partner.',
            ]);
        }

        return DB::transaction(function () use ($partner, $actor) {
            $from = $partner->status->value;
            $partner->forceFill([
                'status' => ChannelPartnerStatus::Active,
                'approved_at' => $partner->approved_at ?? now(),
                'approved_by' => $actor->id,
                'suspended_at' => null,
                'suspended_by' => null,
            ])->save();

            $fresh = $partner->fresh(['user', 'agreements']) ?? $partner;

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::PartnerApproved,
                channelPartner: $fresh,
                body: 'Channel partner approved: '.$fresh->display_name,
                fromStatus: $from,
                toStatus: ChannelPartnerStatus::Active->value,
            );

            $this->audit->record(
                SecurityActivityType::PartnerApproved,
                'Channel partner '.$fresh->display_name.' was approved.',
                $actor,
                $fresh,
            );

            return $fresh;
        });
    }

    public function suspend(ChannelPartner $partner, User $actor): ChannelPartner
    {
        if ($partner->status === ChannelPartnerStatus::Suspended) {
            return $partner;
        }

        return DB::transaction(function () use ($partner, $actor) {
            $from = $partner->status->value;
            $partner->forceFill([
                'status' => ChannelPartnerStatus::Suspended,
                'suspended_at' => now(),
                'suspended_by' => $actor->id,
            ])->save();

            $fresh = $partner->fresh(['user']) ?? $partner;

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::PartnerSuspended,
                channelPartner: $fresh,
                body: 'Channel partner suspended: '.$fresh->display_name,
                fromStatus: $from,
                toStatus: ChannelPartnerStatus::Suspended->value,
            );

            $this->audit->record(
                SecurityActivityType::PartnerSuspended,
                'Channel partner '.$fresh->display_name.' was suspended.',
                $actor,
                $fresh,
            );

            return $fresh;
        });
    }

    /**
     * @param  array{
     *     commission_percent?: string|float|int|null,
     *     duration_months?: int|null,
     *     starts_at?: string|null,
     *     ends_at?: string|null,
     *     terms?: string|null
     * }  $data
     */
    public function createAgreement(ChannelPartner $partner, array $data, User $actor): ChannelPartnerAgreement
    {
        $percent = $data['commission_percent'] ?? ChannelPartnerAgreement::DEFAULT_COMMISSION_PERCENT;
        $months = (int) ($data['duration_months'] ?? ChannelPartnerAgreement::DEFAULT_DURATION_MONTHS);

        if ($months < 1 || $months > 36) {
            throw ValidationException::withMessages([
                'duration_months' => 'Agreement duration must be between 1 and 36 months.',
            ]);
        }

        return ChannelPartnerAgreement::query()->create([
            'channel_partner_id' => $partner->id,
            'status' => PartnerAgreementStatus::Draft,
            'commission_percent' => $percent,
            'duration_months' => $months,
            'eligible_basis' => ChannelPartnerAgreement::ELIGIBLE_BASIS_RECEIVED_PAYMENTS,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'terms' => $data['terms'] ?? null,
            'created_by' => $actor->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAgreement(ChannelPartnerAgreement $agreement, array $data, User $actor): ChannelPartnerAgreement
    {
        if ($agreement->status === PartnerAgreementStatus::Active) {
            throw ValidationException::withMessages([
                'status' => 'An active agreement cannot be edited. Create a replacement agreement instead.',
            ]);
        }

        $agreement->fill([
            'commission_percent' => $data['commission_percent'] ?? $agreement->commission_percent,
            'duration_months' => $data['duration_months'] ?? $agreement->duration_months,
            'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $agreement->starts_at,
            'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $agreement->ends_at,
            'terms' => array_key_exists('terms', $data) ? $data['terms'] : $agreement->terms,
        ])->save();

        return $agreement->fresh(['partner']) ?? $agreement;
    }

    public function activateAgreement(ChannelPartnerAgreement $agreement, User $actor): ChannelPartnerAgreement
    {
        return DB::transaction(function () use ($agreement, $actor) {
            $partner = ChannelPartner::query()->lockForUpdate()->findOrFail($agreement->channel_partner_id);

            ChannelPartnerAgreement::query()
                ->where('channel_partner_id', $partner->id)
                ->where('status', PartnerAgreementStatus::Active->value)
                ->whereKeyNot($agreement->id)
                ->update(['status' => PartnerAgreementStatus::Expired->value]);

            $startsAt = $agreement->starts_at?->toDateString() ?: now()->toDateString();
            $endsAt = $agreement->ends_at?->toDateString()
                ?: \Illuminate\Support\Carbon::parse($startsAt)->addMonths((int) $agreement->duration_months)->toDateString();

            $from = $agreement->status->value;
            $agreement->forceFill([
                'status' => PartnerAgreementStatus::Active,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'approved_at' => $agreement->approved_at ?? now(),
                'approved_by' => $actor->id,
            ])->save();

            $fresh = $agreement->fresh(['partner']) ?? $agreement;

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::PartnerAgreementActivated,
                channelPartner: $partner,
                partnerAgreement: $fresh,
                body: 'Partner agreement activated for '.$partner->display_name.' at '.$fresh->commission_percent.'% for '.$fresh->duration_months.' months.',
                fromStatus: $from,
                toStatus: PartnerAgreementStatus::Active->value,
            );

            $this->alerts->partnerAgreementConfirmed($fresh);

            return $fresh;
        });
    }
}
