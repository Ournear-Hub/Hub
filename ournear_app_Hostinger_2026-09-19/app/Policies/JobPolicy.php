<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function view(User $user, Job $job): bool
    {
        return $user->canManageRecruitment();
    }

    public function create(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function update(User $user, Job $job): bool
    {
        return $user->canManageRecruitment();
    }

    public function delete(User $user, Job $job): bool
    {
        return $user->canManageRecruitment();
    }
}
