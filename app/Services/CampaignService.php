<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\SecurityActivityType;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CampaignService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private AdminAlertService $alerts,
        private SecurityAuditService $audit,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     summary?: string|null,
     *     notes?: string|null,
     *     starts_at?: string|null,
     *     ends_at?: string|null,
     *     status?: string|null
     * }  $data
     */
    public function create(array $data, User $actor): Campaign
    {
        $status = CampaignStatus::tryFrom((string) ($data['status'] ?? CampaignStatus::Draft->value))
            ?? CampaignStatus::Draft;

        $campaign = Campaign::query()->create([
            'status' => $status,
            'name' => trim((string) $data['name']),
            'summary' => $data['summary'] ?? null,
            'notes' => $data['notes'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'created_by' => $actor->id,
        ]);

        $this->activities->record(
            actor: $actor,
            type: RecruitmentActivityType::CampaignCreated,
            campaign: $campaign,
            body: 'Campaign created: '.$campaign->name,
            toStatus: $campaign->status->value,
        );

        return $campaign->fresh() ?? $campaign;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Campaign $campaign, array $data, User $actor): Campaign
    {
        $campaign->fill([
            'name' => array_key_exists('name', $data) ? trim((string) $data['name']) : $campaign->name,
            'summary' => array_key_exists('summary', $data) ? $data['summary'] : $campaign->summary,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $campaign->notes,
            'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $campaign->starts_at,
            'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $campaign->ends_at,
        ]);

        if (! $campaign->isDirty()) {
            return $campaign;
        }

        $campaign->save();
        $fresh = $campaign->fresh() ?? $campaign;

        $this->activities->record(
            actor: $actor,
            type: RecruitmentActivityType::CampaignUpdated,
            campaign: $fresh,
            body: 'Campaign '.$fresh->name.' was updated.',
        );

        return $fresh;
    }

    public function changeStatus(Campaign $campaign, string $status, User $actor): Campaign
    {
        $next = CampaignStatus::tryFrom($status);
        if (! $next) {
            throw ValidationException::withMessages([
                'status' => 'Select a valid campaign status.',
            ]);
        }

        if ($campaign->status === $next) {
            return $campaign;
        }

        return DB::transaction(function () use ($campaign, $next, $actor) {
            $from = $campaign->status->value;
            $campaign->forceFill(['status' => $next])->save();
            $fresh = $campaign->fresh() ?? $campaign;

            $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::CampaignStatusChanged,
                campaign: $fresh,
                body: 'Campaign '.$fresh->name.' moved to '.$fresh->status->label().'.',
                fromStatus: $from,
                toStatus: $next->value,
            );

            if ($next === CampaignStatus::Active) {
                $this->audit->record(
                    SecurityActivityType::CampaignActivated,
                    'Campaign '.$fresh->name.' was activated.',
                    $actor,
                    $fresh,
                );
                $this->alerts->campaignActivated($fresh);
            }

            return $fresh;
        });
    }
}
