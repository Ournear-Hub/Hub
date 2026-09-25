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
            'auth' => [
                'user' => $request->user() instanceof \App\Models\User
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'username' => $request->user()->username,
                        'role' => $request->user()->role->value,
                        'photo_url' => $request->user()->photoUrl(),
                        'must_change_password' => (bool) $request->user()->must_change_password,
                        'preferred_language' => $request->user()->preferred_language instanceof \App\Enums\PreferredLanguage
                            ? $request->user()->preferred_language->value
                            : 'en',
                        'email_verified' => $request->user()->emailVerified(),
                        'profile_completion' => (int) $request->user()->profile_completion,
                        'permissions' => $request->user()->isAdmin()
                            ? $request->user()->permissionNames()
                            : [],
                    ]
                    : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'status' => $request->session()->get('status'),
                'registered_email' => $request->session()->get('registered_email'),
                'freelancer_confirmation' => $request->session()->get('freelancer_confirmation'),
                'application_confirmation' => $request->session()->get('application_confirmation'),
            ],
            'adminAlerts' => function () use ($request) {
                $user = $request->user();
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
                $user = $request->user();
                if (! $user instanceof User || ! $user->isCandidate()) {
                    return null;
                }

                return [
                    'unread' => $user->unreadNotifications()->count(),
                ];
            },
            'candidateNav' => function () use ($request) {
                $user = $request->user();

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
                $user = $request->user();
                if ($user instanceof User && $user->preferred_language instanceof \App\Enums\PreferredLanguage) {
                    return $user->preferred_language->value;
                }

                return config('app.locale', 'en');
            },
        ];
    }
}
