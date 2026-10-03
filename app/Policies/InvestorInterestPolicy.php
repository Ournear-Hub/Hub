<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\InvestorInterest;
use App\Models\User;

class InvestorInterestPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, InvestorInterest $investorInterest): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, InvestorInterest $investorInterest): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        return $user->hasPermission(PermissionName::InvestorsView->value)
            || $user->hasPermission(PermissionName::InvestorsManage->value);
    }

    private function canManage(User $user): bool
    {
        return $user->hasPermission(PermissionName::InvestorsManage->value);
    }
}
