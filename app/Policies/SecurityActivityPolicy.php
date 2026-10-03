<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\SecurityActivity;
use App\Models\User;

class SecurityActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::SecurityView->value);
    }

    public function view(User $user, SecurityActivity $securityActivity): bool
    {
        return $this->viewAny($user);
    }
}
