<?php

namespace App\Services;

use App\Enums\RecruitmentActivityType;
use App\Enums\ShareChannel;
use App\Models\Job;
use App\Models\ShareEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShareTrackingService
{
    public function __construct(
        private ReferralAttributionService $referrals,
        private RecruitmentActivityService $activities,
    ) {}

    public function recordJobShare(Job $job, string $channel, Request $request): ShareEvent
    {
        $shareChannel = ShareChannel::tryFrom($channel);
        if (! $shareChannel) {
            throw ValidationException::withMessages([
                'channel' => 'Select a supported share channel.',
            ]);
        }

        $link = $this->referrals->resolveLink($request);
        $user = $request->user();

        $event = ShareEvent::query()->create([
            'channel' => $shareChannel,
            'path' => '/jobs/'.$job->slug,
            'job_id' => $job->id,
            'referral_link_id' => $link?->id,
            'campaign_id' => $link?->campaign_id,
            'user_id' => $user instanceof User ? $user->id : null,
        ]);

        if ($user instanceof User) {
            $this->activities->record(
                actor: $user,
                type: RecruitmentActivityType::ShareRecorded,
                campaign: $link?->campaign,
                referralLink: $link,
                shareEvent: $event,
                body: $user->name.' shared '.$job->title.' via '.$shareChannel->label().'.',
            );
        }

        return $event;
    }
}
