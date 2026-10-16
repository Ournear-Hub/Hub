<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\User;
use App\Models\WebsiteSection;
use App\Services\WebsiteContentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StaffingIllustrationsCmsTest extends TestCase
{
    public function test_staffing_admin_sections_support_independent_illustration_images(): void
    {
        $admin = $this->makeAdmin();
        $cms = app(WebsiteContentService::class);
        $cms->ensureSeeded();

        $adminPage = $cms->adminPage('staffing');
        $hero = collect($adminPage['sections'])->firstWhere('key', 'hero');
        $support = collect($adminPage['sections'])->firstWhere('key', 'workforce_partner');

        $this->assertTrue($hero['supports_image'] ?? false);
        $this->assertTrue($support['supports_image'] ?? false);

        Storage::disk('public')->put('media/staffing-hero.jpg', 'hero-image');
        Storage::disk('public')->put('media/staffing-support.jpg', 'support-image');

        $heroMedia = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/staffing-hero.jpg',
            'original_name' => 'staffing-hero.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 10,
        ]);
        $supportMedia = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/staffing-support.jpg',
            'original_name' => 'staffing-support.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 12,
        ]);

        $payload = $this->websiteContentPayload($adminPage, function (array $section) use ($heroMedia, $supportMedia): array {
            if ($section['section_key'] === 'hero') {
                $section['media_id'] = $heroMedia->id;
                $section['extra'] = array_merge($section['extra'] ?? [], ['image_alt' => 'Staffing hero alt']);
            }
            if ($section['section_key'] === 'workforce_partner') {
                $section['media_id'] = $supportMedia->id;
                $section['extra'] = array_merge($section['extra'] ?? [], [
                    'image_enabled' => '1',
                    'image_alt' => 'Staffing support alt',
                ]);
            }

            return $section;
        });

        $this->actingAs($admin)
            ->put(route('admin.website-content.update', 'staffing'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Staffing')
                ->where('cms.sections', function ($sections): bool {
                    $hero = collect($sections)->firstWhere('key', 'hero');
                    $support = collect($sections)->firstWhere('key', 'workforce_partner');

                    return is_array($hero)
                        && is_array($support)
                        && ($hero['image_url'] ?? '') === '/storage/media/staffing-hero.jpg'
                        && ($support['image_url'] ?? '') === '/storage/media/staffing-support.jpg'
                        && ($hero['extra']['image_alt'] ?? '') === 'Staffing hero alt'
                        && ($support['extra']['image_alt'] ?? '') === 'Staffing support alt';
                }));

        $heroSection = WebsiteSection::query()
            ->where('section_key', 'hero')
            ->whereHas('page', fn ($query) => $query->where('slug', 'staffing'))
            ->first();
        $supportSection = WebsiteSection::query()
            ->where('section_key', 'workforce_partner')
            ->whereHas('page', fn ($query) => $query->where('slug', 'staffing'))
            ->first();

        $this->assertSame($heroMedia->id, $heroSection?->media_id);
        $this->assertSame($supportMedia->id, $supportSection?->media_id);
    }

    public function test_removing_one_staffing_illustration_leaves_the_other_intact(): void
    {
        $admin = $this->makeAdmin();
        $cms = app(WebsiteContentService::class);
        $cms->ensureSeeded();

        Storage::disk('public')->put('media/staffing-hero-only.jpg', 'hero');
        Storage::disk('public')->put('media/staffing-support-only.jpg', 'support');

        $heroMedia = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/staffing-hero-only.jpg',
            'original_name' => 'staffing-hero-only.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 4,
        ]);
        $supportMedia = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/staffing-support-only.jpg',
            'original_name' => 'staffing-support-only.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 6,
        ]);

        $adminPage = $cms->adminPage('staffing');
        $assignBoth = $this->websiteContentPayload($adminPage, function (array $section) use ($heroMedia, $supportMedia): array {
            if ($section['section_key'] === 'hero') {
                $section['media_id'] = $heroMedia->id;
            }
            if ($section['section_key'] === 'workforce_partner') {
                $section['media_id'] = $supportMedia->id;
                $section['extra'] = array_merge($section['extra'] ?? [], ['image_enabled' => '1']);
            }

            return $section;
        });

        $this->actingAs($admin)->put(route('admin.website-content.update', 'staffing'), $assignBoth);

        $removeHero = $this->websiteContentPayload($cms->adminPage('staffing'), function (array $section): array {
            if ($section['section_key'] === 'hero') {
                $section['media_id'] = null;
                $section['extra'] = array_merge($section['extra'] ?? [], ['image_enabled' => '0']);
            }

            return $section;
        });

        $this->actingAs($admin)->put(route('admin.website-content.update', 'staffing'), $removeHero);

        $this->get(route('staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('cms.sections', function ($sections): bool {
                    $hero = collect($sections)->firstWhere('key', 'hero');
                    $support = collect($sections)->firstWhere('key', 'workforce_partner');

                    return ($hero['image_url'] ?? null) === null
                        && ($support['image_url'] ?? '') === '/storage/media/staffing-support-only.jpg';
                }));
    }

    public function test_guest_cannot_update_staffing_illustrations(): void
    {
        $cms = app(WebsiteContentService::class);
        $cms->ensureSeeded();
        $payload = $this->websiteContentPayload($cms->adminPage('staffing'));

        $this->put(route('admin.website-content.update', 'staffing'), $payload)->assertRedirect(route('admin.login'));
    }

    public function test_candidate_cannot_update_staffing_illustrations(): void
    {
        $candidate = $this->makeCandidate();
        $cms = app(WebsiteContentService::class);
        $cms->ensureSeeded();
        $payload = $this->websiteContentPayload($cms->adminPage('staffing'));

        $this->actingAs($candidate)
            ->put(route('admin.website-content.update', 'staffing'), $payload)
            ->assertForbidden();
    }

    public function test_invalid_media_upload_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.media-social.media.store'), [
                'image' => UploadedFile::fake()->create('staffing.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_staffing_hero_falls_back_to_default_media_when_enabled_without_assignment(): void
    {
        $cms = app(WebsiteContentService::class);
        $cms->ensureSeeded();

        $this->get(route('staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Staffing')
                ->where('cms.sections', function ($sections): bool {
                    $hero = collect($sections)->firstWhere('key', 'hero');

                    return is_array($hero)
                        && ($hero['extra']['image_enabled'] ?? '1') === '1'
                        && filled($hero['image_url'] ?? null);
                }));
    }

    public function test_staffing_page_renders_without_configured_images(): void
    {
        $cms = app(WebsiteContentService::class);
        $cms->ensureSeeded();

        $admin = $this->makeAdmin();
        $payload = $this->websiteContentPayload($cms->adminPage('staffing'), function (array $section): array {
            if (in_array($section['section_key'], ['hero', 'workforce_partner'], true)) {
                $section['media_id'] = null;
                $section['extra'] = array_merge($section['extra'] ?? [], ['image_enabled' => '0']);
            }

            return $section;
        });
        $this->actingAs($admin)->put(route('admin.website-content.update', 'staffing'), $payload);

        $this->get(route('staffing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Staffing')
                ->has('cms.sections'));
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
