<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\CrmLead;
use App\Models\User;

class CrmLeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, CrmLead $crmLead): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, CrmLead $crmLead): bool
    {
        return $this->canManage($user);
    }

    public function convert(User $user, CrmLead $crmLead): bool
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
