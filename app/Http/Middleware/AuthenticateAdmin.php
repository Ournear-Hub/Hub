<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();
        if ($admin instanceof User && $admin->isAdmin()) {
            Auth::shouldUse('admin');

            return $next($request);
        }

        $account = Auth::guard('account')->user();
        if ($account instanceof User) {
            abort(403);
        }

        throw new AuthenticationException(
            'Unauthenticated.',
            ['admin'],
            route('admin.login'),
        );
    }
}
