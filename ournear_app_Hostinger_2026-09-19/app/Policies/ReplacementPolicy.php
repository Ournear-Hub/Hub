<?php

namespace App\Policies;

use App\Models\Replacement;
use App\Models\User;

class ReplacementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function view(User $user, Replacement $replacement): bool
    {
        return $user->canManageRecruitment();
    }

    public function create(User $user): bool
    {
        return $user->canManageRecruitment();
    }

    public function update(User $user, Replacement $replacement): bool
    {
        return $user->canManageRecruitment();
    }
}
