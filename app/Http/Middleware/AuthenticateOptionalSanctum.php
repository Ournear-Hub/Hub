<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOptionalSanctum
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null && $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($request->bearerToken());
            $user = $accessToken?->tokenable;

            if (
                $user instanceof User
                && $accessToken !== null
                && ($accessToken->expires_at === null || $accessToken->expires_at->isFuture())
            ) {
                $request->setUserResolver(static fn () => $user);
            }
        }

        return $next($request);
    }
}
