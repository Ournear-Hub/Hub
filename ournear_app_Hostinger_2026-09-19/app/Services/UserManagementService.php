<?php

namespace App\Services;

use App\Enums\SecurityActivityType;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    public function __construct(
        private SecurityAuditService $audit,
        private LoginLogService $loginLogs,
    ) {}

    /**
     * @param  list<int>  $roleIds
     */
    public function createStaff(User $actor, string $name, string $email, array $roleIds): User
    {
        return DB::transaction(function () use ($actor, $name, $email, $roleIds) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Str::password(32),
                'role' => UserRole::Admin,
            ]);
            $user->forceFill([
                'is_active' => true,
                'account_status' => \App\Enums\AccountStatus::Active,
                'must_change_password' => true,
            ])->save();

            $this->syncRoles($actor, $user, $roleIds, audit: false);

            $this->audit->record(
                SecurityActivityType::UserCreated,
                'Staff user '.$user->email.' was created.',
                $actor,
                $user,
                ['role_ids' => $roleIds],
            );

            $this->sendPasswordReset($actor, $user);

            return $user->fresh(['roles.permissions']) ?? $user;
        });
    }

    public function updateStaff(User $actor, User $target, string $name, string $email): User
    {
        $target->fill([
            'name' => $name,
            'email' => $email,
        ])->save();

        $this->audit->record(
            SecurityActivityType::UserUpdated,
            'User '.$target->email.' was updated.',
            $actor,
            $target,
        );

        return $target->fresh(['roles.permissions']) ?? $target;
    }

    /**
     * @param  list<int>  $roleIds
     */
    public function syncRoles(User $actor, User $target, array $roleIds, bool $audit = true): void
    {
        if (! $target->isAdmin()) {
            throw ValidationException::withMessages([
                'role_ids' => 'Staff roles can only be assigned to Admin accounts.',
            ]);
        }

        $roles = Role::query()->whereIn('id', $roleIds)->get();
        if ($roles->count() !== count(array_unique($roleIds))) {
            throw ValidationException::withMessages([
                'role_ids' => 'One or more selected roles are invalid.',
            ]);
        }

        if ($roles->isEmpty()) {
            throw ValidationException::withMessages([
                'role_ids' => 'Assign at least one staff role.',
            ]);
        }

        $assignsSuperAdmin = $roles->contains(fn (Role $role) => $role->isSuperAdmin());
        if ($assignsSuperAdmin && ! $actor->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'role_ids' => 'Only a Super Admin can assign the Super Admin role.',
            ]);
        }

        $currentlySuper = $target->isSuperAdmin();
        if ($currentlySuper && ! $assignsSuperAdmin && $this->superAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'role_ids' => 'The last Super Admin cannot be removed.',
            ]);
        }

        $previous = $target->roles()->pluck('slug')->sort()->values()->all();
        $target->roles()->sync($roles->pluck('id')->all());
        $target->forgetPermissionCache();
        $next = $roles->pluck('slug')->sort()->values()->all();

        if ($audit && $previous !== $next) {
            $this->audit->record(
                SecurityActivityType::RoleAssigned,
                'Roles for '.$target->email.' were updated.',
                $actor,
                $target,
                [
                    'from' => $previous,
                    'to' => $next,
                ],
            );
        }
    }

    public function setActive(User $actor, User $target, bool $active): User
    {
        if ((int) $actor->id === (int) $target->id && ! $active) {
            throw ValidationException::withMessages([
                'is_active' => 'You cannot deactivate your own account.',
            ]);
        }

        if (! $active && $target->isSuperAdmin() && $this->superAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'is_active' => 'The last Super Admin cannot be deactivated.',
            ]);
        }

        $wasActive = $target->isActive();
        $target->forceFill(['is_active' => $active])->save();
        $target->forgetPermissionCache();

        if ($wasActive === $active) {
            return $target;
        }

        $this->audit->record(
            $active ? SecurityActivityType::UserActivated : SecurityActivityType::UserDeactivated,
            $active
                ? 'User '.$target->email.' was activated.'
                : 'User '.$target->email.' was deactivated.',
            $actor,
            $target,
        );

        if (! $active) {
            $this->loginLogs->accountDisabled(request(), $target);
        }

        return $target;
    }

    public function sendPasswordReset(User $actor, User $target): void
    {
        Password::sendResetLink(['email' => $target->email]);

        $target->forceFill(['must_change_password' => true])->save();

        $this->loginLogs->passwordResetRequest(request(), $target, $target->email);
        $this->audit->record(
            SecurityActivityType::PasswordResetSent,
            'A password reset was sent to '.$target->email.'.',
            $actor,
            $target,
        );
        app(UserActivityService::class)->record(
            $target,
            \App\Enums\UserActivityType::SecurityEvent,
            'A password reset was requested.',
            $actor,
        );
    }

    public function markPasswordChanged(User $user): void
    {
        $user->forceFill([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        app(UserActivityService::class)->record(
            $user,
            \App\Enums\UserActivityType::ChangedPassword,
            'Password was changed.',
            $user,
        );
    }

    public function superAdminCount(): int
    {
        return User::query()
            ->where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('slug', StaffRoleSlug::SuperAdmin->value))
            ->count();
    }
}
