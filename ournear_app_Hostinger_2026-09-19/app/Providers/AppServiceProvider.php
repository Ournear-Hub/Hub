<?php

namespace App\Providers;

use App\Enums\MasterEntityType;
use App\Models\Application;
use App\Models\CandidateDocument;
use App\Models\CandidateEmploymentHistory;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\Conversation;
use App\Models\Employee;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use App\Models\Freelancer;
use App\Models\EmployerSubmission;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\LoginLog;
use App\Models\Placement;
use App\Models\Replacement;
use App\Models\Role;
use App\Models\SecurityActivity;
use App\Models\User;
use App\Policies\ApplicationPolicy;
use App\Policies\CandidateDocumentPolicy;
use App\Policies\CandidateEmploymentHistoryPolicy;
use App\Policies\CandidateProfilePolicy;
use App\Policies\CandidateResumePolicy;
use App\Policies\ConversationPolicy;
use App\Policies\DatabaseNotificationPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\EmployerEnquiryPolicy;
use App\Policies\EmployerProfilePolicy;
use App\Policies\EmployerSubmissionPolicy;
use App\Policies\FreelancerPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\JobPolicy;
use App\Policies\LoginLogPolicy;
use App\Policies\MasterDataPolicy;
use App\Policies\PlacementPolicy;
use App\Policies\ReplacementPolicy;
use App\Policies\RolePolicy;
use App\Policies\SecurityActivityPolicy;
use App\Policies\UserPolicy;
use App\Services\AnalyticsService;
use App\Services\PublicSeoService;
use App\Services\WebsiteSettingService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
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
        Gate::policy(Freelancer::class, FreelancerPolicy::class);
        Gate::policy(CandidateProfile::class, CandidateProfilePolicy::class);
        Gate::policy(CandidateEmploymentHistory::class, CandidateEmploymentHistoryPolicy::class);
        Gate::policy(CandidateResume::class, CandidateResumePolicy::class);
        Gate::policy(CandidateDocument::class, CandidateDocumentPolicy::class);
        Gate::policy(EmployerProfile::class, EmployerProfilePolicy::class);
        Gate::policy(EmployerSubmission::class, EmployerSubmissionPolicy::class);
        Gate::policy(Placement::class, PlacementPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Replacement::class, ReplacementPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(LoginLog::class, LoginLogPolicy::class);
        Gate::policy(SecurityActivity::class, SecurityActivityPolicy::class);
        Gate::policy(DatabaseNotification::class, DatabaseNotificationPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);

        foreach (MasterEntityType::cases() as $type) {
            Gate::policy($type->modelClass(), MasterDataPolicy::class);
        }

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

        RateLimiter::for('freelancer-enquiries', function (Request $request) {
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

        RateLimiter::for('account-password-change', function (Request $request) {
            return Limit::perMinute(5)->by((string) $request->user()?->id.'|'.$request->ip());
        });

        RateLimiter::for('account-photo', function (Request $request) {
            return Limit::perMinute(10)->by((string) $request->user()?->id.'|'.$request->ip());
        });

        RateLimiter::for('account-files', function (Request $request) {
            return Limit::perMinute(10)->by((string) $request->user()?->id.'|'.$request->ip());
        });

        RateLimiter::for('account-saved-jobs', function (Request $request) {
            return Limit::perMinute(30)->by((string) $request->user()?->id.'|'.$request->ip());
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
