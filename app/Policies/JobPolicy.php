<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessHiringWorkspace()
            || $user->hasPermission(PermissionName::JobsView->value)
            || $user->hasPermission(PermissionName::JobsManage->value);
    }

    public function view(User $user, Job $job): bool
    {
        if ($user->hasPermission(PermissionName::JobsView->value)
            || $user->hasPermission(PermissionName::JobsManage->value)) {
            return true;
        }

        return $user->canAccessHiringWorkspace()
            && $user->employerProfile?->id === $job->employer_id;
    }

    public function create(User $user): bool
    {
        return ($user->canAccessHiringWorkspace() && $user->employerProfile !== null)
            || $user->hasPermission(PermissionName::JobsManage->value);
    }

    public function update(User $user, Job $job): bool
    {
        if ($user->hasPermission(PermissionName::JobsManage->value)) {
            return true;
        }

        return $user->canAccessHiringWorkspace()
            && $user->employerProfile?->id === $job->employer_id;
    }

    public function delete(User $user, Job $job): bool
    {
        return $this->update($user, $job);
    }
}
