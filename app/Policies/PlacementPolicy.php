<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Placement;
use App\Models\User;

class PlacementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::PlacementsView->value)
            || $user->hasPermission(PermissionName::PlacementsManage->value);
    }

    public function view(User $user, Placement $placement): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::PlacementsManage->value);
    }

    public function update(User $user, Placement $placement): bool
    {
        return $user->hasPermission(PermissionName::PlacementsManage->value);
    }
}
