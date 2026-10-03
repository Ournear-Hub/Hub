<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\RecruitmentAutomation;
use App\Models\User;

class RecruitmentAutomationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, RecruitmentAutomation $automation): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, RecruitmentAutomation $automation): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasPermission(PermissionName::AutomationView->value)
            || $user->hasPermission(PermissionName::AutomationManage->value);
    }

    private function canManage(User $user): bool
    {
        return $user->hasPermission(PermissionName::AutomationManage->value);
    }
}
