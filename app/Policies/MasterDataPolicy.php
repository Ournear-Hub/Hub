<?php

namespace App\Policies;

use App\Models\User;

class MasterDataPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageMasterData();
    }

    public function view(User $user, object $record): bool
    {
        return $user->canManageMasterData();
    }

    public function create(User $user): bool
    {
        return $user->canManageMasterData();
    }

    public function update(User $user, object $record): bool
    {
        return $user->canManageMasterData();
    }

    public function delete(User $user, object $record): bool
    {
        return false;
    }
}
