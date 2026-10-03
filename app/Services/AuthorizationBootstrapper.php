<?php

namespace App\Services;

use App\Enums\PermissionName;
use App\Enums\StaffRoleSlug;
use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessCatalog;

class AuthorizationBootstrapper
{
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
        $this->assignExistingAdmins();
    }

    public function seedPermissions(): void
    {
        foreach (AccessCatalog::permissions() as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission['name']],
                [
                    'group' => $permission['group'],
                    'label' => $permission['label'],
                ],
            );
        }
    }

    public function seedRoles(): void
    {
        $map = AccessCatalog::rolePermissions();

        foreach (StaffRoleSlug::cases() as $slug) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug->value],
                [
                    'name' => $slug->label(),
                    'description' => $slug->description(),
                    'is_locked' => $slug->isLocked(),
                ],
            );

            $permissionIds = Permission::query()
                ->whereIn('name', $map[$slug->value] ?? [])
                ->pluck('id')
                ->all();

            if ($slug->isLocked()) {
                $role->permissions()->sync($permissionIds);
            } else {
                // Keep catalog grants as a floor. Do not strip extra permissions
                // granted through Admin role management.
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }
        }

        $this->restrictRolesManageToSuperAdmin();
    }

    private function restrictRolesManageToSuperAdmin(): void
    {
        $permissionId = Permission::query()
            ->where('name', PermissionName::RolesManage->value)
            ->value('id');

        if (! is_int($permissionId) && ! is_numeric($permissionId)) {
            return;
        }

        Role::query()
            ->where('slug', '!=', StaffRoleSlug::SuperAdmin->value)
            ->each(function (Role $role) use ($permissionId): void {
                $role->permissions()->detach((int) $permissionId);
            });
    }

    public function assignExistingAdmins(): void
    {
        $superAdmin = Role::query()->where('slug', StaffRoleSlug::SuperAdmin->value)->first();
        if (! $superAdmin) {
            return;
        }

        User::query()
            ->where('role', UserRole::Admin->value)
            ->whereDoesntHave('roles')
            ->orderBy('id')
            ->each(function (User $user) use ($superAdmin): void {
                $user->roles()->syncWithoutDetaching([$superAdmin->id]);
            });
    }

    public function syncUserRole(User $user, string $slug): void
    {
        $role = Role::query()->where('slug', $slug)->first();
        if (! $role) {
            return;
        }

        $user->roles()->sync([$role->id]);
        $user->forgetPermissionCache();
    }
}
