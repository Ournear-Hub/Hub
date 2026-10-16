<?php

namespace App\Services;

use App\Enums\JobPublishingStatus;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;

class EmployerPostingEligibilityService
{
    /**
     * @return array{
     *     eligible: bool,
     *     unlimited_publishing: bool,
     *     trial_available: bool,
     *     trial_active: bool,
     *     trial_expired: bool,
     *     trial_expires_at: string|null,
     *     workspace_ready: bool,
     *     email_verified: bool,
     *     phone_verified: bool,
     *     admin_approved: bool,
     *     restricted: bool,
     *     reason: string|null,
     *     next_action: string|null
     * }
     */
    public function for(User $user): array
    {
        $user->unsetRelation('employerProfile');
        $profile = $user->employerProfile()->first();
        $emailVerified = $user->emailVerified();
        $phoneVerified = $user->mobileVerified();
        $adminApproved = $profile instanceof EmployerProfile && $profile->ournearVerified();
        $restricted = $profile instanceof EmployerProfile && $profile->postingRestricted();
        $workspaceReady = $this->workspaceReady($user);
        $unlimited = ! $restricted && ($adminApproved || ($emailVerified && $phoneVerified));
        $trialAvailable = ! $restricted && $workspaceReady && $profile instanceof EmployerProfile && $profile->trial_job_used_at === null;
        $trial = $this->trialState($profile);
        $eligible = $unlimited || $trialAvailable;

        $reason = null;
        $nextAction = null;
        if (! $eligible) {
            if ($restricted) {
                $reason = 'Job posting has been restricted by OurNear.';
                $nextAction = 'Contact OurNear if you believe this restriction should be reviewed.';
            } elseif (! $workspaceReady) {
                $reason = 'Activate your employer workspace with company name, phone, and email before publishing.';
                $nextAction = 'Complete employer activation on the hiring dashboard.';
            } elseif ($trial['expired'] || $profile?->trial_job_used_at !== null) {
                $reason = 'Your one-hour trial job allowance has been used. Verify your email and mobile number to publish additional jobs.';
                $nextAction = $emailVerified
                    ? 'Complete mobile verification in Verification settings.'
                    : 'Verify your email and mobile number in Verification settings.';
            } else {
                $reason = 'Job publishing is not available yet.';
                $nextAction = 'Complete employer setup or verification.';
            }
        }

        return [
            'eligible' => $eligible,
            'unlimited_publishing' => $unlimited,
            'trial_available' => $trialAvailable,
            'trial_active' => $trial['active'],
            'trial_expired' => $trial['expired'],
            'trial_expires_at' => $trial['expires_at'],
            'workspace_ready' => $workspaceReady,
            'email_verified' => $emailVerified,
            'phone_verified' => $phoneVerified,
            'admin_approved' => $adminApproved,
            'restricted' => $restricted,
            'reason' => $reason,
            'next_action' => $nextAction,
        ];
    }

    public function allows(User $user): bool
    {
        return $this->for($user)['eligible'];
    }

    public function allowsUnlimited(User $user): bool
    {
        return $this->for($user)['unlimited_publishing'];
    }

    public function workspaceReady(User $user): bool
    {
        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;
        if (! $profile instanceof EmployerProfile) {
            return false;
        }

        if (trim((string) $profile->company_name) === '') {
            return false;
        }

        if (trim((string) $profile->mobile_number) === '') {
            return false;
        }

        return trim($user->hiringEmail()) !== '';
    }

    public function syncPublishingBenefits(User $user): void
    {
        if (! $this->allowsUnlimited($user)) {
            return;
        }

        $profile = $user->employerProfile;
        if ($profile instanceof EmployerProfile) {
            app(EmployerJobPublicationPolicy::class)->liftTrialExpiryForVerifiedEmployer($profile);
        }
    }

    /**
     * @return array{active: bool, expired: bool, expires_at: string|null}
     */
    private function trialState(?EmployerProfile $profile): array
    {
        if (! $profile instanceof EmployerProfile || $profile->trial_job_used_at === null) {
            return ['active' => false, 'expired' => false, 'expires_at' => null];
        }

        $activeJob = Job::query()
            ->where('employer_id', $profile->id)
            ->where('posting_tier', 'trial')
            ->where('publishing_status', JobPublishingStatus::Published)
            ->where('trial_expires_at', '>', now())
            ->orderByDesc('trial_expires_at')
            ->first();

        if ($activeJob instanceof Job) {
            return [
                'active' => true,
                'expired' => false,
                'expires_at' => $activeJob->trial_expires_at?->toIso8601String(),
            ];
        }

        return ['active' => false, 'expired' => true, 'expires_at' => null];
    }
}
