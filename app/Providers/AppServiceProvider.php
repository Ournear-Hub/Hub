<?php

namespace App\Providers;

use App\Contracts\SmsOtpGateway;
use App\Contracts\WhatsAppGateway;
use App\Enums\MasterEntityType;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\Campaign;
use App\Models\CandidateDocument;
use App\Models\CandidateEmploymentHistory;
use App\Models\CandidateProfile;
use App\Models\CandidateResume;
use App\Models\ChannelPartner;
use App\Models\ChannelPartnerAgreement;
use App\Models\CommissionEntry;
use App\Models\CommissionPayment;
use App\Models\Conversation;
use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\Employee;
use App\Models\EmployerAttribution;
use App\Models\EmployerDocument;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use App\Models\Freelancer;
use App\Models\InternalCandidateResume;
use App\Models\EmployerSubmission;
use App\Models\InvestmentOpportunity;
use App\Models\Investor;
use App\Models\InvestorInterest;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\LoginLog;
use App\Models\MessageTemplate;
use App\Models\Placement;
use App\Models\Referral;
use App\Models\ReferralLink;
use App\Models\RecruitmentAutomation;
use App\Models\RelationshipReview;
use App\Models\Replacement;
use App\Models\ShareEvent;
use App\Models\Role;
use App\Models\ScorecardTemplate;
use App\Models\SecurityActivity;
use App\Models\Task;
use App\Models\User;
use App\Policies\ApplicationInterviewPolicy;
use App\Policies\ApplicationPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\CandidateDocumentPolicy;
use App\Policies\CandidateEmploymentHistoryPolicy;
use App\Policies\CandidateProfilePolicy;
use App\Policies\CandidateResumePolicy;
use App\Policies\ChannelPartnerAgreementPolicy;
use App\Policies\ChannelPartnerPolicy;
use App\Policies\CommissionEntryPolicy;
use App\Policies\CommissionPaymentPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\CrmLeadPolicy;
use App\Policies\CrmOpportunityPolicy;
use App\Policies\DatabaseNotificationPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\EmployerAttributionPolicy;
use App\Policies\EmployerDocumentPolicy;
use App\Policies\EmployerEnquiryPolicy;
use App\Policies\EmployerProfilePolicy;
use App\Policies\EmployerSubmissionPolicy;
use App\Policies\FreelancerPolicy;
use App\Policies\InternalCandidateResumePolicy;
use App\Policies\InterviewScorecardPolicy;
use App\Policies\InvestmentOpportunityPolicy;
use App\Policies\InvestorInterestPolicy;
use App\Policies\InvestorPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\JobPolicy;
use App\Policies\LoginLogPolicy;
use App\Policies\MessageTemplatePolicy;
use App\Policies\MasterDataPolicy;
use App\Policies\PlacementPolicy;
use App\Policies\ReferralLinkPolicy;
use App\Policies\ReferralPolicy;
use App\Policies\RecruitmentAutomationPolicy;
use App\Policies\RelationshipReviewPolicy;
use App\Policies\ReplacementPolicy;
use App\Policies\ShareEventPolicy;
use App\Policies\RolePolicy;
use App\Policies\ScorecardTemplatePolicy;
use App\Policies\SecurityActivityPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use App\Services\AnalyticsService;
use App\Services\MailConfigurationService;
use App\Services\PublicSeoService;
use App\Services\Sms\LogSmsOtpGateway;
use App\Services\Sms\UnconfiguredSmsOtpGateway;
use App\Services\WebsiteSettingService;
use App\Services\WhatsApp\MetaWhatsAppCloudGateway;
use App\Services\WhatsApp\UnconfiguredWhatsAppGateway;
use App\Support\MobileNumber;
use App\Support\WhatsAppCloudConfig;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
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
        $this->app->singleton(SmsOtpGateway::class, function ($app): SmsOtpGateway {
            if ($app->environment('local') && config('otp.sms_driver') === 'log') {
                return $app->make(LogSmsOtpGateway::class);
            }

            return $app->make(UnconfiguredSmsOtpGateway::class);
        });
        $this->app->singleton(WhatsAppGateway::class, function ($app) {
            if (WhatsAppCloudConfig::isReady()) {
                return $app->make(MetaWhatsAppCloudGateway::class);
            }

            return $app->make(UnconfiguredWhatsAppGateway::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Http::globalOptions([
            'connect_timeout' => 5,
            'timeout' => 10,
        ]);

        if (\PHP_OS_FAMILY === 'Windows' && $this->app->runningInConsole()) {
            $_ENV['SYSTEMROOT'] ??= getenv('SYSTEMROOT') ?: ($_ENV['SystemRoot'] ?? 'C:\\Windows');
            $_ENV['PATH'] ??= getenv('PATH') ?: ($_ENV['Path'] ?? '');
        }

        Gate::policy(Job::class, JobPolicy::class);
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(ApplicationInterview::class, ApplicationInterviewPolicy::class);
        Gate::policy(ScorecardTemplate::class, ScorecardTemplatePolicy::class);
        Gate::policy(\App\Models\InterviewScorecard::class, InterviewScorecardPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(CrmLead::class, CrmLeadPolicy::class);
        Gate::policy(CrmOpportunity::class, CrmOpportunityPolicy::class);
        Gate::policy(ChannelPartner::class, ChannelPartnerPolicy::class);
        Gate::policy(ChannelPartnerAgreement::class, ChannelPartnerAgreementPolicy::class);
        Gate::policy(EmployerAttribution::class, EmployerAttributionPolicy::class);
        Gate::policy(CommissionEntry::class, CommissionEntryPolicy::class);
        Gate::policy(CommissionPayment::class, CommissionPaymentPolicy::class);
        Gate::policy(Investor::class, InvestorPolicy::class);
        Gate::policy(InvestmentOpportunity::class, InvestmentOpportunityPolicy::class);
        Gate::policy(InvestorInterest::class, InvestorInterestPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(MessageTemplate::class, MessageTemplatePolicy::class);
        Gate::policy(RecruitmentAutomation::class, RecruitmentAutomationPolicy::class);
        Gate::policy(ReferralLink::class, ReferralLinkPolicy::class);
        Gate::policy(ShareEvent::class, ShareEventPolicy::class);
        Gate::policy(Referral::class, ReferralPolicy::class);
        Gate::policy(EmployerEnquiry::class, EmployerEnquiryPolicy::class);
        Gate::policy(\App\Models\StaffingCandidateSubmission::class, \App\Policies\StaffingCandidateSubmissionPolicy::class);
        Gate::policy(Freelancer::class, FreelancerPolicy::class);
        Gate::policy(CandidateProfile::class, CandidateProfilePolicy::class);
        Gate::policy(\App\Models\RecruiterTalentPoolAssignment::class, \App\Policies\RecruiterTalentPoolAssignmentPolicy::class);
        Gate::policy(
            \App\Models\CompanyRelationshipRecruiterAssignment::class,
            \App\Policies\CompanyRelationshipRecruiterAssignmentPolicy::class,
        );
        Gate::policy(
            \App\Models\TalentPoolCompanySubmission::class,
            \App\Policies\TalentPoolCompanySubmissionPolicy::class,
        );
        Gate::policy(CandidateEmploymentHistory::class, CandidateEmploymentHistoryPolicy::class);
        Gate::policy(CandidateResume::class, CandidateResumePolicy::class);
        Gate::policy(InternalCandidateResume::class, InternalCandidateResumePolicy::class);
        Gate::policy(CandidateDocument::class, CandidateDocumentPolicy::class);
        Gate::policy(EmployerDocument::class, EmployerDocumentPolicy::class);
        Gate::policy(EmployerProfile::class, EmployerProfilePolicy::class);
        Gate::policy(EmployerSubmission::class, EmployerSubmissionPolicy::class);
        Gate::policy(Placement::class, PlacementPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(RelationshipReview::class, RelationshipReviewPolicy::class);
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
            if ($this->app->environment('testing')) {
                return Limit::perHour(10000)->by('job-applications-testing');
            }

            $user = $request->user();
            if ($user !== null) {
                return Limit::perHour(8)->by('candidate-user:'.$user->getAuthIdentifier());
            }

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

        RateLimiter::for('account-mobile-otp', function (Request $request) {
            $user = $request->user();
            $normalized = is_string($user?->mobile)
                ? MobileNumber::normalize($user->mobile)
                : null;
            $mobileKey = is_string($normalized) ? $normalized : 'none';

            return [
                Limit::perMinutes(10, 5)->by('user:'.(string) ($user?->id ?? 'guest')),
                Limit::perMinutes(10, 5)->by('mobile:'.$mobileKey),
                Limit::perMinutes(10, 20)->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('whatsapp-webhook', function (Request $request) {
            return Limit::perMinute(120)->by('ip:'.$request->ip());
        });

        RateLimiter::for('account-mobile-otp-verify', function (Request $request) {
            return Limit::perMinute(20)->by((string) $request->user()?->id.'|'.$request->ip());
        });

        RateLimiter::for('account-whatsapp-otp', function (Request $request) {
            $user = $request->user();
            $normalized = is_string($user?->whatsapp_number)
                ? \App\Support\UserWhatsAppNumber::normalize($user->whatsapp_number)
                : null;
            $numberKey = is_string($normalized) ? $normalized : 'none';

            return [
                Limit::perMinutes(10, 5)->by('wa-user:'.(string) ($user?->id ?? 'guest')),
                Limit::perMinutes(10, 5)->by('wa-number:'.$numberKey),
                Limit::perMinutes(10, 20)->by('wa-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('account-whatsapp-otp-verify', function (Request $request) {
            return Limit::perMinute(20)->by('wa-verify:'.(string) $request->user()?->id.'|'.$request->ip());
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

        Event::listen(JobProcessing::class, function (JobProcessing $event): void {
            if ($event->job->getQueue() !== null) {
                app(MailConfigurationService::class)->applyRuntimeConfiguration();
            }
        });

        Event::listen(MessageSending::class, function (MessageSending $event): void {
            app(MailConfigurationService::class)->applyRuntimeConfiguration();
            app(WebsiteSettingService::class)->applyToSymfonyMessage($event->message);
        });
    }
}
