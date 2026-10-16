<?php

namespace Tests\Feature;

use App\Models\ResumeImportBatch;
use App\Models\User;
use App\Support\ReadablePdfFixture;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBulkResumeImportTest extends TestCase
{
    public function test_unauthorized_staff_cannot_upload_or_confirm(): void
    {
        $viewer = $this->makeStaff('viewer');

        $this->actingAs($viewer)
            ->post(route('admin.candidates.imports.store'), [
                'files' => [UploadedFile::fake()->create('resume.pdf', 10, 'application/pdf')],
            ])
            ->assertForbidden();

        $batch = ResumeImportBatch::query()->create([
            'uploaded_by' => $this->makeAdmin()->id,
            'status' => 'awaiting_confirmation',
        ]);

        $this->actingAs($viewer)
            ->post(route('admin.candidates.imports.confirm', $batch))
            ->assertForbidden();
    }

    public function test_stage_extracts_email_and_confirm_creates_candidate(): void
    {
        Mail::fake();
        Storage::fake('local');

        $admin = $this->makeAdmin();
        $pdf = UploadedFile::fake()->createWithContent(
            'candidate.pdf',
            ReadablePdfFixture::onePageWithContact('bulk.import@example.com', 'Bulk Import'),
        );

        $this->actingAs($admin)
            ->post(route('admin.candidates.imports.store'), ['files' => [$pdf]])
            ->assertRedirect(route('admin.candidates.imports.index'));

        $batch = ResumeImportBatch::query()->latest('id')->first();
        $this->assertNotNull($batch);
        $this->assertSame('awaiting_confirmation', $batch->status);
        $item = $batch->items()->first();
        $this->assertNotNull($item);
        $this->assertSame('ready', $item->status);
        $this->assertSame('bulk.import@example.com', $item->extracted_email);

        $this->actingAs($admin)
            ->post(route('admin.candidates.imports.confirm', $batch))
            ->assertRedirect(route('admin.candidates.imports.index'));

        $user = User::query()->where('email', 'bulk.import@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('created', $item->fresh()->status);
        $this->assertSame('completed', $batch->fresh()->status);
    }

    public function test_existing_email_is_matched_without_overwriting_profile(): void
    {
        Storage::fake('local');
        $existing = $this->makeCandidate(['email' => 'existing@example.com', 'name' => 'Existing Name']);
        $admin = $this->makeAdmin();
        $pdf = UploadedFile::fake()->createWithContent(
            'candidate.pdf',
            ReadablePdfFixture::onePageWithContact('existing@example.com', 'Different Parsed Name'),
        );

        $this->actingAs($admin)->post(route('admin.candidates.imports.store'), ['files' => [$pdf]]);
        $batch = ResumeImportBatch::query()->latest('id')->firstOrFail();
        $this->assertSame('matched', $batch->items()->first()?->status);

        $this->actingAs($admin)->post(route('admin.candidates.imports.confirm', $batch));

        $this->assertSame('Existing Name', $existing->fresh()->name);
    }

    public function test_unsupported_file_is_reported_as_failed(): void
    {
        Storage::fake('local');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.candidates.imports.store'), [
            'files' => [UploadedFile::fake()->create('resume.exe', 10)],
        ]);

        $batch = ResumeImportBatch::query()->latest('id')->firstOrFail();
        $this->assertSame('failed', $batch->items()->first()?->status);
    }
}
