<?php

namespace App\Http\Middleware;

use App\Enums\PermissionName;
use App\Models\User;
use App\Support\AccountPortalNavigation;
use App\Support\AdminNavigationCatalog;
use App\Support\CandidateAccountNav;
use App\Support\EmployerAccountNav;
use App\Support\MobileBrowserExperience;
use App\Support\PublicHeaderAccountNav;
use App\Services\AccountDashboardService;
use App\Services\CandidateNotificationService;
use App\Support\NotificationQuery;
use App\Services\AnalyticsService;
use App\Services\PublicSeoService;
use App\Services\SocialLinkService;
use App\Services\TurnstileService;
use App\Services\WebsiteContentService;
use App\Services\WebsiteSettingService;
use App\Support\PublicShareUrlBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        $homeSource = resource_path('js/Pages/Home.tsx');
        $homeMtime = is_file($homeSource) ? (string) filemtime($homeSource) : '';
        $hotPath = public_path('hot');
        $hotMarker = is_file($hotPath) ? (string) filemtime($hotPath) : 'no-hot';

        return hash('xxh128', implode('|', [
            (string) parent::version($request),
            $homeMtime,
            $hotMarker,
        ]));
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => $this->authProps($request),
            'emailVerificationReminder' => function () use ($request) {
                $user = $this->accountUser($request);
                if (! $user instanceof User || ! $user->isCandidate() || $user->hasVerifiedEmail()) {
                    return null;
                }

                if (! $request->session()->get('prompt_email_verification')) {
                    return null;
                }

                return [
                    'email' => $user->email,
                ];
            },
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'status' => $request->session()->get('status'),
                'registered_email' => $request->session()->get('registered_email'),
                'freelancer_confirmation' => $request->session()->get('freelancer_confirmation'),
                'application_confirmation' => $request->session()->get('application_confirmation'),
                'duplicate_matches' => $request->session()->get('duplicate_matches'),
                'mobile_otp_retry_after' => $request->session()->get('mobile_otp_retry_after'),
                'whatsapp_otp_retry_after' => $request->session()->get('whatsapp_otp_retry_after'),
                'masterDataImportPreview' => $request->session()->get('masterDataImportPreview'),
                'masterDataImportOutcome' => $request->session()->get('masterDataImportOutcome'),
            ],
            'adminAlerts' => function () use ($request) {
                $user = $this->adminUser($request);
                if (! $user instanceof User || ! $user->hasPermission(PermissionName::NotificationsView->value)) {
                    return null;
                }

                return [
                    'unread' => $user->unreadNotifications()->count(),
                    'recent' => $user->notifications()
                        ->latest()
                        ->limit(8)
                        ->get()
                        ->map(fn ($notification) => NotificationQuery::toAdminArray($notification))
                        ->values()
                        ->all(),
                ];
            },
            'accountAlerts' => function () use ($request) {
                $user = $this->accountUser($request);
                if (! $user instanceof User) {
                    return null;
                }

                if ($user->isCandidate() || $user->canAccessHiringWorkspace()) {
                    return [
                        'unread' => $user->unreadNotifications()->count(),
                        'recent' => $user->notifications()
                            ->latest()
                            ->limit(8)
                            ->get()
                            ->map(fn ($notification) => CandidateNotificationService::toAccountArray($notification))
                            ->values()
                            ->all(),
                    ];
                }

                return null;
            },
            'candidateNav' => function () use ($request) {
                $user = $this->accountUser($request);

                return $user instanceof User && $user->isCandidate()
                    ? CandidateAccountNav::items()
                    : null;
            },
            'candidateSidebarProfile' => function () use ($request) {
                $user = $this->accountUser($request);
                if (! $user instanceof User || ! $user->isCandidate()) {
                    return null;
                }

                return app(AccountDashboardService::class)->candidateSidebarProfile($user);
            },
            'employerNav' => function () use ($request) {
                $user = $this->accountUser($request);
                if (! $user instanceof User) {
                    return null;
                }

                if ($user->isEmployer()) {
                    return AccountPortalNavigation::employerNavItemsFor($user);
                }

                return $this->isAccountHiringWorkspaceRequest($request)
                    ? AccountPortalNavigation::employerNavItemsFor($user)
                    : null;
            },
            'headerAccountNav' => function () use ($request) {
                $user = $this->accountUser($request);

                return $user instanceof User ? PublicHeaderAccountNav::personalHeaderItems($user) : null;
            },
            'companyHeaderNav' => function () use ($request) {
                $user = $this->accountUser($request);

                return $user instanceof User && AccountPortalNavigation::showsCompanyWorkspaceAvatar($user)
                    ? PublicHeaderAccountNav::companyWorkspaceHeaderItems($user)
                    : null;
            },
            'adminNav' => function () use ($request) {
                $user = $this->adminUser($request);
                if (! $user instanceof User || ! $user->isAdmin()) {
                    return null;
                }

                return AdminNavigationCatalog::visibleSectionsFor($user);
            },
            'publicContent' => fn () => app(WebsiteContentService::class)->common(),
            'socialLinks' => fn () => app(SocialLinkService::class)->publicLinks(),
            'seo' => fn () => app(WebsiteSettingService::class)->publicSeo(),
            'branding' => fn () => app(WebsiteSettingService::class)->publicBranding(),
            'pageSeo' => fn () => app(PublicSeoService::class)->forRequest($request),
            'analytics' => fn () => app(AnalyticsService::class)->publicPayload($request),
            'turnstileSiteKey' => fn () => app(TurnstileService::class)->siteKey(),
            'sharing' => fn () => [
                'public_base_url' => app(PublicShareUrlBuilder::class)->publicBaseUrl(),
            ],
            'ui' => [
                'mobile_profile_view_only' => MobileBrowserExperience::isMobileBrowser($request),
            ],
            'locale' => function () use ($request) {
                $user = $this->currentUser($request);
                if ($user instanceof User && $user->preferred_language instanceof \App\Enums\PreferredLanguage) {
                    return $user->preferred_language->value;
                }

                return config('app.locale', 'en');
            },
        ];
    }

    /**
     * @return array{user: array<string, mixed>|null, admin: array<string, mixed>|null, account: array<string, mixed>|null}
     */
    private function authProps(Request $request): array
    {
        $onAdmin = $this->onAdminSurface($request);
        $admin = $onAdmin ? $this->userPayload($this->adminUser($request)) : null;
        $account = $onAdmin ? null : $this->userPayload($this->accountUser($request));

        return [
            'user' => $onAdmin ? $admin : $account,
            'admin' => $admin,
            'account' => $account,
        ];
    }

    private function currentUser(Request $request): ?User
    {
        return $this->onAdminSurface($request) ? $this->adminUser($request) : $this->accountUser($request);
    }

    private function adminUser(Request $request): ?User
    {
        if (! $this->onAdminSurface($request)) {
            return null;
        }

        $user = Auth::guard('admin')->user();

        return $user instanceof User ? $user : null;
    }

    private function accountUser(Request $request): ?User
    {
        if ($this->onAdminSurface($request)) {
            return null;
        }

        $user = Auth::guard('account')->user();

        return $user instanceof User ? $user : null;
    }

    private function onAdminSurface(Request $request): bool
    {
        return $request->is('admin') || $request->is('admin/*');
    }

    private function isAccountHiringWorkspaceRequest(Request $request): bool
    {
        return \App\Support\AccountRecruiterWorkspacePaths::matchesRequest($request);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function userPayload(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        $portalNavigation = AccountPortalNavigation::for($user);
        $employerIdentity = AccountPortalNavigation::companyWorkspaceIdentity($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'display_name' => $user->name,
            'employer_identity' => $employerIdentity,
            'portal_navigation' => $portalNavigation,
            'email' => $user->email,
            'username' => $user->username,
            'business_id' => $user->business_id,
            'ournear_id' => app(\App\Support\OurNearHireIdentity::class)->display($user),
            'role' => $user->role->value,
            'photo_url' => $user->photoUrl(),
            'must_change_password' => (bool) $user->must_change_password,
            'preferred_language' => $user->preferred_language instanceof \App\Enums\PreferredLanguage
                ? $user->preferred_language->value
                : 'en',
            'email_verified' => $user->emailVerified(),
            'mobile_verified' => $user->mobileVerified(),
            'whatsapp_verified' => $user->whatsappVerified(),
            'profile_completion' => $user->calculateProfileCompletion(),
            'permissions' => $user->isAdmin() ? $user->permissionNames() : [],
            'can_view_analytics' => $user->isAdmin() && $user->canViewOperationalAnalytics(),
            'can_view_hiring' => $user->isAdmin() && $user->canViewHiringCommandCenter(),
        ];
    }
}
