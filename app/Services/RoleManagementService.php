<?php

namespace App\Services;

use App\Enums\PermissionName;
use App\Enums\SecurityActivityType;
use App\Enums\StaffRoleSlug;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessCatalog;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleManagementService
{
    public function __construct(private SecurityAuditService $audit) {}

    public function create(User $actor, string $name, ?string $description = null): Role
    {
        $slug = Str::slug($name, '_');

        if ($slug === '' || $slug === StaffRoleSlug::SuperAdmin->value) {
            throw ValidationException::withMessages([
                'name' => 'This role name is reserved or cannot be used.',
            ]);
        }

        if (Role::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A role with this name already exists.',
            ]);
        }

        $role = Role::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'is_locked' => false,
        ]);

        $this->audit->record(
            SecurityActivityType::RoleCreated,
            'Staff role '.$role->name.' was created.',
            $actor,
            $role,
            ['slug' => $role->slug],
        );

        return $role->fresh(['permissions']) ?? $role;
    }

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

        $names = $permissions->pluck('name')->all();

        if (in_array(PermissionName::RolesManage->value, $names, true)) {
            throw ValidationException::withMessages([
                'permission_ids' => $role->slug === StaffRoleSlug::Admin->value
                    ? 'The Admin role cannot receive roles.manage. Use Super Admin for role administration.'
                    : 'roles.manage can only belong to the Super Admin role.',
            ]);
        }

        foreach ($permissions as $permission) {
            if (! $actor->hasPermission($permission->name)) {
                throw ValidationException::withMessages([
                    'permission_ids' => 'You cannot grant a permission you do not have.',
                ]);
            }
        }

        $catalog = AccessCatalog::rolePermissions()[$role->slug] ?? null;
        if (is_array($catalog)) {
            $missing = array_values(array_diff($catalog, $names));
            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'permission_ids' => 'Catalog staff roles must keep their standard permissions. Extra permissions can be added.',
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
