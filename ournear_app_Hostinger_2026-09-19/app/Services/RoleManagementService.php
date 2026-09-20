<?php

namespace App\Services;

use App\Enums\PermissionName;
use App\Enums\SecurityActivityType;
use App\Enums\StaffRoleSlug;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessCatalog;
use Illuminate\Validation\ValidationException;

class RoleManagementService
{
    public function __construct(private SecurityAuditService $audit) {}

    /**
     * @param  list<int>  $permissionIds
     */
    public function updatePermissions(User $actor, Role $role, array $permissionIds): Role
    {
        if ($role->is_locked) {
            throw ValidationException::withMessages([
                'permission_ids' => 'The Super Admin role always has every permission and cannot be edited.',
            ]);
        }

        $permissions = Permission::query()->whereIn('id', $permissionIds)->get();
        if ($permissions->count() !== count(array_unique($permissionIds))) {
            throw ValidationException::withMessages([
                'permission_ids' => 'One or more selected permissions are invalid.',
            ]);
        }

        if ($role->slug === StaffRoleSlug::Admin->value) {
            $names = $permissions->pluck('name')->all();
            if (in_array(PermissionName::RolesManage->value, $names, true)) {
                throw ValidationException::withMessages([
                    'permission_ids' => 'The Admin role cannot receive roles.manage. Use Super Admin for role administration.',
                ]);
            }
        }

        $previous = $role->permissions()->pluck('name')->sort()->values()->all();
        $role->permissions()->sync($permissions->pluck('id')->all());
        $next = $permissions->pluck('name')->sort()->values()->all();

        if ($previous !== $next) {
            $this->audit->record(
                SecurityActivityType::PermissionChanged,
                'Permissions for '.$role->name.' were updated.',
                $actor,
                $role,
                [
                    'from' => $previous,
                    'to' => $next,
                ],
            );
        }

        return $role->fresh(['permissions']) ?? $role;
    }

    public function restoreLockedRoles(): void
    {
        $super = Role::query()->where('slug', StaffRoleSlug::SuperAdmin->value)->first();
        if (! $super) {
            return;
        }

        $ids = Permission::query()
            ->whereIn('name', AccessCatalog::permissionNames())
            ->pluck('id')
            ->all();

        $super->permissions()->sync($ids);
    }
}
