<?php

namespace App\Policies;

use App\Models\EmployerProfile;
use App\Models\User;

class EmployerProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function view(User $user, EmployerProfile $employerProfile): bool
    {
        if ($user->canManageRecruitment()) {
            return true;
        }

        return $user->isEmployer() && (int) $employerProfile->user_id === (int) $user->id;
    }

    public function update(User $user, EmployerProfile $employerProfile): bool
    {
        return $user->canManageRecruitment();
    }
}
