<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignUserRolesRequest;
use App\Http\Requests\Admin\SendUserPasswordResetRequest;
use App\Http\Requests\Admin\StoreStaffUserRequest;
use App\Http\Requests\Admin\UpdateStaffUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use App\Services\UserManagementService;
use App\Support\UserQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function index(Request $request, UserQuery $users): Response
    {
        $this->authorize('viewAny', User::class);

        $page = $users->adminIndex($request)
            ->through(fn (User $user) => $user->toAdminListArray());

        $selected = $users->selected($request);
        if ($selected && $request->user()?->cannot('view', $selected)) {
            $selected = null;
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $page,
            'selected' => $selected?->toAdminArray(),
            'filters' => $users->filters($request),
            'options' => $users->options(),
            'can_manage' => $request->user()?->can('create', User::class) === true,
            'actor_is_super_admin' => $request->user()?->isSuperAdmin() === true,
        ]);
    }

    public function store(StoreStaffUserRequest $request, UserManagementService $service): RedirectResponse
    {
        $user = $service->createStaff(
            $request->user(),
            $request->string('name')->toString(),
            $request->string('email')->toString(),
            $request->collect('role_ids')->map(fn ($id) => (int) $id)->all(),
        );

        return redirect()
            ->route('admin.users.index', ['user' => $user->id])
            ->with('success', 'Staff user created. A password reset email was sent.');
    }

    public function update(UpdateStaffUserRequest $request, User $user, UserManagementService $service): RedirectResponse
    {
        $service->updateStaff(
            $request->user(),
            $user,
            $request->string('name')->toString(),
            $request->string('email')->toString(),
        );

        return back()->with('success', 'User updated.');
    }

    public function syncRoles(AssignUserRolesRequest $request, User $user, UserManagementService $service): RedirectResponse
    {
        $service->syncRoles(
            $request->user(),
            $user,
            $request->collect('role_ids')->map(fn ($id) => (int) $id)->all(),
        );

        return back()->with('success', 'Roles updated.');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user, UserManagementService $service): RedirectResponse
    {
        $service->setActive($request->user(), $user, $request->boolean('is_active'));

        return back()->with('success', $request->boolean('is_active') ? 'User activated.' : 'User deactivated.');
    }

    public function sendPasswordReset(SendUserPasswordResetRequest $request, User $user, UserManagementService $service): RedirectResponse
    {
        $service->sendPasswordReset($request->user(), $user);

        return back()->with('success', 'Password reset email sent.');
    }
}
