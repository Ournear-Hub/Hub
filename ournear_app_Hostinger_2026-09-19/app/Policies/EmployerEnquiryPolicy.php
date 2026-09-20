<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\EmployerEnquiry;
use App\Models\User;

class EmployerEnquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::EmployersView->value)
            || $user->hasPermission(PermissionName::EmployersManage->value);
    }

    public function view(User $user, EmployerEnquiry $employerEnquiry): bool
    {
        if ($this->viewAny($user)) {
            return true;
        }

        return $user->isEmployer()
            && $employerEnquiry->user_id !== null
            && (int) $employerEnquiry->user_id === (int) $user->id;
    }

    public function update(User $user, EmployerEnquiry $employerEnquiry): bool
    {
        return $user->hasPermission(PermissionName::EmployersManage->value);
    }
}
