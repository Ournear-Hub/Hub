<?php

namespace App\Services;

use App\Enums\InvestmentOpportunityStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\SecurityActivityType;
use App\Models\InvestmentOpportunity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvestmentOpportunityService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private AdminAlertService $alerts,
        private SecurityAuditService $audit,
    ) {}

    /**
     * @param  array{
     *     title: string,
     *     summary?: string|null,
     *     sector?: string|null,
     *     location?: string|null,
     *     indicated_size?: string|float|int|null,
     *     currency?: string|null,
     *     opens_at?: string|null,
     *     closes_at?: string|null,
     *     notes?: string|null,
     *     status?: string|null
     * }  $data
     */
    public function create(array $data, User $actor): InvestmentOpportunity
    {
        $status = InvestmentOpportunityStatus::tryFrom((string) ($data['status'] ?? InvestmentOpportunityStatus::Draft->value))
            ?? InvestmentOpportunityStatus::Draft;

        $opportunity = InvestmentOpportunity::query()->create([
            'status' => $status,
            'title' => trim((string) $data['title']),
            'summary' => $data['summary'] ?? null,
            'sector' => $data['sector'] ?? null,
            'location' => $data['location'] ?? null,
            'indicated_size' => $data['indicated_size'] ?? null,
            'currency' => $data['currency'] ?? 'INR',
            'opens_at' => $data['opens_at'] ?? null,
            'closes_at' => $data['closes_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $actor->id,
        ]);

        $this->activities->record(
            actor: $actor,
            type: RecruitmentActivityType::InvestmentOpportunityCreated,
            investmentOpportunity: $opportunity,
            body: 'Investment opportunity created: '.$opportunity->title,
            toStatus: $opportunity->status->value,
        );

        return $opportunity->fresh() ?? $opportunity;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(InvestmentOpportunity $opportunity, array $data, User $actor): InvestmentOpportunity
    {
        $opportunity->fill([
            'title' => $data['title'] ?? $opportunity->title,
            'summary' => array_key_exists('summary', $data) ? $data['summary'] : $opportunity->summary,
            'sector' => array_key_exists('sector', $data) ? $data['sector'] : $opportunity->sector,
            'location' => array_key_exists('location', $data) ? $data['location'] : $opportunity->location,
            'indicated_size' => array_key_exists('indicated_size', $data) ? $data['indicated_size'] : $opportunity->indicated_size,
            'currency' => $data['currency'] ?? $opportunity->currency,
            'opens_at' => array_key_exists('opens_at', $data) ? $data['opens_at'] : $opportunity->opens_at,
            'closes_at' => array_key_exists('closes_at', $data) ? $data['closes_at'] : $opportunity->closes_at,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $opportunity->notes,
        ])->save();

        return $opportunity->fresh() ?? $opportunity;
    }

    public function changeStage(InvestmentOpportunity $opportunity, string $status, User $actor): InvestmentOpportunity
    {
        $next = InvestmentOpportunityStatus::tryFrom($status);
        if (! $next) {
            throw ValidationException::withMessages([
                'status' => 'Select a valid investment opportunity stage.',
            ]);
        }

        if ($opportunity->status === $next) {
            return $opportunity;
        }

        return DB::transaction(function () use ($opportunity, $next, $actor) {
            $from = $opportunity->status->value;
            $opportunity->forceFill(['status' => $next])->save();
            $fresh = $opportunity->fresh() ?? $opportunity;

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::InvestmentOpportunityStageChanged,
                investmentOpportunity: $fresh,
                body: 'Investment opportunity '.$fresh->title.' moved to '.$fresh->status->label().'.',
                fromStatus: $from,
                toStatus: $next->value,
            );

            if ($next === InvestmentOpportunityStatus::OpenForInterest) {
                $this->audit->record(
                    SecurityActivityType::InvestmentOpportunityOpened,
                    'Investment opportunity '.$fresh->title.' was opened for interest.',
                    $actor,
                    $fresh,
                );
            }

            $this->alerts->investmentOpportunityStageChanged($fresh);

            return $fresh;
        });
    }
}
