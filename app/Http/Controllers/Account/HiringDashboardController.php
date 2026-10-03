<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\AccountDashboardService;
use App\Services\EmployerPostingEligibilityService;
use App\Services\HiringActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HiringDashboardController extends Controller
{
    public function show(
        Request $request,
        HiringActivationService $hiring,
        EmployerPostingEligibilityService $eligibility,
        AccountDashboardService $dashboards,
    ): Response {
        $user = $request->user();
        $this->authorize('update', $user);

        $gate = $hiring->gate($user);

        $workspace = $user->employerProfile
            ? $dashboards->employerHiringCommandCenter($user)
            : null;

        return Inertia::render('Account/Hiring/Dashboard', [
            'gate' => $gate,
            'eligibility' => $user->employerProfile ? $eligibility->for($user) : null,
            'workspace' => $workspace,
        ]);
    }

    public function activate(Request $request, HiringActivationService $hiring): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:160'],
            'hiring_email' => ['nullable', 'email', 'max:190'],
        ]);

        $hiring->activate(
            $user,
            $validated['company_name'],
            $validated['hiring_email'] ?? null,
        );

        return back()->with('success', 'Hiring services active');
    }

    public function sendHiringEmail(Request $request, HiringActivationService $hiring): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        $validated = $request->validate([
            'hiring_email' => ['nullable', 'email', 'max:190'],
        ]);

        $hiring->saveHiringEmail($user, $validated['hiring_email'] ?? null);
        $user->refresh();
        $hiring->sendHiringEmailVerification($user);

        return back()->with('success', 'Verification link sent to the recruiter email.');
    }

    public function verifyHiringEmail(Request $request, HiringActivationService $hiring): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && (int) $request->route('id') === (int) $user->id, 403);
        $this->authorize('update', $user);

        $hiring->verifyHiringEmail($user, (string) $request->route('hash'));

        return redirect()
            ->route('account.hiring')
            ->with('success', 'Recruiter email verified.');
    }
}
