<?php

namespace App\Http\Middleware;

use App\Models\Job;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountEmailVerified
{
    /**
     * Email verification is required only when submitting a job application.
     *
     * @var list<string>
     */
    private const APPLICATION_SUBMIT_ROUTES = [
        'jobs.apply.store',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isCandidate() || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        if (! in_array($routeName, self::APPLICATION_SUBMIT_ROUTES, true)) {
            return $next($request);
        }

        $message = 'Verify your email to apply for jobs. You can continue browsing jobs and completing your profile while you verify your email.';

        if ($request->expectsJson()) {
            abort(403, $message);
        }

        $job = $request->route('job');
        $parameters = $job instanceof Job ? $job : ['job' => $job];

        return redirect()
            ->route('jobs.apply', $parameters)
            ->withErrors(['email' => $message]);
    }
}
