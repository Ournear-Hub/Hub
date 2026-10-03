<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\EmployerAttribution;
use App\Models\User;

class EmployerAttributionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, EmployerAttribution $attribution): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, EmployerAttribution $attribution): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployersView->value)
            || $user->hasPermission(PermissionName::EmployersManage->value);
    }

    private function canManage(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployersManage->value);
    }
}
