<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCandidate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('account')->user();

        if ($user === null || ! $user->isCandidate()) {
            abort(403);
        }

        return $next($request);
    }
}
