<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::JobsView->value)
            || $user->hasPermission(PermissionName::JobsManage->value);
    }

    public function view(User $user, Job $job): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::JobsManage->value);
    }

    public function update(User $user, Job $job): bool
    {
        return $user->hasPermission(PermissionName::JobsManage->value);
    }

    public function delete(User $user, Job $job): bool
    {
        return $user->hasPermission(PermissionName::JobsManage->value);
    }
}
