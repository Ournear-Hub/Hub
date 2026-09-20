<?php

namespace App\Policies;

use App\Models\EmployerSubmission;
use App\Models\User;

class EmployerSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function view(User $user, EmployerSubmission $employerSubmission): bool
    {
        return $user->canManageRecruitment();
    }

    public function create(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function update(User $user, EmployerSubmission $employerSubmission): bool
    {
        return $user->canManageRecruitment();
    }
}
