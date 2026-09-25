<?php

namespace App\Http\Middleware;

use App\Enums\PermissionName;
use App\Models\User;
use App\Support\CandidateAccountNav;
use App\Support\NotificationQuery;
use App\Services\AnalyticsService;
use App\Services\PublicSeoService;
use App\Services\SocialLinkService;
use App\Services\TurnstileService;
use App\Services\WebsiteContentService;
use App\Services\WebsiteSettingService;
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
        return parent::version($request);
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
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'status' => $request->session()->get('status'),
                'registered_email' => $request->session()->get('registered_email'),
                'freelancer_confirmation' => $request->session()->get('freelancer_confirmation'),
                'application_confirmation' => $request->session()->get('application_confirmation'),
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
                if (! $user instanceof User || ! $user->isCandidate()) {
                    return null;
                }

                return [
                    'unread' => $user->unreadNotifications()->count(),
                ];
            },
            'candidateNav' => function () use ($request) {
                $user = $this->accountUser($request);

                return $user instanceof User && $user->isCandidate()
                    ? CandidateAccountNav::items()
                    : null;
            },
            'publicContent' => fn () => app(WebsiteContentService::class)->common(),
            'socialLinks' => fn () => app(SocialLinkService::class)->publicLinks(),
            'seo' => fn () => app(WebsiteSettingService::class)->publicSeo(),
            'branding' => fn () => app(WebsiteSettingService::class)->publicBranding(),
            'pageSeo' => fn () => app(PublicSeoService::class)->forRequest($request),
            'analytics' => fn () => app(AnalyticsService::class)->publicPayload($request),
            'turnstileSiteKey' => fn () => app(TurnstileService::class)->siteKey(),
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

    /**
     * @return array<string, mixed>|null
     */
    private function userPayload(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'role' => $user->role->value,
            'photo_url' => $user->photoUrl(),
            'must_change_password' => (bool) $user->must_change_password,
            'preferred_language' => $user->preferred_language instanceof \App\Enums\PreferredLanguage
                ? $user->preferred_language->value
                : 'en',
            'email_verified' => $user->emailVerified(),
            'profile_completion' => (int) $user->profile_completion,
            'permissions' => $user->isAdmin() ? $user->permissionNames() : [],
        ];
    }
}
