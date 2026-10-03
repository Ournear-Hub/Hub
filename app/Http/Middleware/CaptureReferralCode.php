<?php

namespace App\Http\Middleware;

use App\Services\ReferralAttributionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureReferralCode
{
    public function __construct(private ReferralAttributionService $referrals) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('admin') && ! $request->is('admin/*')) {
            $this->referrals->capture($request);
        }

        return $next($request);
    }
}
