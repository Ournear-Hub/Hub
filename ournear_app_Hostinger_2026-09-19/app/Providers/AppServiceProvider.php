<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\EmployerEnquiry;
use App\Models\Job;
use App\Models\User;
use App\Policies\ApplicationPolicy;
use App\Policies\CandidateProfilePolicy;
use App\Policies\EmployerEnquiryPolicy;
use App\Policies\JobPolicy;
use App\Policies\UserPolicy;
use App\Services\AnalyticsService;
use App\Services\PublicSeoService;
use App\Services\WebsiteSettingService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Job::class, JobPolicy::class);
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(EmployerEnquiry::class, EmployerEnquiryPolicy::class);
        Gate::policy(CandidateProfile::class, CandidateProfilePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('admin-password-reset', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('job-applications', function (Request $request) {
            return [
                Limit::perHour(8)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
                Limit::perHour(20)->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('employer-enquiries', function (Request $request) {
            return [
                Limit::perHour(6)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
                Limit::perHour(20)->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('contact-enquiries', function (Request $request) {
            return [
                Limit::perHour(6)->by(strtolower((string) $request->input('email').$request->input('mobile_number')).'|'.$request->ip()),
                Limit::perHour(20)->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('account-login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('account-register', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('account-password-reset', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('password-reset-submit', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $email = $notifiable->getEmailForPasswordReset();
            $route = $notifiable instanceof User && $notifiable->isAdmin()
                ? 'admin.password.reset'
                : 'password.reset';

            return url(route($route, [
                'token' => $token,
                'email' => $email,
            ], false));
        });

        if ($this->app->environment('production')) {
            $appUrl = (string) config('app.url');
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        View::composer('app', function ($view): void {
            $request = request();
            $view->with('publicSeo', app(PublicSeoService::class)->forRequest($request));
            $view->with('publicAnalytics', app(AnalyticsService::class)->publicPayload($request));
            $view->with('publicBranding', app(WebsiteSettingService::class)->publicBranding());
        });
    }
}
