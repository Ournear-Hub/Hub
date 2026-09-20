<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\EmployerSubmission;
use App\Models\User;

class EmployerSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::SubmissionsView->value)
            || $user->hasPermission(PermissionName::SubmissionsManage->value);
    }

    public function view(User $user, EmployerSubmission $employerSubmission): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::SubmissionsManage->value);
    }

    public function update(User $user, EmployerSubmission $employerSubmission): bool
    {
        return $user->hasPermission(PermissionName::SubmissionsManage->value);
    }
}
