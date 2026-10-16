<?php

namespace App\Services;

use App\Enums\JobPublishingStatus;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Unverified employers: one published trial listing (one hour) tracked by trial_job_used_at.
 * Verified employers (email + mobile, or Admin approval): unlimited published jobs.
 */
class EmployerJobPublicationPolicy
{
    public function __construct(
        private EmployerPostingEligibilityService $eligibility,
    ) {}

    /**
     * @return array{posting_tier: string, trial_expires_at: \Illuminate\Support\Carbon|null, mark_trial_used: bool}
     */
    public function packageForPublish(EmployerProfile $profile, ?Job $jobBeingPublished = null): array
    {
        $this->assertCanPublish($profile, $jobBeingPublished);
        $user = $profile->user;
        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'posting' => 'Employer account is not available for publishing.',
            ]);
        }

        if ($this->eligibility->allowsUnlimited($user)) {
            return [
                'posting_tier' => 'basic',
                'trial_expires_at' => null,
                'mark_trial_used' => false,
            ];
        }

        if ($jobBeingPublished instanceof Job
            && $jobBeingPublished->publishing_status === JobPublishingStatus::Published
            && $jobBeingPublished->posting_tier === 'trial') {
            return [
                'posting_tier' => 'trial',
                'trial_expires_at' => $jobBeingPublished->trial_expires_at,
                'mark_trial_used' => false,
            ];
        }

        return [
            'posting_tier' => 'trial',
            'trial_expires_at' => now()->addHour(),
            'mark_trial_used' => true,
        ];
    }

    public function assertCanPublish(EmployerProfile $profile, ?Job $jobBeingPublished = null): void
    {
        $user = $profile->user;
        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'posting' => 'Employer account is not available for publishing.',
            ]);
        }

        if ($this->eligibility->allowsUnlimited($user)) {
            return;
        }

        if ($jobBeingPublished instanceof Job
            && $jobBeingPublished->publishing_status === JobPublishingStatus::Published
            && $jobBeingPublished->posting_tier === 'trial') {
            return;
        }

        if ($profile->trial_job_used_at === null) {
            return;
        }

        throw ValidationException::withMessages([
            'posting' => 'Verify your email and mobile number to publish additional jobs. Your one-hour trial allowance has already been used.',
        ]);
    }

    /**
     * After full publishing eligibility, remove the one-hour cap on still-published trial jobs without unpublishing.
     */
    public function liftTrialExpiryForVerifiedEmployer(EmployerProfile $profile): void
    {
        $user = $profile->user;
        if (! $user instanceof User || ! $this->eligibility->allowsUnlimited($user)) {
            return;
        }

        Job::query()
            ->where('employer_id', $profile->id)
            ->where('posting_tier', 'trial')
            ->where('publishing_status', JobPublishingStatus::Published)
            ->whereNotNull('trial_expires_at')
            ->update([
                'posting_tier' => 'basic',
                'trial_expires_at' => null,
            ]);
    }
}
