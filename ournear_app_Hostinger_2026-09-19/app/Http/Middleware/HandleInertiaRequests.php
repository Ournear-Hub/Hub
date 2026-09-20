<?php

namespace App\Http\Middleware;

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
                        'role' => $request->user()->role->value,
                    ]
                    : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'status' => $request->session()->get('status'),
            ],
            'publicContent' => fn () => app(WebsiteContentService::class)->common(),
            'socialLinks' => fn () => app(SocialLinkService::class)->publicLinks(),
            'seo' => fn () => app(WebsiteSettingService::class)->publicSeo(),
            'branding' => fn () => app(WebsiteSettingService::class)->publicBranding(),
            'pageSeo' => fn () => app(PublicSeoService::class)->forRequest($request),
            'analytics' => fn () => app(AnalyticsService::class)->publicPayload($request),
            'turnstileSiteKey' => fn () => app(TurnstileService::class)->siteKey(),
        ];
    }
}
