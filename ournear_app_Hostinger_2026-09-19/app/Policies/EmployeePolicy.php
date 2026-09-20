<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployeesView->value)
            || $user->hasPermission(PermissionName::EmployeesManage->value)
            || $user->hasPermission(PermissionName::FinanceView->value);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployeesManage->value);
    }
}
