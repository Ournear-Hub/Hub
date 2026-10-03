<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\CommissionEntry;
use App\Models\User;

class CommissionEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, CommissionEntry $entry): bool
    {
        return $this->canView($user);
    }

    public function update(User $user, CommissionEntry $entry): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasPermission(PermissionName::FinanceView->value)
            || $user->hasPermission(PermissionName::FinanceManage->value);
    }

    private function canManage(User $user): bool
    {
        return $user->hasPermission(PermissionName::FinanceManage->value);
    }
}
