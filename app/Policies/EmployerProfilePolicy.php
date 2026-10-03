<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\EmployerProfile;
use App\Models\User;

class EmployerProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployersView->value)
            || $user->hasPermission(PermissionName::EmployersManage->value);
    }

    public function view(User $user, EmployerProfile $employerProfile): bool
    {
        if ($this->viewAny($user)) {
            return true;
        }

        return $user->canAccessHiringWorkspace() && (int) $employerProfile->user_id === (int) $user->id;
    }

    public function update(User $user, EmployerProfile $employerProfile): bool
    {
        return $user->hasPermission(PermissionName::EmployersManage->value);
    }
}
