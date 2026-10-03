<?php

namespace App\Services;

use App\Enums\InvestmentOpportunityStatus;
use App\Enums\InvestorInterestStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\SecurityActivityType;
use App\Models\InvestmentOpportunity;
use App\Models\Investor;
use App\Models\InvestorInterest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestorInterestService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private AdminAlertService $alerts,
        private SecurityAuditService $audit,
    ) {}

    /**
     * @param  array{
     *     investor_id: int,
     *     investment_opportunity_id: int,
     *     status?: string|null,
     *     indicated_amount?: string|float|int|null,
     *     currency?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function record(array $data, User $actor): InvestorInterest
    {
        $investor = Investor::query()->findOrFail($data['investor_id']);
        $opportunity = InvestmentOpportunity::query()->findOrFail($data['investment_opportunity_id']);

        if (! $opportunity->acceptsInterest()) {
            throw ValidationException::withMessages([
                'investment_opportunity_id' => 'Interest can only be recorded on an open investment opportunity.',
            ]);
        }

        $exists = InvestorInterest::query()
            ->where('investor_id', $investor->id)
            ->where('investment_opportunity_id', $opportunity->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'investor_id' => 'This investor already has interest recorded on this opportunity.',
            ]);
        }

        $status = InvestorInterestStatus::tryFrom((string) ($data['status'] ?? InvestorInterestStatus::Interested->value))
            ?? InvestorInterestStatus::Interested;

        return DB::transaction(function () use ($data, $actor, $investor, $opportunity, $status) {
            $interest = InvestorInterest::query()->create([
                'investor_id' => $investor->id,
                'investment_opportunity_id' => $opportunity->id,
                'status' => $status,
                'indicated_amount' => $data['indicated_amount'] ?? null,
                'currency' => $data['currency'] ?? 'INR',
                'notes' => $data['notes'] ?? null,
                'committed_at' => $status === InvestorInterestStatus::Committed ? now() : null,
                'created_by' => $actor->id,
            ]);

            if ($opportunity->status === InvestmentOpportunityStatus::OpenForInterest) {
                $from = $opportunity->status->value;
                $opportunity->forceFill([
                    'status' => InvestmentOpportunityStatus::InterestReceived,
                ])->save();

                $this->activities->record(
                    actor: $actor,
                    type: RecruitmentActivityType::InvestmentOpportunityStageChanged,
                    investor: $investor,
                    investmentOpportunity: $opportunity,
                    investorInterest: $interest,
                    body: 'First interest received on '.$opportunity->title.'.',
                    fromStatus: $from,
                    toStatus: InvestmentOpportunityStatus::InterestReceived->value,
                );
            }

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::InvestorInterestRecorded,
                investor: $investor,
                investmentOpportunity: $opportunity,
                investorInterest: $interest,
                body: 'Interest recorded for '.$investor->display_name.' on '.$opportunity->title.'.',
                toStatus: $status->value,
            );

            if ($status === InvestorInterestStatus::Committed) {
                $this->audit->record(
                    SecurityActivityType::InvestorInterestCommitted,
                    'Non-binding investment intent recorded for '.$investor->display_name.' on '.$opportunity->title.'.',
                    $actor,
                    $interest,
                );
            }

            $this->alerts->opportunityInterestReceived($interest);

            return $interest->fresh(['investor', 'opportunity']) ?? $interest;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(InvestorInterest $interest, array $data, User $actor): InvestorInterest
    {
        $next = isset($data['status'])
            ? InvestorInterestStatus::tryFrom((string) $data['status'])
            : $interest->status;

        if (isset($data['status']) && ! $next) {
            throw ValidationException::withMessages([
                'status' => 'Select a valid interest status.',
            ]);
        }

        return DB::transaction(function () use ($interest, $data, $actor, $next) {
            $from = $interest->status->value;
            $becameCommitted = $next === InvestorInterestStatus::Committed && $interest->status !== InvestorInterestStatus::Committed;

            $interest->fill([
                'status' => $next,
                'indicated_amount' => array_key_exists('indicated_amount', $data) ? $data['indicated_amount'] : $interest->indicated_amount,
                'currency' => $data['currency'] ?? $interest->currency,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $interest->notes,
                'committed_at' => $next === InvestorInterestStatus::Committed
                    ? ($interest->committed_at ?? now())
                    : $interest->committed_at,
            ])->save();

            $fresh = $interest->fresh(['investor', 'opportunity']) ?? $interest;

            if ($from !== $fresh->status->value) {
                $this->activities->record(
                    actor: $actor,
                    type: RecruitmentActivityType::InvestorInterestStatusChanged,
                    investor: $fresh->investor,
                    investmentOpportunity: $fresh->opportunity,
                    investorInterest: $fresh,
                    body: 'Interest status updated for '.($fresh->investor?->display_name ?? 'an investor').'.',
                    fromStatus: $from,
                    toStatus: $fresh->status->value,
                );
            }

            if ($becameCommitted) {
                $this->audit->record(
                    SecurityActivityType::InvestorInterestCommitted,
                    'Non-binding investment intent recorded for '.($fresh->investor?->display_name ?? 'an investor').' on '.($fresh->opportunity?->title ?? 'an opportunity').'.',
                    $actor,
                    $fresh,
                );
            }

            return $fresh;
        });
    }
}
