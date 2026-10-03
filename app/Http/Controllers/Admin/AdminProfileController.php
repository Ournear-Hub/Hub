<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PreferredLanguage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForceUpdatePasswordRequest;
use App\Http\Requests\Admin\UpdateAdminProfileRequest;
use App\Models\SecurityActivity;
use App\Models\User;
use App\Services\UserCrmService;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $user->loadMissing(['roles', 'adminProfile', 'brand']);

        return Inertia::render('Admin/Profile/Show', [
            'profile' => $user->toAdminArray(),
            'languages' => array_map(fn (PreferredLanguage $language) => [
                'value' => $language->value,
                'label' => $language->label(),
            ], PreferredLanguage::cases()),
            'login_logs' => $user->loginLogs()->latest('id')->limit(20)->get()->map->toAdminArray()->all(),
            'security_activities' => SecurityActivity::query()
                ->with('actor')
                ->where('actor_id', $user->id)
                ->latest('id')
                ->limit(20)
                ->get()
                ->map(fn (SecurityActivity $activity) => $activity->toAdminArray())
                ->all(),
            'activities' => $user->activities()->with(['actor', 'user'])->latest('id')->limit(20)->get()->map->toAdminArray()->all(),
        ]);
    }

    public function update(UpdateAdminProfileRequest $request, UserCrmService $crm): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $crm->updateIdentity(
            $user,
            $user,
            [
                'name' => $request->string('name')->toString(),
                'username' => $request->string('username')->toString(),
                'email' => $request->string('email')->toString(),
                'mobile' => $request->string('mobile')->toString() ?: null,
                'location' => $request->string('location')->toString() ?: null,
                'preferred_language' => $request->string('preferred_language')->toString(),
            ],
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        $profile = $user->adminProfile()->firstOrCreate(['user_id' => $user->id]);
        $profile->fill($request->only([
            'department',
            'designation',
            'joined_at',
            'professional_details',
            'qualifications',
            'skills',
            'employment_information',
        ]))->save();

        return back()->with('success', 'Admin profile saved.');
    }

    public function editPassword(Request $request): Response
    {
        return Inertia::render('Admin/Profile/Password', [
            'must_change' => (bool) $request->user()?->must_change_password,
        ]);
    }

    public function updatePassword(
        ForceUpdatePasswordRequest $request,
        UserManagementService $users,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $forced = (bool) $user->must_change_password;
        $user->update([
            'password' => $request->validated('password'),
        ]);
        $users->markPasswordChanged($user);

        return redirect()
            ->route($forced ? 'admin.dashboard' : 'admin.profile.show')
            ->with('success', 'Password updated.');
    }
}
