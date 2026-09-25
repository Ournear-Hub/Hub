<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || $user->isActive()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $login = $request->is('admin') || $request->is('admin/*')
            ? 'admin.login'
            : 'login';

        return redirect()->guest(route($login))->withErrors([
            'email' => $user->accountStatus()->loginError(),
        ]);
    }
}
