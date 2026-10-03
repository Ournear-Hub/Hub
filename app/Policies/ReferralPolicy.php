<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Referral;
use App\Models\User;

class ReferralPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::CampaignsView->value)
            || $user->hasPermission(PermissionName::CampaignsManage->value);
    }

    public function view(User $user, Referral $referral): bool
    {
        return $this->viewAny($user);
    }
}
