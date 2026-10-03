<?php

namespace App\Services;

use App\Models\EmployerProfile;
use App\Models\User;

class EmployerPostingEligibilityService
{
    /**
     * @return array{
     *     eligible: bool,
     *     email_verified: bool,
     *     phone_verified: bool,
     *     admin_approved: bool,
     *     reason: string|null,
     *     next_action: string|null
     * }
     */
    public function for(User $user): array
    {
        $user->load('employerProfile');
        $profile = $user->employerProfile;
        $emailVerified = $user->emailVerified();
        $phoneVerified = $user->mobile_verified_at !== null;
        $adminApproved = $profile instanceof EmployerProfile && $profile->ournearVerified();
        $restricted = $profile instanceof EmployerProfile && $profile->postingRestricted();
        $eligible = ! $restricted && (($emailVerified && $phoneVerified) || $adminApproved);

        return [
            'eligible' => $eligible,
            'email_verified' => $emailVerified,
            'phone_verified' => $phoneVerified,
            'admin_approved' => $adminApproved,
            'restricted' => $restricted,
            'reason' => $eligible
                ? null
                : ($restricted
                    ? 'Job posting has been restricted by OurNear.'
                    : 'Job posting requires both email and phone verification, or Admin approval.'),
            'next_action' => $eligible
                ? null
                : ($restricted
                    ? 'Contact OurNear if you believe this restriction should be reviewed.'
                    : ($emailVerified
                        ? 'Complete phone verification or contact Admin for approval.'
                        : 'Verify your email and phone, or contact Admin for approval.')),
        ];
    }

    public function allows(User $user): bool
    {
        return $this->for($user)['eligible'];
    }
}
