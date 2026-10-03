<?php

namespace App\Services;

use App\Enums\ChannelPartnerStatus;
use App\Enums\CommissionEntryStatus;
use App\Enums\EmployerAttributionStatus;
use App\Enums\PartnerAgreementStatus;
use App\Enums\PaymentMethod;
use App\Enums\RecruitmentActivityType;
use App\Enums\SecurityActivityType;
use App\Models\ChannelPartner;
use App\Models\ChannelPartnerAgreement;
use App\Models\CommissionEntry;
use App\Models\CommissionPayment;
use App\Models\EmployerAttribution;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CommissionService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private AdminAlertService $alerts,
        private SecurityAuditService $audit,
    ) {}

    public function createFromPayment(Payment $payment, User $actor): ?CommissionEntry
    {
        $existing = CommissionEntry::query()->where('payment_id', $payment->id)->first();
        if ($existing instanceof CommissionEntry && $existing->status !== CommissionEntryStatus::Failed) {
            return $existing;
        }

        $context = $this->commissionContext($payment);
        if ($context === null) {
            return $existing;
        }

        [$partner, $agreement, $attribution, $employerId] = $context;

        $basis = Money::of($payment->amount);
        $rate = Money::of($agreement->commission_percent);
        $amount = Money::percent($basis, $rate);
        if (Money::cmp($amount, '0') <= 0) {
            return $existing;
        }

        return DB::transaction(function () use ($existing, $payment, $actor, $partner, $agreement, $attribution, $employerId, $basis, $rate, $amount) {
            $attributes = [
                'channel_partner_id' => $partner->id,
                'channel_partner_agreement_id' => $agreement->id,
                'employer_attribution_id' => $attribution->id,
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'employer_profile_id' => $employerId,
                'currency' => $payment->currency ?: 'INR',
                'basis_amount' => $basis,
                'rate' => $rate,
                'commission_amount' => $amount,
                'status' => CommissionEntryStatus::Pending,
                'window_starts_at' => $agreement->starts_at,
                'window_ends_at' => $agreement->ends_at,
                'notes' => null,
                'created_by' => $existing?->created_by ?? $actor->id,
            ];

            if ($existing instanceof CommissionEntry) {
                $existing->forceFill($attributes)->save();
                $entry = $existing;
            } else {
                $entry = CommissionEntry::query()->create($attributes);
            }

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::CommissionCreated,
                employer: $attribution->employer,
                invoice: $payment->invoice,
                payment: $payment,
                channelPartner: $partner,
                partnerAgreement: $agreement,
                commissionEntry: $entry,
                body: 'Commission '.$entry->business_id.' created from payment '.$payment->business_id.': '.$entry->currency.' '.$amount.'.',
                toStatus: CommissionEntryStatus::Pending->value,
            );

            return $entry->fresh() ?? $entry;
        });
    }

    public function approve(CommissionEntry $entry, User $actor): CommissionEntry
    {
        if (! $entry->status->canApprove()) {
            throw ValidationException::withMessages([
                'status' => 'Only a pending commission entry can be approved.',
            ]);
        }

        return DB::transaction(function () use ($entry, $actor) {
            $from = $entry->status->value;
            $entry->forceFill([
                'status' => CommissionEntryStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $actor->id,
            ])->save();

            $fresh = $entry->fresh(['partner', 'employer', 'payment', 'invoice', 'agreement']) ?? $entry;

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::CommissionApproved,
                employer: $fresh->employer,
                invoice: $fresh->invoice,
                payment: $fresh->payment,
                channelPartner: $fresh->partner,
                partnerAgreement: $fresh->agreement,
                commissionEntry: $fresh,
                body: 'Commission '.$fresh->business_id.' approved.',
                fromStatus: $from,
                toStatus: CommissionEntryStatus::Approved->value,
            );

            $this->audit->record(
                SecurityActivityType::CommissionApproved,
                'Commission '.$fresh->business_id.' was approved.',
                $actor,
                $fresh,
            );

            $this->alerts->commissionApproved($fresh);

            return $fresh;
        });
    }

    public function void(CommissionEntry $entry, User $actor, ?string $notes = null): CommissionEntry
    {
        if (! $entry->status->canVoid()) {
            throw ValidationException::withMessages([
                'status' => 'Only pending or approved commission entries can be voided.',
            ]);
        }

        return DB::transaction(function () use ($entry, $actor, $notes) {
            $from = $entry->status->value;
            $entry->forceFill([
                'status' => CommissionEntryStatus::Void,
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'notes' => $notes ?: $entry->notes,
            ])->save();

            $fresh = $entry->fresh(['partner', 'employer', 'payment', 'invoice', 'agreement']) ?? $entry;

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::CommissionVoided,
                employer: $fresh->employer,
                invoice: $fresh->invoice,
                payment: $fresh->payment,
                channelPartner: $fresh->partner,
                partnerAgreement: $fresh->agreement,
                commissionEntry: $fresh,
                body: 'Commission '.$fresh->business_id.' voided.',
                fromStatus: $from,
                toStatus: CommissionEntryStatus::Void->value,
            );

            $this->audit->record(
                SecurityActivityType::CommissionVoided,
                'Commission '.$fresh->business_id.' was voided.',
                $actor,
                $fresh,
            );

            return $fresh;
        });
    }

    /**
     * @param  array{
     *     channel_partner_id: int,
     *     entry_ids: list<int>,
     *     paid_at: string,
     *     method: string,
     *     reference?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function recordPayout(array $data, User $actor): CommissionPayment
    {
        $partner = ChannelPartner::query()->find($data['channel_partner_id']);
        if (! $partner instanceof ChannelPartner) {
            throw ValidationException::withMessages([
                'channel_partner_id' => 'The selected channel partner is invalid.',
            ]);
        }

        $method = $data['method'] instanceof PaymentMethod
            ? $data['method']
            : PaymentMethod::from((string) $data['method']);

        return DB::transaction(function () use ($data, $actor, $partner, $method) {
            $entries = CommissionEntry::query()
                ->whereIn('id', $data['entry_ids'])
                ->lockForUpdate()
                ->get();

            if ($entries->count() !== count(array_unique($data['entry_ids']))) {
                throw ValidationException::withMessages([
                    'entry_ids' => 'One or more commission entries are invalid.',
                ]);
            }

            foreach ($entries as $entry) {
                if ((int) $entry->channel_partner_id !== (int) $partner->id) {
                    throw ValidationException::withMessages([
                        'entry_ids' => 'All commission entries must belong to the same partner.',
                    ]);
                }
                if (! $entry->status->canPay()) {
                    throw ValidationException::withMessages([
                        'entry_ids' => 'Only approved commission entries can be paid.',
                    ]);
                }
            }

            $total = '0.00';
            foreach ($entries as $entry) {
                $total = Money::add($total, (string) $entry->commission_amount);
            }

            if (Money::cmp($total, '0') <= 0) {
                throw ValidationException::withMessages([
                    'entry_ids' => 'Payout amount must be greater than zero.',
                ]);
            }

            $payout = CommissionPayment::query()->create([
                'channel_partner_id' => $partner->id,
                'currency' => $entries->first()?->currency ?: 'INR',
                'amount' => $total,
                'paid_at' => $data['paid_at'],
                'method' => $method,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor->id,
            ]);

            foreach ($entries as $entry) {
                $entry->forceFill([
                    'status' => CommissionEntryStatus::Paid,
                    'commission_payment_id' => $payout->id,
                ])->save();

                $this->activities->record(
                    actor: $actor,
                    type: RecruitmentActivityType::CommissionPaid,
                    employer: $entry->employer,
                    invoice: $entry->invoice,
                    payment: $entry->payment,
                    channelPartner: $partner,
                    partnerAgreement: $entry->agreement,
                    commissionEntry: $entry,
                    body: 'Commission '.$entry->business_id.' paid on payout '.$payout->business_id.'.',
                    fromStatus: CommissionEntryStatus::Approved->value,
                    toStatus: CommissionEntryStatus::Paid->value,
                );
            }

            $fresh = $payout->fresh(['partner', 'entries']) ?? $payout;
            $this->alerts->commissionPaid($fresh);

            return $fresh;
        });
    }

    public function tryCreateFromPayment(Payment $payment, User $actor): ?CommissionEntry
    {
        try {
            return $this->createFromPayment($payment, $actor);
        } catch (\Throwable $exception) {
            Log::error('Commission entry could not be created from payment.', [
                'payment_id' => $payment->id,
                'message' => $exception->getMessage(),
            ]);

            return $this->persistFailed($payment, $actor, $exception);
        }
    }

    public function retry(CommissionEntry $entry, User $actor): CommissionEntry
    {
        if (! $entry->status->canRetry()) {
            throw ValidationException::withMessages([
                'status' => 'Only a failed commission entry can be retried.',
            ]);
        }

        $payment = $entry->payment ?? Payment::query()->find($entry->payment_id);
        if (! $payment instanceof Payment) {
            throw ValidationException::withMessages([
                'payment_id' => 'The original payment is missing, so this commission entry cannot be retried.',
            ]);
        }

        try {
            $result = $this->createFromPayment($payment, $actor);
        } catch (\Throwable $exception) {
            $this->persistFailed($payment, $actor, $exception);

            throw ValidationException::withMessages([
                'status' => 'Commission retry failed: '.$exception->getMessage(),
            ]);
        }

        if (! $result instanceof CommissionEntry || $result->status === CommissionEntryStatus::Failed) {
            throw ValidationException::withMessages([
                'status' => 'Commission retry could not create a pending entry. Check the partner attribution and agreement window.',
            ]);
        }

        return $result;
    }

    private function persistFailed(Payment $payment, User $actor, \Throwable $exception): ?CommissionEntry
    {
        $existing = CommissionEntry::query()->where('payment_id', $payment->id)->first();
        if ($existing instanceof CommissionEntry && $existing->status !== CommissionEntryStatus::Failed) {
            return $existing;
        }

        $context = $this->commissionContext($payment);
        if ($context === null || ! $payment->invoice_id) {
            return $existing;
        }

        [$partner, $agreement, $attribution, $employerId] = $context;
        $basis = Money::of($payment->amount);
        $rate = Money::of($agreement->commission_percent);
        $amount = Money::percent($basis, $rate);
        $notes = 'Commission creation failed: '.$exception->getMessage();

        $attributes = [
            'channel_partner_id' => $partner->id,
            'channel_partner_agreement_id' => $agreement->id,
            'employer_attribution_id' => $attribution->id,
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'employer_profile_id' => $employerId,
            'currency' => $payment->currency ?: 'INR',
            'basis_amount' => $basis,
            'rate' => $rate,
            'commission_amount' => Money::cmp($amount, '0') > 0 ? $amount : '0.00',
            'status' => CommissionEntryStatus::Failed,
            'window_starts_at' => $agreement->starts_at,
            'window_ends_at' => $agreement->ends_at,
            'notes' => $notes,
            'created_by' => $existing?->created_by ?? $actor->id,
        ];

        if ($existing instanceof CommissionEntry) {
            $existing->forceFill($attributes)->save();

            return $existing->fresh() ?? $existing;
        }

        return CommissionEntry::query()->create($attributes);
    }

    /**
     * @return array{0: ChannelPartner, 1: ChannelPartnerAgreement, 2: EmployerAttribution, 3: int}|null
     */
    private function commissionContext(Payment $payment): ?array
    {
        $payment->loadMissing(['invoice', 'employer']);
        $employerId = $payment->employer_profile_id ?? $payment->invoice?->employer_profile_id;
        if (! $employerId || ! $payment->invoice_id) {
            return null;
        }

        $attribution = EmployerAttribution::query()
            ->with(['partner', 'employer'])
            ->where('employer_profile_id', $employerId)
            ->where('status', EmployerAttributionStatus::Confirmed->value)
            ->first();

        if (! $attribution instanceof EmployerAttribution) {
            return null;
        }

        $partner = $attribution->partner;
        if (! $partner instanceof ChannelPartner || $partner->status !== ChannelPartnerStatus::Active) {
            return null;
        }

        $paidAt = $payment->paid_at?->toDateString() ?: now()->toDateString();
        $agreement = $this->agreementCovering($partner, $paidAt);
        if (! $agreement instanceof ChannelPartnerAgreement) {
            return null;
        }

        return [$partner, $agreement, $attribution, (int) $employerId];
    }

    private function agreementCovering(ChannelPartner $partner, string $paidAt): ?ChannelPartnerAgreement
    {
        return $partner->agreements
            ->first(function (ChannelPartnerAgreement $agreement) use ($paidAt) {
                return $agreement->status === PartnerAgreementStatus::Active
                    && $agreement->coversDate($paidAt);
            }) ?? ChannelPartnerAgreement::query()
            ->where('channel_partner_id', $partner->id)
            ->where('status', PartnerAgreementStatus::Active->value)
            ->get()
            ->first(fn (ChannelPartnerAgreement $agreement) => $agreement->coversDate($paidAt));
    }
}
