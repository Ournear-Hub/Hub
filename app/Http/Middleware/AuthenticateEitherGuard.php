<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateEitherGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->check()) {
            Auth::shouldUse('admin');

            return $next($request);
        }

        if (Auth::guard('account')->check()) {
            Auth::shouldUse('account');

            return $next($request);
        }

        throw new AuthenticationException(
            'Unauthenticated.',
            ['account'],
            route('login'),
        );
    }
}
