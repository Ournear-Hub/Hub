<?php

namespace App\Services;

use App\Models\SocialLink;

class SocialLinkService
{
    /**
     * @return list<array{platform: string, label: string, sort_order: int}>
     */
    public static function defaults(): array
    {
        return [
            ['platform' => 'linkedin', 'label' => 'LinkedIn', 'sort_order' => 0],
            ['platform' => 'facebook', 'label' => 'Facebook', 'sort_order' => 1],
            ['platform' => 'instagram', 'label' => 'Instagram', 'sort_order' => 2],
            ['platform' => 'youtube', 'label' => 'YouTube', 'sort_order' => 3],
            ['platform' => 'whatsapp', 'label' => 'WhatsApp', 'sort_order' => 4],
            ['platform' => 'telegram', 'label' => 'Telegram', 'sort_order' => 5],
        ];
    }

    public function ensureSeeded(): void
    {
        if (SocialLink::query()->exists()) {
            return;
        }

        foreach (self::defaults() as $row) {
            SocialLink::query()->create([
                'platform' => $row['platform'],
                'label' => $row['label'],
                'url' => null,
                'is_enabled' => false,
                'sort_order' => $row['sort_order'],
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function adminLinks(): array
    {
        $this->ensureSeeded();

        return SocialLink::query()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SocialLink $link) => $link->toAdminArray())
            ->all();
    }

    /**
     * @return list<array{platform: string, label: string, href: string}>
     */
    public function publicLinks(): array
    {
        $this->ensureSeeded();

        return SocialLink::query()
            ->where('is_enabled', true)
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (SocialLink $link) => $this->isPublicHttpUrl((string) $link->url))
            ->map(fn (SocialLink $link) => $link->toPublicArray())
            ->values()
            ->all();
    }

    private function isPublicHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }

    /**
     * @param  list<array<string, mixed>>  $links
     */
    public function update(array $links): void
    {
        $this->ensureSeeded();

        foreach ($links as $row) {
            $link = SocialLink::query()->whereKey($row['id'])->first();
            if (! $link) {
                continue;
            }

            $link->update([
                'url' => filled($row['url'] ?? null) ? trim((string) $row['url']) : null,
                'is_enabled' => filter_var($row['is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
