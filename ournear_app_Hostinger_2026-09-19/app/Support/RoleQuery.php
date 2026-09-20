<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RoleQuery
{
    /**
     * @return list<array<string, mixed>>
     */
    public function adminIndex(): array
    {
        return Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderBy('id')
            ->get()
            ->map(fn (Role $role) => $role->toAdminArray())
            ->values()
            ->all();
    }

    public function selected(Request $request): ?Role
    {
        $id = $request->integer('role');
        if ($id <= 0) {
            return Role::query()->with('permissions')->withCount('users')->orderBy('id')->first();
        }

        return Role::query()->with('permissions')->withCount('users')->find($id);
    }

    /**
     * @return list<array{group: string, permissions: list<array<string, mixed>>}>
     */
    public function groupedPermissions(): array
    {
        return Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group')
            ->map(fn (Collection $permissions, string $group) => [
                'group' => $group,
                'permissions' => $permissions->map(fn (Permission $permission) => $permission->toAdminArray())->values()->all(),
            ])
            ->values()
            ->all();
    }
}
