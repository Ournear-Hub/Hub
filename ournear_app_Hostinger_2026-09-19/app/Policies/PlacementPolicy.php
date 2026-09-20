<?php

namespace App\Policies;

use App\Models\Placement;
use App\Models\User;

class PlacementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function view(User $user, Placement $placement): bool
    {
        return $user->canManageRecruitment();
    }

    public function create(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function update(User $user, Placement $placement): bool
    {
        return $user->canManageRecruitment();
    }
}
