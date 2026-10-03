<?php

namespace App\Services;

use App\Enums\ReferralLinkStatus;
use App\Enums\RecruitmentActivityType;
use App\Models\Campaign;
use App\Models\Job;
use App\Models\ReferralLink;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReferralLinkService
{
    public function __construct(
        private RecruitmentActivityService $activities,
    ) {}

    /**
     * @param  array{
     *     path?: string|null,
     *     campaign_id?: int|null,
     *     job_id?: int|null,
     *     referrer_user_id?: int|null,
     *     notes?: string|null,
     *     code?: string|null
     * }  $data
     */
    public function create(array $data, User $actor): ReferralLink
    {
        $job = isset($data['job_id']) ? Job::query()->find($data['job_id']) : null;
        $path = $this->normalizePath((string) ($data['path'] ?? ''), $job);

        if (isset($data['campaign_id']) && $data['campaign_id']) {
            Campaign::query()->findOrFail($data['campaign_id']);
        }

        $link = ReferralLink::query()->create([
            'code' => $this->uniqueCode($data['code'] ?? null),
            'status' => ReferralLinkStatus::Active,
            'path' => $path,
            'campaign_id' => $data['campaign_id'] ?? null,
            'job_id' => $job?->id,
            'referrer_user_id' => $data['referrer_user_id'] ?? null,
            'created_by' => $actor->id,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->activities->record(
            actor: $actor,
            type: RecruitmentActivityType::ReferralLinkCreated,
            campaign: $link->campaign,
            referralLink: $link,
            body: 'Referral link '.$link->code.' created for '.$link->path.'.',
        );

        return $link->fresh(['campaign', 'job']) ?? $link;
    }

    public function disable(ReferralLink $link, User $actor): ReferralLink
    {
        if ($link->status === ReferralLinkStatus::Disabled) {
            return $link;
        }

        $link->loadMissing('campaign');
        $link->forceFill(['status' => ReferralLinkStatus::Disabled])->save();
        $fresh = $link->fresh(['campaign']) ?? $link;

        $this->activities->record(
            actor: $actor,
            type: RecruitmentActivityType::ReferralLinkDisabled,
            campaign: $fresh->campaign,
            referralLink: $fresh,
            body: 'Referral link '.$fresh->code.' was disabled.',
        );

        return $fresh;
    }

    private function uniqueCode(?string $requested): string
    {
        $code = $requested ? strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper($requested)) ?? '') : '';
        if ($code !== '' && ! ReferralLink::query()->where('code', $code)->exists()) {
            return $code;
        }

        do {
            $code = strtoupper(Str::random(8));
        } while (ReferralLink::query()->where('code', $code)->exists());

        return $code;
    }

    private function normalizePath(string $path, ?Job $job): string
    {
        if ($job) {
            return '/jobs/'.$job->slug;
        }

        $path = '/'.ltrim(trim($path), '/');
        if ($path === '/') {
            return '/';
        }

        $allowed = [
            '/jobs',
            '/register',
            '/login',
            '/companies',
            '/profiles',
            '/about',
            '/services',
            '/for-employers',
            '/contact',
            '/freelancers',
        ];

        foreach ($allowed as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                if (str_starts_with($path, '/admin')) {
                    break;
                }

                return $path;
            }
        }

        throw ValidationException::withMessages([
            'path' => 'Referral links can only point to public pages.',
        ]);
    }
}
