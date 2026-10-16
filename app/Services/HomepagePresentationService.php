<?php

namespace App\Services;

use App\Models\ContentItem;
use App\Models\Industry;

class HomepagePresentationService
{
    public function __construct(private WebsiteContentService $cms) {}

    /**
     * @return list<array{
     *     title: string,
     *     body: string|null,
     *     image_url: string|null,
     *     primary_label: string|null,
     *     primary_href: string|null,
     *     secondary_label: string|null,
     *     secondary_href: string|null,
     *     image_object_position: string|null
     * }>
     */
    public function heroSlides(): array
    {
        $this->cms->ensureHomepageSections();
        $page = $this->cms->publicPage('home');
        $sections = collect($page['sections'] ?? []);
        $slider = $sections->firstWhere('key', 'hero_slider');
        $items = collect($slider['items'] ?? [])
            ->where('is_active', true)
            ->sortBy(fn (array $item) => (int) ($item['sort_order'] ?? 0))
            ->values();

        if ($items->isEmpty()) {
            $hero = $sections->firstWhere('key', 'hero');

            return [[
                'title' => (string) ($hero['heading'] ?? 'Recruit faster with one connected hiring platform.'),
                'body' => $hero['body'] ?? null,
                'image_url' => $hero['image_url'] ?? null,
                'primary_label' => $hero['primary_button_label'] ?? null,
                'primary_href' => filled($hero['primary_button_label'] ?? null) ? '/hire' : null,
                'secondary_label' => $hero['secondary_button_label'] ?? null,
                'secondary_href' => filled($hero['secondary_button_label'] ?? null) ? '/jobs' : null,
                'image_object_position' => null,
            ]];
        }

        return $items->map(function (array $item) {
            $bullets = array_values($item['bullets'] ?? []);

            return [
                'title' => (string) ($item['title'] ?? ''),
                'body' => filled($item['description'] ?? null) ? (string) $item['description'] : null,
                'image_url' => $item['image_url'] ?? null,
                'primary_label' => filled($bullets[0] ?? null) ? (string) $bullets[0] : null,
                'primary_href' => filled($bullets[1] ?? null) ? (string) $bullets[1] : null,
                'secondary_label' => filled($bullets[2] ?? null) ? (string) $bullets[2] : null,
                'secondary_href' => filled($bullets[3] ?? null) ? (string) $bullets[3] : null,
                'image_object_position' => $this->heroImageObjectPosition($item),
            ];
        })
            ->filter(fn (array $slide) => $slide['title'] !== '' || filled($slide['image_url']))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function heroImageObjectPosition(array $item): ?string
    {
        $icon = is_string($item['icon'] ?? null) ? trim((string) $item['icon']) : '';
        if ($icon !== '' && str_contains($icon, ' ')) {
            return $icon;
        }

        return null;
    }

    /**
     * @return list<array{title: string, body: string, icon: string, href: string}>
     */
    public function audienceFeatureCards(string $sectionKey): array
    {
        $this->cms->ensureHomepageSections();
        $page = $this->cms->publicPage('home');
        $section = collect($page['sections'] ?? [])->firstWhere('key', $sectionKey);
        $items = collect($section['items'] ?? [])->where('is_active', true)->values();

        return $items->map(function (array $item) {
            $bullets = $item['bullets'] ?? [];
            $href = is_string($bullets[0] ?? null) && $bullets[0] !== '' ? $bullets[0] : '/jobs';

            return [
                'title' => (string) ($item['title'] ?? ''),
                'body' => (string) ($item['description'] ?? ''),
                'icon' => (string) ($item['icon'] ?? 'briefcase'),
                'href' => $href,
            ];
        })->filter(fn (array $card) => $card['title'] !== '')->values()->all();
    }

    /**
     * @return array{heading: string, body: string|null, primary_label: string|null, primary_href: string|null, image_url: string|null}|null
     */
    public function promotionalBanner(): ?array
    {
        $this->cms->ensureHomepageSections();
        $page = $this->cms->publicPage('home');
        $section = collect($page['sections'] ?? [])->firstWhere('key', 'promo_banner');
        $extra = is_array($section['extra'] ?? null) ? $section['extra'] : [];
        $primaryHref = is_string($extra['primary_href'] ?? null) ? $extra['primary_href'] : '/hire';
        $imageUrl = is_array($section) ? ($section['image_url'] ?? null) : null;

        return [
            'eyebrow' => 'ONE PLATFORM. EVERY HIRING NEED.',
            'heading' => (string) (is_array($section) ? ($section['heading'] ?? 'Smarter Hiring. Better Teams.') : 'Smarter Hiring. Better Teams.'),
            'body' => is_array($section) && filled($section['body'] ?? null)
                ? (string) $section['body']
                : 'Use OurNear Hire to post jobs, manage talent and coordinate recruitment through one connected platform.',
            'primary_label' => is_array($section) ? ($section['primary_button_label'] ?? 'Explore ATS') : 'Explore ATS',
            'primary_href' => $primaryHref,
            'image_url' => $imageUrl,
        ];
    }

    /**
     * @return array{image_url: string|null, heading: string|null, body: string|null}|null
     */
    public function latestJobsSection(): ?array
    {
        $this->cms->ensureHomepageSections();
        $page = $this->cms->publicPage('home');
        $section = collect($page['sections'] ?? [])->firstWhere('key', 'jobs_product');
        if ($section === null || ($section['is_active'] ?? true) === false) {
            return null;
        }

        return [
            'image_url' => $section['image_url'] ?? null,
            'heading' => $section['heading'] ?? null,
            'body' => $section['body'] ?? null,
        ];
    }

    /**
     * @return list<array{title: string, description: string|null, icon: string|null}>
     */
    public function industries(): array
    {
        $master = Industry::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($master->isNotEmpty()) {
            return $master->map(fn (Industry $industry) => [
                'title' => (string) $industry->name,
                'description' => $industry->description,
                'icon' => $this->industryIconName((string) $industry->name),
            ])->all();
        }

        return ContentItem::query()
            ->where('group_key', 'industries')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ContentItem $item) => [
                'title' => $item->title,
                'description' => $item->description,
                'icon' => $item->icon ?? $this->industryIconName((string) $item->title),
            ])
            ->all();
    }

    private function industryIconName(string $name): string
    {
        $key = strtolower(trim($name));

        return match (true) {
            str_contains($key, 'mining') => 'building',
            str_contains($key, 'infrastructure') => 'building',
            str_contains($key, 'construction') => 'building',
            str_contains($key, 'real estate') => 'building',
            str_contains($key, 'oil') || str_contains($key, 'gas') || str_contains($key, 'petrochemical') => 'building',
            str_contains($key, 'renewable') || str_contains($key, 'energy') || str_contains($key, 'power') => 'building',
            str_contains($key, 'aviation') || str_contains($key, 'aerospace') || str_contains($key, 'rail') => 'search',
            str_contains($key, 'logistics') || str_contains($key, 'transport') => 'search',
            str_contains($key, 'e-commerce') || str_contains($key, 'retail') || str_contains($key, 'fmcg') => 'briefcase',
            str_contains($key, 'manufacturing') || str_contains($key, 'automotive') || str_contains($key, 'textile') => 'briefcase',
            str_contains($key, 'information technology') || str_contains($key, 'telecom') => 'settings',
            str_contains($key, 'health') || str_contains($key, 'education') || str_contains($key, 'hospitality') => 'user',
            str_contains($key, 'human resources') || str_contains($key, 'recruitment') => 'user',
            str_contains($key, 'security') => 'alert',
            str_contains($key, 'media') || str_contains($key, 'entertainment') => 'share',
            str_contains($key, 'consult') || str_contains($key, 'business service') => 'message',
            str_contains($key, 'pharma') || str_contains($key, 'life science') => 'check',
            str_contains($key, 'bank') || str_contains($key, 'financial') => 'briefcase',
            str_contains($key, 'agri') => 'briefcase',
            str_contains($key, 'food') => 'briefcase',
            str_contains($key, 'government') || str_contains($key, 'public service') => 'building',
            str_contains($key, 'facilities') || str_contains($key, 'office') => 'building',
            default => 'briefcase',
        };
    }
}
