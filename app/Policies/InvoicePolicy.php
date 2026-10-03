<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::FinanceView->value)
            || $user->hasPermission(PermissionName::FinanceManage->value);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::FinanceManage->value);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission(PermissionName::FinanceManage->value);
    }
}
