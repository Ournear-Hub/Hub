<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\CommissionPayment;
use App\Models\User;

class CommissionPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, CommissionPayment $payment): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
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
