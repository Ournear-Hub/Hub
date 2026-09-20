<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageUsers();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->canManageUsers()) {
            return true;
        }

        return (int) $user->id === (int) $model->id;
    }

    public function update(User $user, User $model): bool
    {
        if ($user->canManageUsers()) {
            return true;
        }

        return (int) $user->id === (int) $model->id;
    }
}
