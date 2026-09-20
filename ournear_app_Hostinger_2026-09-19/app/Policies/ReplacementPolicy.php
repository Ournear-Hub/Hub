<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Replacement;
use App\Models\User;

class ReplacementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::ReplacementsView->value)
            || $user->hasPermission(PermissionName::ReplacementsManage->value);
    }

    public function view(User $user, Replacement $replacement): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::ReplacementsManage->value);
    }

    public function update(User $user, Replacement $replacement): bool
    {
        return $user->hasPermission(PermissionName::ReplacementsManage->value);
    }
}
