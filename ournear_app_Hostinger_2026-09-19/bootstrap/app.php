<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = env('TRUSTED_PROXIES');
        if (is_string($trustedProxies) && trim($trustedProxies) !== '') {
            $at = trim($trustedProxies) === '*'
                ? '*'
                : array_values(array_filter(array_map('trim', explode(',', $trustedProxies))));

            $middleware->trustProxies(at: $at);
        }

        $middleware->web(append: [
            \App\Http\Middleware\EnsureActiveUser::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'password.change' => \App\Http\Middleware\EnsurePasswordChanged::class,
            'account.member' => \App\Http\Middleware\EnsureAccountMember::class,
            'account.candidate' => \App\Http\Middleware\EnsureCandidate::class,
            'account.employer' => \App\Http\Middleware\EnsureEmployer::class,
        ]);

        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            $user = $request->user();

            if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return route('admin.dashboard');
            }

            return route('account.home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
