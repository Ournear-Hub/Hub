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
        $user = Auth::guard('account')->user();
        if ($user instanceof User) {
            return redirect()->route('account.home');
        }

        IntendedUrl::remember($request);

        $tab = $request->string('tab')->toString() === 'signup' ? 'signup' : 'login';

        return Inertia::render('Account/Login', [
            'tab' => $tab,
            'registeredEmail' => $request->session()->get('registered_email'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::guard('account')->user();
        if ($user instanceof User && $user->isCandidate() && ! $user->hasVerifiedEmail()) {
            $request->session()->put('prompt_email_verification', true);
        }

        return redirect()->to(IntendedUrl::pullAllowed(
            $request,
            ['/account', '/jobs'],
            route('account.home'),
        ));
    }

    public function switch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'destination' => ['required', 'in:login,register'],
        ]);
        $user = Auth::guard('account')->user();

        abort_unless($user instanceof User && $user->isAdmin(), 403);

        return redirect()->route($validated['destination']);
    }

    public function destroy(Request $request, LoginLogService $logs): RedirectResponse
    {
        $user = Auth::guard('account')->user();
        $logs->logout($request, $user instanceof User ? $user : null);

        Auth::guard('account')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
