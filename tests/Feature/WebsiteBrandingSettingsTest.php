<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\WebsiteSetting;
use App\Services\BrandingAssetService;
use App\Services\WebsiteSettingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WebsiteBrandingSettingsTest extends TestCase
{
    public function test_admin_can_upload_logo_and_wordmark_and_see_public_branding(): void
    {
        if (! Schema::hasColumn('website_settings', 'wordmark_path')) {
            $this->markTestSkipped('wordmark_path column is required for companion branding.');
        }

        $admin = $this->makeAdmin();
        $logo = UploadedFile::fake()->image('logo-mark.png', 64, 64);
        $wordmark = UploadedFile::fake()->image('companion-wordmark.png', 320, 80);

        $this->actingAs($admin)
            ->put(route('admin.settings.branding'), [
                'website_name' => 'OurNear Hire',
                'logo' => $logo,
                'wordmark' => $wordmark,
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'branding']));

        $row = WebsiteSetting::query()->where('lock_key', 'default')->firstOrFail();
        $this->assertStringStartsWith('branding/', (string) $row->logo_path);
        $this->assertStringStartsWith('branding/', (string) $row->wordmark_path);

        $branding = app(WebsiteSettingService::class)->publicBranding();
        $this->assertNotNull($branding['logo_url']);
        $this->assertNotNull($branding['wordmark_url']);
        $this->assertStringStartsWith('/storage/branding/', $branding['logo_url']);
        $this->assertStringStartsWith('/storage/branding/', $branding['wordmark_url']);
        $this->assertSame($branding['logo_url'], $branding['header_logo_url']);
        $this->assertSame($branding['wordmark_url'], $branding['footer_logo_url']);

        Storage::disk(BrandingAssetService::DISK)->assertExists($row->logo_path);
        Storage::disk(BrandingAssetService::DISK)->assertExists($row->wordmark_path);

        $this->actingAs($admin)
            ->get(route('admin.settings.edit', ['tab' => 'branding']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings')
                ->where('tab', 'branding')
                ->where('branding.website_name', 'OurNear Hire')
                ->where('branding.logo_url', $branding['logo_url'])
                ->where('branding.wordmark_url', $branding['wordmark_url']));

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('branding.logo_url', $branding['logo_url'])
                ->where('branding.wordmark_url', $branding['wordmark_url']));
    }

    public function test_staff_without_website_permission_cannot_update_branding(): void
    {
        $recruiter = $this->makeStaff('recruiter');

        $this->actingAs($recruiter)
            ->put(route('admin.settings.branding'), [
                'website_name' => 'Blocked',
                'logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertForbidden();
    }

    public function test_invalid_branding_upload_is_rejected_without_persisting_paths(): void
    {
        $admin = $this->makeAdmin();
        $before = app(WebsiteSettingService::class)->current();

        $this->actingAs($admin)
            ->put(route('admin.settings.branding'), [
                'website_name' => 'OurNear',
                'logo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('logo');

        $after = $before->fresh();
        $this->assertSame($before->logo_path, $after->logo_path);
    }

    public function test_admin_can_replace_logo_and_remove_wordmark(): void
    {
        if (! Schema::hasColumn('website_settings', 'wordmark_path')) {
            $this->markTestSkipped('wordmark_path column is required for companion branding.');
        }

        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('admin.settings.branding'), [
                'website_name' => 'OurNear',
                'logo' => UploadedFile::fake()->image('first.png', 48, 48),
                'wordmark' => UploadedFile::fake()->image('word.png', 200, 60),
            ])
            ->assertRedirect();

        $row = WebsiteSetting::query()->where('lock_key', 'default')->firstOrFail();
        $firstLogo = $row->logo_path;
        $firstWordmark = $row->wordmark_path;

        $this->actingAs($admin)
            ->put(route('admin.settings.branding'), [
                'website_name' => 'OurNear',
                'logo' => UploadedFile::fake()->image('second.png', 48, 48),
                'remove_wordmark' => true,
            ])
            ->assertRedirect();

        $row->refresh();
        $this->assertNotSame($firstLogo, $row->logo_path);
        $this->assertNull($row->wordmark_path);
        Storage::disk(BrandingAssetService::DISK)->assertMissing($firstWordmark);
    }

    public function test_admin_can_assign_header_logo_from_media_library(): void
    {
        Storage::disk('public')->put('media/library-logo.png', UploadedFile::fake()->image('library-logo.png', 80, 40)->getContent());

        $media = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/library-logo.png',
            'original_name' => 'library-logo.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 100,
        ]);

        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('admin.settings.branding'), [
                'website_name' => 'OurNear Hire',
                'logo_media_id' => $media->id,
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'branding']));

        $row = WebsiteSetting::query()->where('lock_key', 'default')->firstOrFail();
        $this->assertStringStartsWith('branding/', (string) $row->logo_path);
        Storage::disk(BrandingAssetService::DISK)->assertExists($row->logo_path);
        Storage::disk('public')->assertExists('media/library-logo.png');

        $branding = app(WebsiteSettingService::class)->publicBranding();
        $this->assertNotNull($branding['header_logo_url']);
    }

    public function test_removing_logo_clears_public_header_url(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('admin.settings.branding'), [
                'website_name' => 'OurNear',
                'logo' => UploadedFile::fake()->image('logo.png', 48, 48),
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.settings.branding'), [
                'website_name' => 'OurNear',
                'remove_logo' => true,
            ])
            ->assertRedirect();

        $branding = app(WebsiteSettingService::class)->publicBranding();
        $this->assertNull($branding['logo_url']);
        $this->assertNull($branding['header_logo_url']);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('branding.header_logo_url', null)
                ->where('branding.footer_logo_url', null));
    }

    public function test_branding_tab_includes_media_library_options_for_images(): void
    {
        Storage::disk('public')->put('media/picker.png', 'png-bytes');

        $media = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/picker.png',
            'original_name' => 'picker.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size_bytes' => 9,
        ]);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.settings.edit', ['tab' => 'branding']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('brandingMediaOptions')
                ->where('brandingMediaOptions', fn ($options) => collect($options)->contains(
                    fn ($row) => ($row['id'] ?? null) === $media->id
                        && ($row['original_name'] ?? null) === 'picker.png',
                )));
    }
}
