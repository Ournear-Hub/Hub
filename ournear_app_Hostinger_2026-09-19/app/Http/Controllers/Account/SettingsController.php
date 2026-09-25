<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Enums\CommunicationChannel;
use App\Http\Requests\Account\UpdateAccountCommunicationPreferencesRequest;
use App\Http\Requests\Account\UpdateAccountPasswordRequest;
use App\Http\Requests\Account\UpdateAccountSettingsRequest;
use App\Services\AccountProfileService;
use App\Services\CommunicationPreferenceService;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->authorize('update', $user);

        return Inertia::render('Account/Settings/Index', [
            'role' => $user->role->value,
            'profile' => $user->toAccountProfileArray(),
        ]);
    }

    public function account(Request $request, AccountProfileService $profiles): Response
    {
        $user = $request->user();
        $this->authorize('update', $user);

        return Inertia::render('Account/Settings/Account', [
            'role' => $user->role->value,
            'profile' => $user->toAccountProfileArray(),
            'languages' => $profiles->languageOptions(),
        ]);
    }

    public function updateAccount(UpdateAccountSettingsRequest $request, AccountProfileService $profiles): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        $profiles->updateAccount($user, $request->accountAttributes());

        return back()->with('success', 'Your account settings have been updated.');
    }

    public function security(Request $request): Response
    {
        $user = $request->user();
        $this->authorize('update', $user);

        return Inertia::render('Account/Settings/Security', [
            'role' => $user->role->value,
            'profile' => $user->toAccountProfileArray(),
        ]);
    }

    public function updatePassword(
        UpdateAccountPasswordRequest $request,
        UserManagementService $users,
    ): RedirectResponse {
        $user = $request->user();
        $this->authorize('update', $user);

        $user->update([
            'password' => $request->validated('password'),
        ]);
        $users->markPasswordChanged($user);

        return back()->with('success', 'Your password has been updated.');
    }

    public function communication(Request $request, CommunicationPreferenceService $preferences): Response
    {
        $user = $request->user();
        $this->authorize('update', $user);
        abort_unless($user->isCandidate(), 403);

        $preferences->ensureDefaults($user);
        $user->load('communicationPreferences');

        return Inertia::render('Account/Settings/Communication', [
            'role' => $user->role->value,
            'channels' => collect(CommunicationChannel::cases())
                ->map(fn (CommunicationChannel $channel) => [
                    'value' => $channel->value,
                    'label' => $channel->label(),
                    'opted_in' => (bool) $user->communicationPreferences
                        ->first(fn ($preference) => $preference->channel === $channel
                            || $preference->channel?->value === $channel->value)
                        ?->opted_in,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function updateCommunication(
        UpdateAccountCommunicationPreferencesRequest $request,
        CommunicationPreferenceService $preferences,
    ): RedirectResponse {
        $user = $request->user();
        $this->authorize('update', $user);

        $preferences->sync($user, $request->channels(), 'account');

        return back()->with('success', 'Your communication preferences have been updated.');
    }
}
