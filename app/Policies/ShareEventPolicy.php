<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\ShareEvent;
use App\Models\User;

class ShareEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::CampaignsView->value)
            || $user->hasPermission(PermissionName::CampaignsManage->value);
    }

    public function view(User $user, ShareEvent $shareEvent): bool
    {
        return $this->viewAny($user);
    }
}
