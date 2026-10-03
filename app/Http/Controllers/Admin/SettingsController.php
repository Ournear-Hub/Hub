<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBrandingRequest;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Http\Requests\Admin\UpdateWebsiteSettingsRequest;
use App\Services\WebsiteSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request, WebsiteSettingService $settings): Response|RedirectResponse
    {
        $tab = match ($request->string('tab')->toString()) {
            'branding' => 'branding',
            'account' => 'account',
            default => 'website',
        };

        if ($tab === 'account') {
            return redirect()->route('admin.profile.show');
        }

        abort_unless($request->user()?->canManageWebsite() === true, 403);

        $current = $settings->current();

        return Inertia::render('Admin/Settings', [
            'tab' => $tab,
            'websiteSettings' => $current->toAdminArray(),
            'branding' => $current->toAdminBrandingArray(),
        ]);
    }

    public function updateWebsite(UpdateWebsiteSettingsRequest $request, WebsiteSettingService $settings): RedirectResponse
    {
        $settings->update($request->validated());

        return redirect()
            ->route('admin.settings.edit', ['tab' => 'website'])
            ->with('success', 'Website settings saved.');
    }

    public function updateBranding(UpdateBrandingRequest $request, WebsiteSettingService $settings): RedirectResponse
    {
        $settings->updateBranding($request->validated());

        return redirect()
            ->route('admin.settings.edit', ['tab' => 'branding'])
            ->with('success', 'Branding saved.');
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()?->update($request->validated());

        return redirect()
            ->route('admin.profile.show')
            ->with('success', 'Profile updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()?->update([
            'password' => $request->validated('password'),
        ]);
        app(\App\Services\UserManagementService::class)->markPasswordChanged($request->user());

        return redirect()
            ->route('admin.profile.show')
            ->with('success', 'Password updated.');
    }
}
