<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\LoginRequest;
use App\Models\User;
use App\Services\LoginLogService;
use App\Support\IntendedUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        if ($user instanceof User) {
            if ($user->isAdmin()) {
                return Inertia::render('Account/Switch', [
                    'destination' => 'login',
                    'currentUserName' => $user->name,
                ]);
            }

            return redirect()->route('account.home');
        }

        IntendedUrl::remember($request);

        return Inertia::render('Account/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->to(IntendedUrl::pullAllowed(
            $request,
            ['/account', '/jobs'],
            route('account.home'),
        ));
    }

    public function switch(Request $request, LoginLogService $logs): RedirectResponse
    {
        $validated = $request->validate([
            'destination' => ['required', 'in:login,register'],
        ]);
        $user = $request->user();

        abort_unless($user instanceof User && $user->isAdmin(), 403);

        $safeIntendedUrl = IntendedUrl::pullAllowed(
            $request,
            ['/account', '/jobs'],
            '',
        );

        $logs->logout($request, $user);
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($safeIntendedUrl !== '') {
            IntendedUrl::remember($request, $safeIntendedUrl);
        }

        return redirect()->route($validated['destination']);
    }

    public function destroy(Request $request, LoginLogService $logs): RedirectResponse
    {
        $user = $request->user();
        $logs->logout($request, $user instanceof User ? $user : null);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
