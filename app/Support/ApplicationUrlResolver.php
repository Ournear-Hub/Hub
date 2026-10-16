<?php

namespace App\Support;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

final class ApplicationUrlResolver
{
    private static ?bool $websiteSettingsReadable = null;

    public function effectiveRootUrl(): ?string
    {
        $fromSettings = $this->websitePublicUrl();
        if ($fromSettings !== null) {
            return $fromSettings;
        }

        $configured = rtrim(trim((string) config('app.url', '')), '/');
        if ($configured === '' || $configured === 'http://localhost') {
            return null;
        }

        return $configured;
    }

    public function applyForUrlGeneration(): void
    {
        $root = $this->effectiveRootUrl();
        if ($root === null) {
            return;
        }

        URL::forceRootUrl($root);

        if (str_starts_with($root, 'https://')) {
            URL::forceScheme('https');
        }
    }

    public function applyAtBoot(): void
    {
        if (! $this->shouldReadWebsiteSettings()) {
            return;
        }

        $this->applyForUrlGeneration();
    }

    private function websitePublicUrl(): ?string
    {
        if (! $this->shouldReadWebsiteSettings()) {
            return null;
        }

        $row = WebsiteSetting::query()->where('lock_key', 'default')->first()
            ?? WebsiteSetting::query()->first();

        if ($row === null) {
            return null;
        }

        $url = trim((string) ($row->website_url ?? ''));
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return rtrim($url, '/');
    }

    private function shouldReadWebsiteSettings(): bool
    {
        if (self::$websiteSettingsReadable !== null) {
            return self::$websiteSettingsReadable;
        }

        try {
            self::$websiteSettingsReadable = Schema::hasTable('website_settings')
                && Schema::hasColumn('website_settings', 'website_url');
        } catch (\Throwable) {
            self::$websiteSettingsReadable = false;
        }

        return self::$websiteSettingsReadable;
    }
}
