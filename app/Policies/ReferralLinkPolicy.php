<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\ReferralLink;
use App\Models\User;

class ReferralLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, ReferralLink $referralLink): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, ReferralLink $referralLink): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasPermission(PermissionName::CampaignsView->value)
            || $user->hasPermission(PermissionName::CampaignsManage->value);
    }

    private function canManage(User $user): bool
    {
        return $user->hasPermission(PermissionName::CampaignsManage->value);
    }
}
