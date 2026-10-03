<?php

namespace App\Http\Middleware;

use App\Support\IntendedUrl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UseAuthChannel
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse($request->is('admin') || $request->is('admin/*') ? 'admin' : 'account');
        IntendedUrl::partition($request);

        return $next($request);
    }
}
