<?php

namespace App\Http\Middleware;

use App\Services\MailConfigurationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies DB-backed mail + APP URL config once per web request (not during console/health boot).
 */
class ApplyRuntimeMailConfiguration
{
    public function __construct(private MailConfigurationService $mailConfiguration) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->mailConfiguration->applyRuntimeConfiguration();

        return $next($request);
    }
}
