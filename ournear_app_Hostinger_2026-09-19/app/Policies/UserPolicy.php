<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Enums\StaffRoleSlug;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(PermissionName::UsersView->value)
            || $user->hasPermission(PermissionName::UsersManage->value);
    }

    public function view(User $user, User $model): bool
    {
        if ($this->viewAny($user)) {
            return true;
        }

        return (int) $user->id === (int) $model->id;
    }

    public function viewPhoto(User $user, User $model): bool
    {
        if ((int) $user->id === (int) $model->id) {
            return true;
        }

        if (! $user->isAdmin()) {
            return false;
        }

        if ($this->viewAny($user)) {
            return true;
        }

        if ($model->isCandidate() && $user->hasPermission(PermissionName::CandidatesView->value)) {
            return true;
        }

        return $model->isEmployer() && $user->hasPermission(PermissionName::EmployersView->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(PermissionName::UsersManage->value);
    }

    public function update(User $user, User $model): bool
    {
        if ((int) $user->id === (int) $model->id) {
            return true;
        }

        return $user->hasPermission(PermissionName::UsersManage->value);
    }

    public function assignRoles(User $user, User $model): bool
    {
        if (! $user->hasPermission(PermissionName::UsersManage->value) || ! $model->isAdmin()) {
            return false;
        }

        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    public function deactivate(User $user, User $model): bool
    {
        if (! $user->hasPermission(PermissionName::UsersManage->value)) {
            return false;
        }

        if ((int) $user->id === (int) $model->id) {
            return false;
        }

        if ($model->hasStaffRole(StaffRoleSlug::SuperAdmin->value) && ! $user->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    public function sendPasswordReset(User $user, User $model): bool
    {
        return $this->assignRoles($user, $model) || (
            $user->hasPermission(PermissionName::UsersManage->value) && $model->isAdmin()
        );
    }
}
