<?php

namespace Tests\Feature;

use App\Enums\StaffRoleSlug;
use App\Models\MediaAsset;
use App\Models\PublicPageImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicPageImageTest extends TestCase
{
    public function test_admin_can_upload_and_the_public_page_uses_the_active_image(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.page-images.store'), [
                'page_slug' => 'development-programme',
                'slot_key' => 'hero',
                'title' => 'Workplace hero',
                'alt_text' => 'Colleagues working around a laptop',
                'is_active' => '1',
                'image' => UploadedFile::fake()->image('hero.jpg', 800, 600),
            ])
            ->assertRedirect(route('admin.page-images.index'));

        $saved = PublicPageImage::query()->first();
        $this->assertNotNull($saved);
        $this->assertTrue($saved->is_active);
        $this->assertNotNull($saved->media);
        Storage::disk('public')->assertExists($saved->media->path);

        $this->get(route('development-programme'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/DevelopmentProgramme')
                ->where('images.hero.alt', 'Colleagues working around a laptop')
                ->where('images.hero.url', '/storage/'.$saved->media->path));
    }

    public function test_replacing_an_image_updates_the_public_slot_and_removes_the_old_file(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post(route('admin.page-images.store'), [
            'page_slug' => 'development-programme',
            'slot_key' => 'team',
            'title' => 'Team',
            'alt_text' => 'First team photo',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('first.jpg', 640, 480),
        ])->assertRedirect();

        $original = PublicPageImage::query()->with('media')->firstOrFail();
        $oldPath = $original->media->path;

        $this->actingAs($admin)->put(route('admin.page-images.update', $original), [
            'title' => 'Team updated',
            'alt_text' => 'Replacement team photo',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('second.png', 640, 480),
        ])->assertRedirect(route('admin.page-images.index'));

        $original->refresh();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($original->media->path);
        $this->assertNotSame($oldPath, $original->media->path);

        $this->get(route('development-programme'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('images.team.alt', 'Replacement team photo'));
    }

    public function test_inactive_and_missing_files_are_not_sent_to_the_public_page(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post(route('admin.page-images.store'), [
            'page_slug' => 'development-programme',
            'slot_key' => 'hr-recruitment',
            'title' => 'HR',
            'alt_text' => 'Recruitment workspace',
            'is_active' => '0',
            'image' => UploadedFile::fake()->image('hr.webp', 400, 300),
        ])->assertRedirect();

        $missing = MediaAsset::query()->create([
            'disk' => 'public',
            'path' => 'media/missing.jpg',
            'original_name' => 'missing.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 10,
        ]);
        PublicPageImage::query()->create([
            'page_slug' => 'development-programme',
            'slot_key' => 'digital-marketing',
            'title' => 'Missing',
            'alt_text' => 'Should not render',
            'is_active' => true,
            'media_id' => $missing->id,
        ]);

        $this->get(route('development-programme'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->missing('images.hr-recruitment')
                ->missing('images.digital-marketing'));
    }

    public function test_invalid_and_oversized_files_are_rejected(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.page-images.store'), [
            'page_slug' => 'development-programme',
            'slot_key' => 'hero',
            'title' => 'Bad file',
            'alt_text' => 'Not an image',
            'is_active' => '1',
            'image' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
        ])->assertSessionHasErrors('image');

        $this->actingAs($admin)->post(route('admin.page-images.store'), [
            'page_slug' => 'development-programme',
            'slot_key' => 'hero',
            'title' => 'Too large',
            'alt_text' => 'Oversized image',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('large.jpg')->size(6000),
        ])->assertSessionHasErrors('image');

        $this->assertSame(0, PublicPageImage::query()->count());
    }

    public function test_users_without_website_permission_cannot_change_images(): void
    {
        Storage::fake('public');
        $payload = [
            'page_slug' => 'development-programme',
            'slot_key' => 'hero',
            'title' => 'Blocked',
            'alt_text' => 'Blocked',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ];

        $this->post(route('admin.page-images.store'), $payload)
            ->assertRedirect(route('admin.login'));

        $this->actingAs($this->makeCandidate())
            ->post(route('admin.page-images.store'), $payload)
            ->assertForbidden();

        $this->actingAs($this->makeStaff(StaffRoleSlug::Recruiter->value))
            ->post(route('admin.page-images.store'), $payload)
            ->assertForbidden();
    }
}
