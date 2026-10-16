<?php

namespace Tests\Feature;

use App\Services\UserPhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateProfilePhotoUploadTest extends TestCase
{
    public function test_oversized_photo_is_rejected(): void
    {
        Storage::fake('public');
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->post(route('account.profile.photo.update'), [
                'photo' => UploadedFile::fake()->create('large.jpg', UserPhotoService::MAX_KILOBYTES + 1),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertNull($candidate->fresh()->photo_path);
    }

    public function test_invalid_photo_type_is_rejected(): void
    {
        Storage::fake('public');
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->post(route('account.profile.photo.update'), [
                'photo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors('photo');
    }

    public function test_guest_cannot_upload_photo(): void
    {
        $this->post(route('account.profile.photo.update'), [
            'photo' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertRedirect(route('login'));
    }
}
