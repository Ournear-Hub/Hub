<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicSeoService
{
    public function __construct(
        private WebsiteSettingService $settings,
        private WebsiteContentService $cms,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function forRequest(Request $request): ?array
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            return null;
        }

        if ($this->isAccountRequest($request)) {
            return $this->forAccountPage($request);
        }

        $name = $request->route()?->getName();

        return match ($name) {
            'jobs.show' => $this->forJob($this->jobFrom($request)),
            'jobs.index' => $this->forJobsIndex(),
            'jobs.apply' => $this->forApply($this->jobFrom($request)),
            'home' => $this->forCmsPage('Home', '/', 'home'),
            'about' => $this->forCmsPage('About Us', '/about', 'about'),
            'services' => $this->forCmsPage('Services', '/services', 'services'),
            'for-employers' => $this->forCmsPage('For Employers', '/for-employers', 'for-employers'),
            'contact' => $this->forCmsPage('Contact Us', '/contact', 'contact'),
            'privacy-policy' => $this->forCmsPage('Privacy Policy', '/privacy-policy', 'privacy-policy'),
            'terms' => $this->forCmsPage('Terms & Conditions', '/terms-and-conditions', 'terms-and-conditions'),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function forJob(?Job $job): array
    {
        if ($job === null) {
            return $this->forJobsIndex();
        }

        $site = $this->settings->publicSeo();
        $title = trim($job->title.' · '.$job->location);
        $description = $this->jobDescription($job);
        $canonical = $this->absolute('/jobs/'.$job->slug);
        $ogImage = $this->ogImage();

        return $this->pack(
            title: $title,
            description: $description,
            canonical: $canonical,
            robots: 'index,follow',
            ogType: 'article',
            ogImage: $ogImage,
            siteName: $site['site_name'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function forJobsIndex(): array
    {
        $site = $this->settings->publicSeo();
        $description = $site['default_description'] !== ''
            ? $site['default_description']
            : 'Browse open roles at OurNear Talent. Apply directly — no candidate account is required.';

        return $this->pack(
            title: 'Jobs',
            description: $description,
            canonical: $this->absolute('/jobs'),
            robots: 'index,follow',
            ogType: 'website',
            ogImage: $this->ogImage(),
            siteName: $site['site_name'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function forApply(?Job $job): array
    {
        $site = $this->settings->publicSeo();
        $title = $job ? 'Apply · '.$job->title : 'Apply';
        $canonical = $job
            ? $this->absolute('/jobs/'.$job->slug.'/apply')
            : $this->absolute('/jobs');

        return $this->pack(
            title: $title,
            description: 'Submit an application for this role. Applications are handled privately by OurNear Talent.',
            canonical: $canonical,
            robots: 'noindex,nofollow',
            ogType: 'website',
            ogImage: null,
            siteName: $site['site_name'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function forAccountPage(Request $request): array
    {
        $site = $this->settings->publicSeo();
        $path = '/'.ltrim($request->path(), '/');
        $title = match ($request->route()?->getName()) {
            'login' => 'Log in',
            'register' => 'Register',
            'password.request' => 'Forgot password',
            'password.reset' => 'Reset password',
            'account.home' => 'Account',
            'account.profile.edit' => 'Profile',
            'account.applications.index' => 'Applications',
            'account.enquiries.index' => 'Enquiries',
            default => 'Account',
        };

        return $this->pack(
            title: $title,
            description: 'Private OurNear Talent account area.',
            canonical: $this->absolute($path === '' ? '/' : $path),
            robots: 'noindex,nofollow',
            ogType: 'website',
            ogImage: null,
            siteName: $site['site_name'],
        );
    }

    private function isAccountRequest(Request $request): bool
    {
        if ($request->is('login', 'register', 'forgot-password', 'account', 'account/*')) {
            return true;
        }

        if ($request->is('reset-password') || $request->is('reset-password/*')) {
            return true;
        }

        return $request->routeIs(
            'login',
            'register',
            'password.request',
            'password.reset',
            'account.*',
        );
    }

    public function robotsTxt(): string
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /jobs/*/apply',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /account',
            'Disallow: /account/',
            '',
            'Sitemap: '.$this->absolute('/sitemap.xml'),
            '',
        ];

        return implode("\n", $lines);
    }

    public function sitemapXml(): string
    {
        $urls = $this->indexableUrls();
        $rows = [];

        foreach ($urls as $url) {
            $rows[] = '  <url>'
                ."\n    <loc>".$this->xml($url['loc']).'</loc>'
                ."\n    <changefreq>".$this->xml($url['changefreq']).'</changefreq>'
                ."\n    <priority>".$this->xml($url['priority']).'</priority>'
                ."\n  </url>";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .implode("\n", $rows)."\n"
            .'</urlset>'."\n";
    }

    /**
     * @return list<array{loc: string, changefreq: string, priority: string}>
     */
    public function indexableUrls(): array
    {
        $urls = [
            ['loc' => $this->absolute('/'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => $this->absolute('/jobs'), 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => $this->absolute('/about'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => $this->absolute('/services'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => $this->absolute('/for-employers'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => $this->absolute('/contact'), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => $this->absolute('/privacy-policy'), 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => $this->absolute('/terms-and-conditions'), 'changefreq' => 'yearly', 'priority' => '0.3'],
        ];

        $jobs = Job::query()->publiclyVisible()->orderByDesc('posted_date')->get(['slug']);

        foreach ($jobs as $job) {
            $urls[] = [
                'loc' => $this->absolute('/jobs/'.$job->slug),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        return $urls;
    }

    public function absolute(string $path): string
    {
        $base = rtrim((string) ($this->settings->current()->website_url ?: config('app.url')), '/');
        $path = '/'.ltrim($path, '/');

        if ($path === '/') {
            return $base.'/';
        }

        return $base.$path;
    }

    /**
     * @return array<string, mixed>
     */
    private function forCmsPage(string $fallbackTitle, string $path, string $slug): array
    {
        $site = $this->settings->publicSeo();
        $page = $this->cms->publicPage($slug);
        $description = filled($page['meta_description'] ?? null)
            ? (string) $page['meta_description']
            : $site['default_description'];

        return $this->pack(
            title: $fallbackTitle,
            description: $description,
            canonical: $this->absolute($path),
            robots: 'index,follow',
            ogType: 'website',
            ogImage: $this->ogImage(),
            siteName: $site['site_name'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function pack(
        string $title,
        string $description,
        string $canonical,
        string $robots,
        string $ogType,
        ?string $ogImage,
        string $siteName,
    ): array {
        $ogTitle = $title.' | '.$siteName;

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og_type' => $ogType,
            'og_title' => $ogTitle,
            'og_description' => $description,
            'og_url' => $canonical,
            'og_image' => $ogImage,
            'og_site_name' => $siteName,
            'twitter_card' => $ogImage ? 'summary_large_image' : 'summary',
            'twitter_title' => $ogTitle,
            'twitter_description' => $description,
            'twitter_image' => $ogImage,
        ];
    }

    private function jobDescription(Job $job): string
    {
        $parts = array_filter([
            $job->title,
            $job->location,
            $job->employment_type,
            $job->industry,
        ]);
        $lead = implode(' · ', $parts).'.';
        $body = trim(preg_replace('/\s+/', ' ', strip_tags((string) $job->description)) ?? '');

        if ($body === '') {
            return Str::limit($lead.' Open role at OurNear Talent. Apply directly — no candidate account is required.', 160);
        }

        return Str::limit($lead.' '.$body, 160);
    }

    private function ogImage(): ?string
    {
        $page = $this->cms->publicPage('home');

        foreach ($page['sections'] ?? [] as $section) {
            if (($section['key'] ?? '') !== 'hero') {
                continue;
            }

            $url = $section['image_url'] ?? null;
            if (is_string($url) && $url !== '') {
                return str_starts_with($url, 'http') ? $url : $this->absolute($url);
            }
        }

        return null;
    }

    private function jobFrom(Request $request): ?Job
    {
        $job = $request->route('job');

        return $job instanceof Job ? $job : null;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
