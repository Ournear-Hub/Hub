<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::RolesManage->value);
    }

    public function view(User $user, Role $role): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::RolesManage->value);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermission(PermissionName::RolesManage->value) && ! $role->is_locked;
    }
}
