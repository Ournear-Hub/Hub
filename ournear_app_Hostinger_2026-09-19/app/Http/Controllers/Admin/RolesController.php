<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncRolePermissionsRequest;
use App\Models\Role;
use App\Services\RoleManagementService;
use App\Support\RoleQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RolesController extends Controller
{
    public function index(Request $request, RoleQuery $roles): Response
    {
        $this->authorize('viewAny', Role::class);

        $selected = $roles->selected($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles->adminIndex(),
            'selected' => $selected?->toAdminArray(),
            'permission_groups' => $roles->groupedPermissions(),
            'filters' => [
                'role' => $selected ? (string) $selected->id : '',
            ],
        ]);
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role, RoleManagementService $service): RedirectResponse
    {
        $service->updatePermissions(
            $request->user(),
            $role,
            $request->collect('permission_ids')->map(fn ($id) => (int) $id)->all(),
        );

        return redirect()
            ->route('admin.roles.index', ['role' => $role->id])
            ->with('success', 'Role permissions updated.');
    }
}
