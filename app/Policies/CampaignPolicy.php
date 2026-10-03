<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Campaign $campaign): bool
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
