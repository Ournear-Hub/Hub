<?php

namespace Tests\Feature;

use App\Services\WebsiteContentService;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EditorialWebsiteContentTest extends TestCase
{
    public function test_admin_website_content_tabs_are_limited_to_editorial_pages(): void
    {
        $admin = $this->makeAdmin();
        $content = app(WebsiteContentService::class);
        $expected = collect($content->editorialPageTabs())->pluck('slug')->all();

        $this->actingAs($admin)
            ->get(route('admin.website-content.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/WebsiteContent/Index')
                ->where('tabs', fn ($tabs) => collect($tabs)->pluck('slug')->all() === $expected));
    }

    public function test_home_promo_banner_cms_update_renders_on_public_home(): void
    {
        $admin = $this->makeAdmin();
        $cms = app(WebsiteContentService::class);
        $cms->ensureSeeded();

        $payload = $this->websiteContentPayload($cms->adminPage('home'), function (array $section): array {
            if ($section['section_key'] !== 'promo_banner') {
                return $section;
            }

            $section['heading'] = 'CMS Promo Heading Oct 2026';
            $section['primary_button_label'] = 'Post from CMS';
            $section['extra'] = array_merge($section['extra'] ?? [], [
                'primary_href' => '/hire',
            ]);

            return $section;
        });

        $this->actingAs($admin)
            ->put(route('admin.website-content.update', 'home'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('promoBanner.heading', 'CMS Promo Heading Oct 2026')
                ->where('promoBanner.primary_label', 'Post from CMS')
                ->where('promoBanner.primary_href', '/hire'));
    }

    /**
     * @param  array<string, mixed>  $adminPage
     * @param  callable(array<string, mixed>): array<string, mixed>|null  $mutateSection
     * @return array<string, mixed>
     */
    private function websiteContentPayload(array $adminPage, ?callable $mutateSection = null): array
    {
        $sections = collect($adminPage['sections'] ?? [])
            ->map(function (array $section) use ($mutateSection): array {
                $row = [
                    'section_key' => $section['key'],
                    'heading' => $section['heading'] ?? '',
                    'subtitle' => $section['subtitle'] ?? '',
                    'body' => $section['body'] ?? '',
                    'primary_button_label' => $section['primary_button_label'] ?? '',
                    'secondary_button_label' => $section['secondary_button_label'] ?? '',
                    'image_path' => $section['image_path'] ?? '',
                    'media_id' => $section['media_id'] ?? null,
                    'is_active' => $section['is_active'] ?? true,
                    'extra' => $section['extra'] ?? [],
                    'items' => collect($section['items'] ?? [])->map(fn (array $item) => [
                        'id' => $item['id'],
                        'title' => $item['title'],
                        'description' => $item['description'] ?? '',
                        'icon' => $item['icon'] ?? '',
                        'image_path' => $item['image_path'] ?? '',
                        'is_active' => $item['is_active'] ?? true,
                        'bullets' => implode("\n", $item['bullets'] ?? []),
                    ])->all(),
                ];

                return $mutateSection ? $mutateSection($row) : $row;
            })
            ->all();

        return [
            'seo_title' => $adminPage['seo_title'] ?? '',
            'meta_description' => $adminPage['meta_description'] ?? '',
            'sections' => $sections,
        ];
    }
}
