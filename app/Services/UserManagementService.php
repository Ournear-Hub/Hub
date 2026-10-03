<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\PermissionName;
use App\Enums\RegistrationSource;
use App\Enums\SecurityActivityType;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\EmployerProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
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
                'registration_source' => RegistrationSource::Admin,
            ]);
            $user->forceFill([
                'is_active' => true,
                'account_status' => AccountStatus::Active,
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

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     mobile?: string|null,
     *     company_name: string,
     *     contact_person?: string|null,
     *     mobile_number?: string|null,
     *     company_website?: string|null
     * }  $data
     */
    public function createEmployerClient(User $actor, array $data): EmployerProfile
    {
        $email = strtolower(trim($data['email']));
        $existing = User::query()->where('email', $email)->with('employerProfile')->first();
        if ($existing instanceof User && $existing->isEmployer() && $existing->employerProfile instanceof EmployerProfile) {
            throw ValidationException::withMessages([
                'email' => 'An employer already uses this email. Link the existing EmployerProfile instead of creating another company.',
                'employer_profile_id' => 'Link employer #'.$existing->employerProfile->id.' instead of creating a new account.',
            ]);
        }
        if ($existing instanceof User) {
            throw ValidationException::withMessages([
                'email' => 'This email already belongs to an existing account.',
            ]);
        }

        return DB::transaction(function () use ($actor, $data, $email) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $email,
                'mobile' => $data['mobile'] ?? $data['mobile_number'] ?? null,
                'password' => Str::password(32),
                'role' => UserRole::Employer,
                'registration_source' => RegistrationSource::Admin,
            ]);
            $user->forceFill([
                'is_active' => true,
                'account_status' => AccountStatus::Active,
                'must_change_password' => true,
            ])->save();

            $profile = EmployerProfile::query()->create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'contact_person' => $data['contact_person'] ?? $data['name'],
                'mobile_number' => $data['mobile_number'] ?? $data['mobile'] ?? null,
                'company_website' => $data['company_website'] ?? null,
            ]);

            $this->audit->record(
                SecurityActivityType::CrmEmployerCreated,
                'Employer '.$profile->displayCompany().' was created from CRM.',
                $actor,
                $profile,
                ['user_id' => $user->id],
            );

            $this->sendPasswordReset($actor, $user);

            return $profile->fresh('user') ?? $profile;
        });
    }

    /**
     * Identity-only partner user. No password reset and no account portal.
     *
     * @param  array{name: string, email: string, mobile?: string|null}  $data
     */
    public function createPartnerIdentity(User $actor, array $data): User
    {
        $email = strtolower(trim($data['email']));
        $existing = User::query()->where('email', $email)->first();
        if ($existing instanceof User) {
            throw ValidationException::withMessages([
                'email' => 'This email already belongs to an existing account.',
            ]);
        }

        return DB::transaction(function () use ($actor, $data, $email) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $email,
                'mobile' => $data['mobile'] ?? null,
                'password' => Str::password(32),
                'role' => UserRole::Partner,
                'registration_source' => RegistrationSource::Admin,
            ]);
            $user->forceFill([
                'is_active' => true,
                'account_status' => AccountStatus::Active,
                'must_change_password' => true,
            ])->save();

            $this->audit->record(
                SecurityActivityType::UserCreated,
                'Channel partner user '.$user->email.' was created.',
                $actor,
                $user,
            );

            return $user->fresh() ?? $user;
        });
    }

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     mobile?: string|null
     * }  $data
     */
    public function createInvestorIdentity(User $actor, array $data): User
    {
        $email = strtolower(trim($data['email']));
        $existing = User::query()->where('email', $email)->first();
        if ($existing instanceof User) {
            throw ValidationException::withMessages([
                'email' => 'This email already belongs to an existing account.',
            ]);
        }

        return DB::transaction(function () use ($actor, $data, $email) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $email,
                'mobile' => $data['mobile'] ?? null,
                'password' => Str::password(32),
                'role' => UserRole::Investor,
                'registration_source' => RegistrationSource::Admin,
            ]);
            $user->forceFill([
                'is_active' => false,
                'account_status' => AccountStatus::PendingVerification,
                'must_change_password' => true,
            ])->save();

            $this->audit->record(
                SecurityActivityType::UserCreated,
                'Investor user '.$user->email.' was created.',
                $actor,
                $user,
            );

            return $user->fresh() ?? $user;
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

        $roles = Role::query()->whereIn('id', $roleIds)->with('permissions')->get();
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

        $this->assertAssignableBy($actor, $roles);

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

    /**
     * @param  Collection<int, Role>  $roles
     */
    private function assertAssignableBy(User $actor, Collection $roles): void
    {
        foreach ($roles as $role) {
            if ($role->isSuperAdmin()) {
                continue;
            }

            foreach ($role->permissions as $permission) {
                if (
                    $permission->name === PermissionName::RolesManage->value
                    || ! $actor->hasPermission($permission->name)
                ) {
                    throw ValidationException::withMessages([
                        'role_ids' => 'You cannot assign a role that grants privileges beyond your own access.',
                    ]);
                }
            }
        }
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
