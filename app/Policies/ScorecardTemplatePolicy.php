<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\ScorecardTemplate;
use App\Models\User;

class ScorecardTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, ScorecardTemplate $template): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, ScorecardTemplate $template): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasPermission(PermissionName::ApplicationsView->value)
            || $user->hasPermission(PermissionName::ApplicationsManage->value);
    }

    private function canManage(User $user): bool
    {
        return $user->hasPermission(PermissionName::ApplicationsManage->value);
    }
}
