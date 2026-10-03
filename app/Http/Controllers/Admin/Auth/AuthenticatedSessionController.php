<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
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
        $user = Auth::guard('admin')->user();
        if ($user instanceof User && $user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Admin/Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->to(IntendedUrl::pullAllowed(
            $request,
            ['/admin'],
            route('admin.dashboard'),
        ));
    }

    public function destroy(Request $request, LoginLogService $logs): RedirectResponse
    {
        $user = Auth::guard('admin')->user();
        $logs->logout($request, $user instanceof User ? $user : null);

        Auth::guard('admin')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
