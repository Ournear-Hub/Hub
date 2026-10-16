<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignUserRolesRequest;
use App\Http\Requests\Admin\SendUserPasswordResetRequest;
use App\Http\Requests\Admin\StoreStaffUserRequest;
use App\Http\Requests\Admin\StoreTagRequest;
use App\Http\Requests\Admin\SyncUserTagsRequest;
use App\Http\Requests\Admin\UpdateCommunicationPreferencesRequest;
use App\Http\Requests\Admin\UpdatePlatformUserRequest;
use App\Http\Requests\Admin\UpdateStaffUserRequest;
use App\Http\Requests\Admin\UpdateTagRequest;
use App\Http\Requests\Admin\UpdateTagStatusRequest;
use App\Http\Requests\Admin\UpdateUserSourceRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\Tag;
use App\Models\User;
use App\Services\LocalDevAccountEmailVerificationService;
use App\Services\TagService;
use App\Services\UserCrmService;
use App\Services\UserManagementService;
use App\Support\User360Assembler;
use App\Support\UserActivityQuery;
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

        return Inertia::render('Admin/Users/Index', [
            'users' => $page,
            'filters' => $users->filters($request),
            'options' => $users->options(),
            'can_manage' => $request->user()?->can('create', User::class) === true,
        ]);
    }

    public function activity(Request $request, UserActivityQuery $activities): Response
    {
        $this->authorize('viewAny', User::class);

        $page = $activities->adminIndex($request)
            ->through(fn ($activity) => $activity->toAdminArray());

        return Inertia::render('Admin/Users/Activity', [
            'activities' => $page,
            'filters' => $activities->filters($request),
            'options' => $activities->options(),
        ]);
    }

    public function show(Request $request, User $user, User360Assembler $assembler, UserQuery $users): Response
    {
        $this->authorize('view', $user);

        return Inertia::render('Admin/Users/Show', [
            'record' => $assembler->payload($user),
            'can_manage' => $request->user()?->can('update', $user) === true,
            'can_change_status' => $request->user()?->can('deactivate', $user) === true,
            'actor_is_super_admin' => $request->user()?->isSuperAdmin() === true,
            'options' => $users->options(),
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
            ->route('admin.staff.index', ['user' => $user->id])
            ->with('success', 'Staff user created. A password reset email was sent. The password is never displayed.');
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

    public function updateProfile(UpdatePlatformUserRequest $request, User $user, UserCrmService $crm): RedirectResponse
    {
        $crm->updateIdentity(
            $request->user(),
            $user,
            $request->identity(),
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        return back()->with('success', 'User profile updated.');
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

    public function updateStatus(UpdateUserStatusRequest $request, User $user, UserCrmService $crm): RedirectResponse
    {
        $status = $request->status();
        $crm->setStatus($request->user(), $user, $status);

        return back()->with('success', 'Account status set to '.$status->label().'.');
    }

    public function sendPasswordReset(SendUserPasswordResetRequest $request, User $user, UserManagementService $service): RedirectResponse
    {
        $service->sendPasswordReset($request->user(), $user);

        return back()->with('success', 'Password reset email sent. The password is never displayed.');
    }

    public function updatePreferences(UpdateCommunicationPreferencesRequest $request, User $user, UserCrmService $crm): RedirectResponse
    {
        $crm->updatePreferences($request->user(), $user, $request->channels());

        return back()->with('success', 'Communication preferences saved.');
    }

    public function updateSource(UpdateUserSourceRequest $request, User $user, UserCrmService $crm): RedirectResponse
    {
        $crm->setRegistrationSource($request->user(), $user, $request->source());

        return back()->with('success', 'Registration source updated.');
    }

    public function syncTags(SyncUserTagsRequest $request, User $user, TagService $tags): RedirectResponse
    {
        $tags->syncUserTags($request->user(), $user, $request->tagIds());

        return back()->with('success', 'User tags updated.');
    }

    public function storeTag(StoreTagRequest $request, TagService $tags): RedirectResponse
    {
        $tags->create($request->user(), $request->string('name')->toString());

        return back()->with('success', 'Tag created.');
    }

    public function updateTag(UpdateTagRequest $request, Tag $tag, TagService $tags): RedirectResponse
    {
        $tags->rename($request->user(), $tag, $request->string('name')->toString());

        return back()->with('success', 'Tag renamed.');
    }

    public function updateTagStatus(UpdateTagStatusRequest $request, Tag $tag, TagService $tags): RedirectResponse
    {
        $active = $request->boolean('is_active');
        $tags->setActive($request->user(), $tag, $active);

        return back()->with('success', $active ? 'Tag activated.' : 'Tag deactivated.');
    }

    public function localVerifyEmail(
        Request $request,
        User $user,
        LocalDevAccountEmailVerificationService $verification,
    ): RedirectResponse {
        $this->authorize('update', $user);

        try {
            $verification->assertAllowedEnvironment();
        } catch (\RuntimeException $exception) {
            abort(403, $exception->getMessage());
        }

        if ($user->emailVerified()) {
            return back()->with('success', 'Account email is already verified.');
        }

        $verification->verify($user, $request->user());

        return back()->with('success', 'Account email marked verified (local development only).');
    }
}
