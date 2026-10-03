<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\LoginLog;
use App\Models\User;

class LoginLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::SecurityView->value);
    }

    public function view(User $user, LoginLog $loginLog): bool
    {
        return $this->viewAny($user);
    }
}
