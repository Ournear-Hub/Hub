<?php

namespace App\Services;

use App\Models\ContentItem;
use App\Models\MediaAsset;
use App\Models\PageSectionItem;
use App\Models\WebsitePage;
use App\Models\WebsiteSection;
use App\Support\PublicContent;
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

        $this->ensureLegalPages();
    }

    public function ensureLegalPages(): void
    {
        foreach (WebsiteContentDefaults::pages() as $pageData) {
            if (! in_array($pageData['slug'], ['privacy-policy', 'terms-and-conditions'], true)) {
                continue;
            }

            if (WebsitePage::query()->where('slug', $pageData['slug'])->exists()) {
                continue;
            }

            $page = WebsitePage::query()->create([
                'slug' => $pageData['slug'],
                'title' => $pageData['title'],
            ]);

            foreach ($pageData['sections'] as $sectionIndex => $sectionData) {
                WebsiteSection::query()->create([
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
            }
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
        $details = $this->contactDetails();

        return [
            'brand' => $settings->websiteName(),
            'tagline' => 'Right People. Right Training. Right Results.',
            'copyright' => app(WebsiteSettingService::class)->copyrightLine(),
            'contact' => [
                'email' => $settings->office_email ?: PublicContent::fallbackContact()['email'],
                'phone' => $settings->office_phone ?: PublicContent::fallbackContact()['phone'],
                'address' => $settings->office_address ?: PublicContent::fallbackContact()['address'],
                'whatsapp' => $details['whatsapp'],
                'hours' => $details['hours'],
                'help' => $details['help'],
            ],
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
     * @param  array<string, mixed>  $payload
     */
    public function updatePage(string $slug, array $payload): void
    {
        $this->ensureSeeded();

        $page = WebsitePage::query()->where('slug', $slug)->firstOrFail();

        DB::transaction(function () use ($page, $payload): void {
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

                $section->update([
                    'heading' => $sectionData['heading'] ?? null,
                    'subtitle' => $sectionData['subtitle'] ?? null,
                    'body' => $sectionData['body'] ?? null,
                    'primary_button_label' => $sectionData['primary_button_label'] ?? null,
                    'secondary_button_label' => $sectionData['secondary_button_label'] ?? null,
                    'image_path' => $this->safeImagePath($sectionData['image_path'] ?? null),
                    'extra' => $sectionData['extra'] ?? $section->extra,
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

                $sectionPayload = [
                    'key' => $section->section_key,
                    'label' => $section->label,
                    'heading' => $section->heading,
                    'subtitle' => $section->subtitle,
                    'body' => $section->body,
                    'primary_button_label' => $section->primary_button_label,
                    'secondary_button_label' => $section->secondary_button_label,
                    'image_path' => $section->image_path,
                    'image_url' => $this->resolveImageUrl($section->media, $section->image_path),
                    'extra' => $section->extra ?? [],
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
            'home' => ['hero', 'our_commitment'],
            'about' => ['hero', 'our_commitment'],
            'services' => ['hero'],
            'for-employers' => ['hero'],
            'contact' => ['hero'],
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

    private function resolveImageUrl(?MediaAsset $media, ?string $path): ?string
    {
        if ($media) {
            return $media->url();
        }

        return $this->legacyImageUrl($path);
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
