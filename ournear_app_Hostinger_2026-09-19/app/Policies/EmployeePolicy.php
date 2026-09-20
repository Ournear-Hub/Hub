<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->canManageRecruitment();
    }

    public function create(User $user): bool
    {
        return $user->canManageRecruitment();
    }
}
