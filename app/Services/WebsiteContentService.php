<?php

namespace App\Services;

use App\Models\ContentItem;
use App\Models\MediaAsset;
use App\Models\PageSectionItem;
use App\Models\WebsitePage;
use App\Models\WebsiteSection;
use App\Support\PublicContent;
use App\Support\StaffingPageDefaultMedia;
use App\Support\WebsiteContentDefaults;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WebsiteContentService
{
    public function ensureSeeded(): void
    {
        if (! WebsitePage::query()->exists()) {
            $this->seedDefaults();
        }

        $this->ensureMissingPages();
        $this->ensureStaffingPageMediaDefaults();
    }

    public function ensureLegalPages(): void
    {
        $this->ensureMissingPages();
    }

    public function ensureHomepageSections(): void
    {
        $this->ensureSeeded();
        $this->ensureMissingHomepageSections();
        $this->ensureContentGroups([
            'home_hero_slides',
            'home_hiring_features',
            'home_candidate_features',
        ]);
        $this->syncEmptyHomeSectionItems();
    }

    public function ensureMissingPages(): void
    {
        foreach (WebsiteContentDefaults::pages() as $pageData) {
            $page = WebsitePage::query()->where('slug', $pageData['slug'])->first();

            if ($page === null) {
                $page = WebsitePage::query()->create([
                    'slug' => $pageData['slug'],
                    'title' => $pageData['title'],
                ]);
            }

            foreach ($pageData['sections'] as $sectionIndex => $sectionData) {
                if ($page->sections()->where('section_key', $sectionData['key'])->exists()) {
                    continue;
                }

                $section = WebsiteSection::query()->create([
                    'page_id' => $page->id,
                    'section_key' => $sectionData['key'],
                    'label' => $sectionData['label'],
                    'heading' => $sectionData['heading'] ?? null,
                    'subtitle' => $sectionData['subtitle'] ?? null,
                    'body' => $sectionData['body'] ?? null,
                    'primary_button_label' => $sectionData['primary_button_label'] ?? null,
                    'secondary_button_label' => $sectionData['secondary_button_label'] ?? null,
                    'image_path' => $sectionData['image_path'] ?? null,
                    'extra' => $sectionData['extra'] ?? null,
                    'sort_order' => $sectionIndex,
                    'is_active' => true,
                ]);

                $groupKey = WebsiteContentDefaults::sectionGroups()[$pageData['slug']][$sectionData['key']] ?? null;
                if (is_string($groupKey) && $groupKey !== '') {
                    $this->attachGroupItemsToSection($section, $groupKey);
                }
            }

            if ($pageData['slug'] === 'staffing') {
                $this->syncStaffingIllustrationSectionLabels($page);
            }
        }
    }

    private function syncStaffingIllustrationSectionLabels(WebsitePage $page): void
    {
        $definitions = [
            'hero' => [
                'label' => 'Staffing Hero Image',
                'body' => 'Hero photograph on the public Staffing page (left copy is fixed in the page component).',
            ],
            'workforce_partner' => [
                'label' => 'Workforce Partner Section',
                'body' => 'Optional accent image for the workforce partner band on the Staffing page.',
            ],
        ];

        foreach ($definitions as $sectionKey => $values) {
            $page->sections()->where('section_key', $sectionKey)->update($values);
        }
    }

    public function resetToDefaults(): void
    {
        DB::transaction(function (): void {
            PageSectionItem::query()->delete();
            ContentItem::query()->delete();
            WebsiteSection::query()->delete();
            WebsitePage::query()->delete();
            $this->seedDefaults();
            $this->ensureStaffingPageMediaDefaults();
        });
    }

    public function seedDefaults(): void
    {
        foreach (WebsiteContentDefaults::groups() as $groupKey => $items) {
            foreach ($items as $index => $item) {
                ContentItem::query()->create([
                    'group_key' => $groupKey,
                    'type' => $this->typeForGroup($groupKey),
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'icon' => $item['icon'] ?? null,
                    'image_path' => $item['image_path'] ?? null,
                    'bullets' => $item['bullets'] ?? [],
                    'sort_order' => $index,
                    'is_active' => true,
                ]);
            }
        }

        $sectionGroups = WebsiteContentDefaults::sectionGroups();

        foreach (WebsiteContentDefaults::pages() as $pageIndex => $pageData) {
            $page = WebsitePage::query()->create([
                'slug' => $pageData['slug'],
                'title' => $pageData['title'],
            ]);

            foreach ($pageData['sections'] as $sectionIndex => $sectionData) {
                $section = WebsiteSection::query()->create([
                    'page_id' => $page->id,
                    'section_key' => $sectionData['key'],
                    'label' => $sectionData['label'],
                    'heading' => $sectionData['heading'] ?? null,
                    'subtitle' => $sectionData['subtitle'] ?? null,
                    'body' => $sectionData['body'] ?? null,
                    'primary_button_label' => $sectionData['primary_button_label'] ?? null,
                    'secondary_button_label' => $sectionData['secondary_button_label'] ?? null,
                    'image_path' => $sectionData['image_path'] ?? null,
                    'extra' => $sectionData['extra'] ?? null,
                    'sort_order' => $sectionIndex,
                    'is_active' => true,
                ]);

                $groupKey = $sectionGroups[$pageData['slug']][$sectionData['key']] ?? null;
                if (! is_string($groupKey) || $groupKey === '') {
                    continue;
                }

                $groupItems = ContentItem::query()
                    ->where('group_key', $groupKey)
                    ->orderBy('sort_order')
                    ->get();

                foreach ($groupItems as $itemIndex => $item) {
                    $section->items()->attach($item->id, ['sort_order' => $itemIndex]);
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function common(): array
    {
        $this->ensureSeeded();

        $settings = app(WebsiteSettingService::class)->current();

        return [
            'brand' => $settings->websiteName(),
            'tagline' => 'Right People. Right Training. Right Results.',
            'copyright' => app(WebsiteSettingService::class)->copyrightLine(),
            'contact' => app(SiteContactService::class)->inertiaContact(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function contactDetails(): array
    {
        $this->ensureSeeded();

        $section = WebsiteSection::query()
            ->where('section_key', 'details')
            ->whereHas('page', fn ($query) => $query->where('slug', 'contact'))
            ->first();

        $extra = is_array($section?->extra) ? $section->extra : [];

        return [
            'email' => (string) ($extra['email'] ?? PublicContent::fallbackContact()['email']),
            'phone' => (string) ($extra['phone'] ?? PublicContent::fallbackContact()['phone']),
            'whatsapp' => (string) ($extra['whatsapp'] ?? ''),
            'address' => (string) ($extra['address'] ?? PublicContent::fallbackContact()['address']),
            'hours' => (string) ($extra['hours'] ?? PublicContent::fallbackContact()['hours']),
            'help' => (string) ($extra['help'] ?? ''),
        ];
    }

    /**
     * @return list<string>
     */
    public function industryTitles(): array
    {
        $this->ensureSeeded();

        return ContentItem::query()
            ->where('group_key', 'industries')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('title')
            ->all();
    }

    /**
     * @return list<array{title: string, summary: string}>
     */
    public function coreServiceSummaries(): array
    {
        $this->ensureSeeded();

        return ContentItem::query()
            ->where('group_key', 'core_services')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ContentItem $item) => [
                'title' => $item->title,
                'summary' => (string) $item->description,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function publicPage(string $slug): array
    {
        return $this->pagePayload($slug, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function adminPage(string $slug): array
    {
        return $this->pagePayload($slug, false);
    }

    /** @var list<string> */
    public const EDITORIAL_PAGE_SLUGS = [
        'home',
        'hire',
        'talent-pool',
        'staffing',
        'about',
    ];

    /**
     * @return list<array{slug: string, title: string}>
     */
    public function pageTabs(): array
    {
        $this->ensureSeeded();

        return WebsitePage::query()
            ->orderBy('id')
            ->get(['slug', 'title'])
            ->map(fn (WebsitePage $page) => [
                'slug' => $page->slug,
                'title' => $page->title,
            ])
            ->all();
    }

    /**
     * Public marketing pages editable in Admin → Website Content (primary nav destinations).
     *
     * @return list<array{slug: string, title: string}>
     */
    public function editorialPageTabs(): array
    {
        $this->ensureSeeded();

        $order = array_flip(self::EDITORIAL_PAGE_SLUGS);

        $editorial = WebsitePage::query()
            ->whereIn('slug', self::EDITORIAL_PAGE_SLUGS)
            ->get(['slug', 'title'])
            ->sortBy(fn (WebsitePage $page) => $order[$page->slug] ?? 999)
            ->values()
            ->map(fn (WebsitePage $page) => [
                'slug' => $page->slug,
                'title' => $page->title,
            ])
            ->all();

        return array_merge($editorial, $this->showcaseAdminTabs());
    }

    /**
     * @return list<array{slug: string, title: string}>
     */
    public function showcaseAdminTabs(): array
    {
        return [
            ['slug' => 'showcase-statistics', 'title' => 'Homepage Statistics'],
            ['slug' => 'showcase-home-companies', 'title' => 'Home Page Companies'],
            ['slug' => 'showcase-talent-pool-companies', 'title' => 'Talent Pool Companies'],
            ['slug' => 'showcase-staffing-companies', 'title' => 'Staffing Companies'],
        ];
    }

    public function isShowcaseAdminPage(string $slug): bool
    {
        return str_starts_with($slug, 'showcase-');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updatePage(string $slug, array $payload): void
    {
        $this->ensureSeeded();

        $page = WebsitePage::query()->where('slug', $slug)->firstOrFail();

        DB::transaction(function () use ($page, $payload, $slug): void {
            if (array_key_exists('seo_title', $payload) || array_key_exists('meta_description', $payload)) {
                $page->update([
                    'seo_title' => filled($payload['seo_title'] ?? null) ? trim((string) $payload['seo_title']) : null,
                    'meta_description' => filled($payload['meta_description'] ?? null) ? trim((string) $payload['meta_description']) : null,
                ]);
            }

            foreach ($payload['sections'] as $sectionData) {
                $section = $page->sections()
                    ->where('section_key', $sectionData['section_key'])
                    ->first();

                if (! $section) {
                    throw ValidationException::withMessages([
                        'sections' => 'Unknown section: '.$sectionData['section_key'],
                    ]);
                }

                $extra = $this->normalizeStaffingSectionExtra(
                    $slug,
                    $section->section_key,
                    is_array($sectionData['extra'] ?? null) ? $sectionData['extra'] : (array) ($section->extra ?? [])
                );

                $section->update([
                    'heading' => $sectionData['heading'] ?? null,
                    'subtitle' => $sectionData['subtitle'] ?? null,
                    'body' => $sectionData['body'] ?? null,
                    'primary_button_label' => $sectionData['primary_button_label'] ?? null,
                    'secondary_button_label' => $sectionData['secondary_button_label'] ?? null,
                    'image_path' => $this->safeImagePath($sectionData['image_path'] ?? null),
                    'extra' => $extra,
                    'is_active' => filter_var($sectionData['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ]);

                if (array_key_exists('media_id', $sectionData)) {
                    $this->assignMedia($section, $sectionData['media_id'] ?? null);
                }

                foreach ($sectionData['items'] ?? [] as $itemData) {
                    $itemId = (int) ($itemData['id'] ?? 0);
                    $item = $section->items()->where('content_items.id', $itemId)->first();

                    if (! $item) {
                        throw ValidationException::withMessages([
                            'items' => 'Unknown content item for this section.',
                        ]);
                    }

                    $item->update([
                        'title' => $itemData['title'],
                        'description' => $itemData['description'] ?? null,
                        'icon' => $itemData['icon'] ?? null,
                        'image_path' => $this->safeImagePath($itemData['image_path'] ?? null),
                        'bullets' => $this->normalizeBullets($itemData['bullets'] ?? []),
                        'is_active' => filter_var($itemData['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                    ]);
                }
            }
        });
    }

    public function publicUrlFor(string $slug): string
    {
        return match ($slug) {
            'home' => '/',
            'about' => '/about',
            'services' => '/services',
            'for-employers' => '/for-employers',
            'contact' => '/contact',
            'hire' => '/hire',
            'talent-pool' => '/talent-pool',
            'staffing' => '/staffing',
            'privacy-policy' => '/privacy-policy',
            'terms-and-conditions' => '/terms-and-conditions',
            default => '/',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function pagePayload(string $slug, bool $publicOnly): array
    {
        $this->ensureSeeded();

        $page = WebsitePage::query()
            ->where('slug', $slug)
            ->with(['sections.media', 'sections.items.media'])
            ->firstOrFail();

        $sections = $page->sections
            ->when($publicOnly, fn ($collection) => $collection->where('is_active', true))
            ->values()
            ->map(function (WebsiteSection $section) use ($publicOnly, $slug) {
                $items = $section->items
                    ->when($publicOnly, fn ($collection) => $collection->where('is_active', true))
                    ->values()
                    ->map(function (ContentItem $item) use ($publicOnly) {
                        $data = $item->toPublicArray();
                        $data['image_url'] = $this->resolveImageUrl($item->media, $item->image_path);
                        if (! $publicOnly) {
                            $data['group_key'] = $item->group_key;
                            $data['type'] = $item->type;
                            $data['media_id'] = $item->media_id;
                        }

                        return $data;
                    })
                    ->all();

                $extra = is_array($section->extra) ? $section->extra : [];
                $imageUrl = $this->resolveImageUrl($section->media, $section->image_path);

                if ($slug === 'staffing' && in_array($section->section_key, ['hero', 'workforce_partner'], true)) {
                    $extra = $this->staffingImageExtraPayload($extra, $section->section_key);

                    if ($section->section_key === 'hero' && $this->staffingImageEnabled($extra) && ! filled($imageUrl)) {
                        $imageUrl = StaffingPageDefaultMedia::defaultHeroImageUrl();
                    }

                    if ($publicOnly && ! $this->staffingImageEnabled($extra)) {
                        $imageUrl = null;
                        $extra['mobile_image_url'] = null;
                    }
                }

                $sectionPayload = [
                    'key' => $section->section_key,
                    'label' => $section->label,
                    'heading' => $section->heading,
                    'subtitle' => $section->subtitle,
                    'body' => $section->body,
                    'primary_button_label' => $section->primary_button_label,
                    'secondary_button_label' => $section->secondary_button_label,
                    'image_path' => $section->image_path,
                    'image_url' => $imageUrl,
                    'extra' => $extra,
                    'is_active' => $section->is_active,
                    'items' => $items,
                ];

                if (! $publicOnly) {
                    $sectionPayload['media_id'] = $section->media_id;
                    $sectionPayload['supports_image'] = $this->sectionSupportsImage($slug, $section->section_key);
                }

                return $sectionPayload;
            })
            ->all();

        return [
            'slug' => $page->slug,
            'title' => $page->title,
            'preview_url' => $this->publicUrlFor($page->slug),
            'seo_title' => $page->seo_title,
            'meta_description' => $page->meta_description,
            'sections' => $sections,
        ];
    }

    private function typeForGroup(string $groupKey): string
    {
        return match ($groupKey) {
            'industries' => 'industry',
            'core_services' => 'service',
            'benefits' => 'benefit',
            default => 'card',
        };
    }

    private function sectionSupportsImage(string $slug, string $sectionKey): bool
    {
        $map = [
            'home' => ['hero', 'hero_slider', 'promo_banner', 'jobs_product', 'business_solutions', 'home_cta', 'our_commitment', 'hiring_features', 'candidate_features'],
            'about' => ['hero', 'founder', 'our_commitment'],
            'services' => ['hero'],
            'for-employers' => ['hero'],
            'contact' => ['hero'],
            'hire' => ['hero', 'problem', 'solution', 'cta'],
            'talent-pool' => ['hero', 'manual_work', 'how_it_works', 'cta'],
            'staffing' => ['hero', 'workforce_partner'],
        ];

        return in_array($sectionKey, $map[$slug] ?? [], true);
    }

    private function assignMedia(WebsiteSection $section, mixed $mediaId): void
    {
        $id = is_numeric($mediaId) ? (int) $mediaId : 0;

        if ($id <= 0) {
            $section->update([
                'media_id' => null,
                'image_path' => null,
            ]);

            return;
        }

        $media = MediaAsset::query()->find($id);
        if (! $media) {
            throw ValidationException::withMessages([
                'media_id' => 'The selected image could not be found.',
            ]);
        }

        $section->update([
            'media_id' => $media->id,
            'image_path' => $media->path,
        ]);
    }

    public function contentItemImageUrl(ContentItem $item): ?string
    {
        $item->loadMissing('media');

        return $this->resolveImageUrl($item->media, $item->image_path);
    }

    private function resolveImageUrl(?MediaAsset $media, ?string $path): ?string
    {
        if ($media) {
            return $media->url();
        }

        return $this->legacyImageUrl($path);
    }

    private function ensureMissingHomepageSections(): void
    {
        $page = WebsitePage::query()->where('slug', 'home')->first();
        if ($page === null) {
            return;
        }

        $definitions = WebsiteContentDefaults::homeExtensionSections();
        $sectionGroups = WebsiteContentDefaults::sectionGroups()['home'] ?? [];

        foreach ($definitions as $sectionIndex => $sectionData) {
            if ($page->sections()->where('section_key', $sectionData['key'])->exists()) {
                continue;
            }

            $section = WebsiteSection::query()->create([
                'page_id' => $page->id,
                'section_key' => $sectionData['key'],
                'label' => $sectionData['label'],
                'heading' => $sectionData['heading'] ?? null,
                'subtitle' => $sectionData['subtitle'] ?? null,
                'body' => $sectionData['body'] ?? null,
                'primary_button_label' => $sectionData['primary_button_label'] ?? null,
                'secondary_button_label' => $sectionData['secondary_button_label'] ?? null,
                'image_path' => $sectionData['image_path'] ?? null,
                'extra' => $sectionData['extra'] ?? null,
                'sort_order' => 100 + $sectionIndex,
                'is_active' => true,
            ]);

            $groupKey = $sectionGroups[$sectionData['key']] ?? null;
            if (! is_string($groupKey) || $groupKey === '') {
                continue;
            }

            $this->attachGroupItemsToSection($section, $groupKey);
        }
    }

    private function syncEmptyHomeSectionItems(): void
    {
        $page = WebsitePage::query()->where('slug', 'home')->first();
        if ($page === null) {
            return;
        }

        $sectionGroups = WebsiteContentDefaults::sectionGroups()['home'] ?? [];

        foreach ($sectionGroups as $sectionKey => $groupKey) {
            if (! is_string($groupKey) || $groupKey === '') {
                continue;
            }

            $section = $page->sections()->where('section_key', $sectionKey)->first();
            if ($section === null || $section->items()->exists()) {
                continue;
            }

            $this->attachGroupItemsToSection($section, $groupKey);
        }
    }

    private function attachGroupItemsToSection(WebsiteSection $section, string $groupKey): void
    {
        $groupItems = ContentItem::query()
            ->where('group_key', $groupKey)
            ->orderBy('sort_order')
            ->get();

        foreach ($groupItems as $itemIndex => $item) {
            if ($section->items()->where('content_items.id', $item->id)->exists()) {
                continue;
            }

            $section->items()->attach($item->id, ['sort_order' => $itemIndex]);
        }
    }

    /**
     * @param  list<string>  $groupKeys
     */
    private function ensureContentGroups(array $groupKeys): void
    {
        $groups = WebsiteContentDefaults::groups();

        foreach ($groupKeys as $groupKey) {
            if (! isset($groups[$groupKey]) || ContentItem::query()->where('group_key', $groupKey)->exists()) {
                continue;
            }

            foreach ($groups[$groupKey] as $index => $item) {
                ContentItem::query()->create([
                    'group_key' => $groupKey,
                    'type' => $this->typeForGroup($groupKey),
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'icon' => $item['icon'] ?? null,
                    'image_path' => $item['image_path'] ?? null,
                    'bullets' => $item['bullets'] ?? [],
                    'sort_order' => $index,
                    'is_active' => true,
                ]);
            }
        }
    }

    public function ensureStaffingPageMediaDefaults(): void
    {
        $page = WebsitePage::query()->where('slug', 'staffing')->first();
        if ($page === null) {
            return;
        }

        $defaultsByKey = [
            'hero' => [
                'image_enabled' => '1',
                'image_object_position' => 'center center',
                'mobile_image_object_position' => 'center center',
                'image_alt' => 'Workforce team coordinating on site',
            ],
            'workforce_partner' => [
                'image_enabled' => '0',
                'image_object_position' => 'center center',
                'mobile_image_object_position' => 'center center',
                'image_alt' => '',
            ],
        ];

        foreach ($defaultsByKey as $sectionKey => $defaults) {
            $section = $page->sections()->where('section_key', $sectionKey)->first();
            if ($section === null) {
                continue;
            }

            $current = is_array($section->extra) ? $section->extra : [];
            $merged = $this->mergeMissingExtraDefaults($defaults, $current);
            if ($merged != $current) {
                $section->update(['extra' => $merged]);
            }
        }

        $hero = $page->sections()->where('section_key', 'hero')->first();
        if ($hero === null || $hero->media_id !== null) {
            return;
        }

        $media = StaffingPageDefaultMedia::ensureDefaultHeroMediaAsset();
        if ($media === null) {
            return;
        }

        $hero->update([
            'media_id' => $media->id,
            'image_path' => $media->path,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function staffingImageExtraPayload(array $extra, string $sectionKey): array
    {
        if (! isset($extra['image_enabled'])) {
            $extra['image_enabled'] = $sectionKey === 'hero' ? '1' : '0';
        }

        $mobileId = isset($extra['mobile_media_id']) && is_numeric($extra['mobile_media_id'])
            ? (int) $extra['mobile_media_id']
            : 0;
        $mobileMedia = $mobileId > 0 ? MediaAsset::query()->find($mobileId) : null;
        $extra['mobile_image_url'] = $this->resolveImageUrl($mobileMedia, null);

        return $extra;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function staffingImageEnabled(array $extra): bool
    {
        return ($extra['image_enabled'] ?? '1') !== '0';
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    private function mergeMissingExtraDefaults(array $defaults, array $current): array
    {
        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $current)) {
                $current[$key] = $value;
            }
        }

        return $current;
    }

    private function normalizeStaffingSectionExtra(string $slug, string $sectionKey, array $extra): array
    {
        if ($slug !== 'staffing' || ! in_array($sectionKey, ['hero', 'workforce_partner'], true)) {
            return $extra;
        }

        $extra['image_enabled'] = filter_var($extra['image_enabled'] ?? ($sectionKey === 'hero' ? '1' : '0'), FILTER_VALIDATE_BOOLEAN)
            ? '1'
            : '0';

        foreach (['image_object_position', 'mobile_image_object_position', 'image_alt'] as $key) {
            if (array_key_exists($key, $extra) && is_string($extra[$key])) {
                $extra[$key] = trim($extra[$key]);
            }
        }

        if (array_key_exists('mobile_media_id', $extra)) {
            if ($extra['mobile_media_id'] === '' || $extra['mobile_media_id'] === null) {
                unset($extra['mobile_media_id']);
            } else {
                $mobileId = (int) $extra['mobile_media_id'];
                if ($mobileId <= 0 || ! MediaAsset::query()->whereKey($mobileId)->exists()) {
                    throw ValidationException::withMessages([
                        'sections' => 'The selected mobile image could not be found.',
                    ]);
                }
                $extra['mobile_media_id'] = $mobileId;
            }
        }

        return $extra;
    }

    private function safeImagePath(mixed $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $path = trim($path);

        if (str_contains($path, '..')) {
            return null;
        }

        return $path;
    }

    private function legacyImageUrl(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return '/'.$path;
    }

    /**
     * @param  mixed  $bullets
     * @return list<string>
     */
    private function normalizeBullets(mixed $bullets): array
    {
        if (is_string($bullets)) {
            $bullets = preg_split('/\r\n|\r|\n/', $bullets) ?: [];
        }

        if (! is_array($bullets)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($bullet) => is_string($bullet) ? trim($bullet) : '',
            $bullets
        )));
    }
}
