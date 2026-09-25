<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs(
            'admin.logout',
            'admin.profile.password.edit',
            'admin.profile.password.update',
        )) {
            return $next($request);
        }

        if ($request->is('admin/profile/password') || $request->is('admin/logout')) {
            return $next($request);
        }

        return redirect()->route('admin.profile.password.edit');
    }
}
