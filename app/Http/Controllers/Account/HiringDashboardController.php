<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\AccountDashboardService;
use App\Services\EmployerPostingEligibilityService;
use App\Services\HiringActivationService;
use App\Services\MasterData\MasterDataService;
use App\Support\EmployerWorkspaceDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HiringDashboardController extends Controller
{
    public function show(
        Request $request,
        HiringActivationService $hiring,
        EmployerPostingEligibilityService $eligibility,
        AccountDashboardService $dashboards,
        MasterDataService $masterData,
    ): Response {
        $user = $request->user();
        $this->authorize('update', $user);

        EmployerWorkspaceDestination::remember($request);

        $gate = $hiring->gate($user);

        $workspace = $user->employerProfile
            ? $dashboards->employerHiringCommandCenter($user)
            : null;

        return Inertia::render('Account/Hiring/Dashboard', [
            'gate' => $gate,
            'eligibility' => $user->employerProfile ? $eligibility->for($user) : null,
            'workspace' => $workspace,
            'industries' => $masterData->formOptions()['industries'] ?? [],
        ]);
    }

    public function activate(Request $request, HiringActivationService $hiring): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:160'],
            'company_phone' => ['required', 'string', 'max:40'],
            'company_email' => ['required', 'email', 'max:190'],
            'industry_id' => ['required', 'integer', Rule::exists('industries', 'id')],
        ]);

        $hiring->activate(
            $user,
            $validated['company_name'],
            $validated['company_phone'],
            $validated['company_email'],
            (int) $validated['industry_id'],
        );

        $destination = EmployerWorkspaceDestination::pull($request, route('account.hiring'));

        return redirect()
            ->to($destination)
            ->with('success', 'Employer workspace activated.');
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
