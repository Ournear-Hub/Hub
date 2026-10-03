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
        if ($this->viewAny($user)) {
            return true;
        }

        return $user->canAccessHiringWorkspace()
            && (int) $user->employerProfile?->id === (int) $employerSubmission->employer_profile_id;
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
