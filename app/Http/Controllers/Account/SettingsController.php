<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Enums\CommunicationChannel;
use App\Http\Requests\Account\UpdateAccountCommunicationPreferencesRequest;
use App\Http\Requests\Account\UpdateAccountPasswordRequest;
use App\Http\Requests\Account\UpdateAccountSettingsRequest;
use App\Services\AccountProfileService;
use App\Services\CommunicationPreferenceService;
use App\Services\HiringActivationService;
use App\Services\MobileVerificationService;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        if ($redirect = $this->redirectEmployerAwayFromPersonalSettings($user)) {
            return $redirect;
        }

        return redirect()->route('account.settings.account');
    }

    public function account(
        Request $request,
        AccountProfileService $profiles,
        CommunicationPreferenceService $preferences,
    ): Response|RedirectResponse {
        $user = $request->user();
        $this->authorize('update', $user);

        if ($redirect = $this->redirectEmployerAwayFromPersonalSettings($user)) {
            return $redirect;
        }

        return Inertia::render('Account/Settings/Account', [
            'role' => $user->role->value,
            'profile' => $user->toAccountProfileArray(),
            'languages' => $profiles->languageOptions(),
            'communication_channels' => $this->communicationChannels($user, $preferences),
        ]);
    }

    public function updateAccount(UpdateAccountSettingsRequest $request, AccountProfileService $profiles): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        if ($redirect = $this->redirectEmployerAwayFromPersonalSettings($user)) {
            return $redirect;
        }

        $profiles->updateAccount($user, $request->accountAttributes());

        return back()->with('success', 'Your account settings have been updated.');
    }

    public function security(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        if ($redirect = $this->redirectEmployerAwayFromPersonalSettings($user)) {
            return $redirect;
        }

        return Inertia::render('Account/Settings/Security', [
            'role' => $user->role->value,
            'profile' => $user->toAccountProfileArray(),
        ]);
    }

    public function verification(
        Request $request,
        MobileVerificationService $verification,
        HiringActivationService $hiring,
    ): Response {
        $user = $request->user();
        $this->authorize('update', $user);

        return Inertia::render('Account/Settings/Verification', [
            'role' => $user->role->value,
            'profile' => $user->toAccountProfileArray(),
            'mobile_otp_retry_after' => $verification->retryAfterSeconds($user),
            'hiring_gate' => ($user->isEmployer() || $user->canAccessHiringWorkspace())
                ? $hiring->gate($user)
                : null,
        ]);
    }

    public function password(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        if ($redirect = $this->redirectEmployerAwayFromPersonalSettings($user)) {
            return $redirect;
        }

        return Inertia::render('Account/Settings/Password');
    }

    public function updatePassword(
        UpdateAccountPasswordRequest $request,
        UserManagementService $users,
    ): RedirectResponse {
        $user = $request->user();
        $this->authorize('update', $user);

        if ($redirect = $this->redirectEmployerAwayFromPersonalSettings($user)) {
            return $redirect;
        }

        $user->update([
            'password' => $request->validated('password'),
        ]);
        $users->markPasswordChanged($user);

        return back()->with('success', 'Your password has been updated.');
    }

    public function communication(
        Request $request,
        CommunicationPreferenceService $preferences,
        AccountProfileService $profiles,
    ): Response|RedirectResponse {
        $user = $request->user();
        $this->authorize('update', $user);
        abort_unless($user->isAccountMember(), 403);

        if ($user->isEmployer()) {
            return Inertia::render('Account/Settings/Account', [
                'role' => 'employer',
                'profile' => $user->toAccountProfileArray(),
                'languages' => $profiles->languageOptions(),
                'communication_channels' => $this->communicationChannels($user, $preferences),
            ]);
        }

        return redirect()
            ->route('account.settings.account')
            ->withFragment('communication');
    }

    public function updateCommunication(
        UpdateAccountCommunicationPreferencesRequest $request,
        CommunicationPreferenceService $preferences,
    ): RedirectResponse {
        $user = $request->user();
        $this->authorize('update', $user);
        abort_unless($user->isAccountMember(), 403);

        $preferences->sync($user, $request->channels(), 'account');

        if ($user->isEmployer()) {
            return redirect()
                ->route('account.settings.communication')
                ->with('success', 'Your communication preferences have been updated.');
        }

        return redirect()
            ->route('account.settings.account')
            ->withFragment('communication')
            ->with('success', 'Your communication preferences have been updated.');
    }

    /**
     * @return list<array{value: string, label: string, opted_in: bool}>
     */
    private function communicationChannels($user, CommunicationPreferenceService $preferences): array
    {
        if (! $user->isAccountMember()) {
            return [];
        }

        $preferences->ensureDefaults($user);
        $user->load('communicationPreferences');

        return collect(CommunicationChannel::cases())
            ->map(fn (CommunicationChannel $channel) => [
                'value' => $channel->value,
                'label' => $channel->label(),
                'opted_in' => (bool) $user->communicationPreferences
                    ->first(fn ($preference) => $preference->channel === $channel
                        || $preference->channel?->value === $channel->value)
                    ?->opted_in,
            ])
            ->values()
            ->all();
    }

    private function redirectEmployerAwayFromPersonalSettings($user): ?RedirectResponse
    {
        if ($user->isEmployer()) {
            return redirect()->route('account.company');
        }

        return null;
    }
}
