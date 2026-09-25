<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('account')->user();

        if ($user === null || ! $user->isEmployer()) {
            abort(403);
        }

        return $next($request);
    }
}
