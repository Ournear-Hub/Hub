<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Company logo uses employer_profiles.logo_path (independent from users.photo_path).
 */
class EmployerProfilePhotoTest extends TestCase
{
    public function test_employer_can_upload_replace_and_remove_company_logo_via_company_logo_routes(): void
    {
        Storage::fake('public');
        $employer = $this->makeEmployer();

        $this->actingAs($employer)
            ->post(route('account.company.logo.update'), [
                'logo' => UploadedFile::fake()->image('logo.jpg', 120, 120),
            ])
            ->assertRedirect();

        $profile = $employer->fresh()->employerProfile;
        $this->assertNotNull($profile?->logo_path);
        Storage::disk('public')->assertExists((string) $profile?->logo_path);
        $this->assertNull($employer->fresh()->photo_path);

        $this->actingAs($employer)
            ->get(route('account.company'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.logo_url', fn ($url) => is_string($url) && str_contains($url, '/employers/'.$profile->id.'/logo')));

        $originalPath = $profile->logo_path;

        $this->actingAs($employer)
            ->post(route('account.company.logo.update'), [
                'logo' => UploadedFile::fake()->image('replacement.webp', 100, 100),
            ])
            ->assertRedirect();

        $this->actingAs($employer)
            ->post(route('account.company.logo.update'), [
                'logo' => UploadedFile::fake()->create('invalid.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');

        $replaced = $employer->fresh()->employerProfile?->logo_path;
        $this->assertNotSame($originalPath, $replaced);
        Storage::disk('public')->assertExists((string) $replaced);
        Storage::disk('public')->assertMissing($originalPath);

        $this->actingAs($employer)->delete(route('account.company.logo.destroy'))->assertRedirect();
        $this->assertNull($employer->fresh()->employerProfile?->logo_path);
    }

    public function test_personal_profile_photo_does_not_update_company_logo(): void
    {
        Storage::fake('public');
        $employer = $this->makeEmployer();

        $this->actingAs($employer)
            ->post(route('account.profile.photo.update'), [
                'photo' => UploadedFile::fake()->image('personal.png', 80, 80),
            ])
            ->assertRedirect();

        $employer->refresh();
        $this->assertNotNull($employer->photo_path);
        $this->assertNull($employer->employerProfile?->logo_path);
    }

    public function test_company_logo_rejects_invalid_files_and_blocks_cross_employer_logo_access(): void
    {
        Storage::fake('public');
        $owner = $this->makeEmployer();
        $other = $this->makeEmployer();

        $this->actingAs($owner)
            ->post(route('account.company.logo.update'), [
                'logo' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');

        $this->assertNull($owner->fresh()->employerProfile?->logo_path);

        $this->actingAs($owner)
            ->post(route('account.company.logo.update'), [
                'logo' => UploadedFile::fake()->image('logo.png', 80, 80),
            ])
            ->assertRedirect();

        $profile = $owner->fresh()->employerProfile;
        $this->actingAs($owner)->get(route('employers.logo.show', $profile))->assertOk();
        $this->actingAs($other)->get(route('employers.logo.show', $profile))->assertForbidden();
    }
}
