<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\WebsiteSection;
use App\Services\WebsiteContentService;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AboutModulePart2Test extends TestCase
{
    public function test_about_page_includes_founder_section_from_cms(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('About')
                ->has('cms.sections')
                ->where('cms.slug', 'about')
                ->where('cms.sections', fn ($sections) => collect($sections)->contains('key', 'founder')));
    }

    public function test_founder_fields_and_image_reflect_on_public_about_after_cms_update(): void
    {
        $admin = $this->makeAdmin();
        $cms = app(WebsiteContentService::class);
        $cms->ensureSeeded();

        Storage::disk('public')->put('media/founder-test.jpg', 'fake-image');
        $media = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/founder-test.jpg',
            'original_name' => 'founder-test.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 11,
        ]);

        $payload = $this->websiteContentPayload($cms->adminPage('about'), function (array $section) use ($media): array {
            if ($section['section_key'] !== 'founder') {
                return $section;
            }

            $section['extra'] = array_merge($section['extra'] ?? [], [
                'founder_name' => 'CMS Founder Name',
                'founder_title' => 'CMS Founder Role',
                'founder_description' => 'CMS founder biography for Part 2 test.',
            ]);
            $section['media_id'] = $media->id;

            return $section;
        });

        $this->actingAs($admin)
            ->put(route('admin.website-content.update', 'about'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('about'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('About')
                ->where('cms.sections', function ($sections) use ($media): bool {
                    $founder = collect($sections)->firstWhere('key', 'founder');
                    if (! is_array($founder)) {
                        return false;
                    }

                    return ($founder['extra']['founder_name'] ?? '') === 'CMS Founder Name'
                        && ($founder['extra']['founder_title'] ?? '') === 'CMS Founder Role'
                        && str_contains((string) ($founder['extra']['founder_description'] ?? ''), 'Part 2 test')
                        && ($founder['image_url'] ?? '') === '/storage/media/founder-test.jpg';
                }));

        $this->assertSame($media->id, WebsiteSection::query()
            ->where('section_key', 'founder')
            ->whereHas('page', fn ($query) => $query->where('slug', 'about'))
            ->value('media_id'));
    }

    /**
     * @param  array<string, mixed>  $adminPage
     * @param  callable(array<string, mixed>): array<string, mixed>  $mutateSection
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
