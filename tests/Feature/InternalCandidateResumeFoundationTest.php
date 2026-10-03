<?php

namespace Tests\Feature;

use App\Enums\InternalCandidateResumeSource;
use App\Enums\StaffRoleSlug;
use App\Models\InternalCandidateResume;
use App\Models\User;
use App\Services\CandidatePrivateFileService;
use App\Services\InternalCandidateResumeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InternalCandidateResumeFoundationTest extends TestCase
{
    public function test_internal_resume_can_exist_with_null_user_id(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $service = app(InternalCandidateResumeService::class);

        $record = $service->store($recruiter, $this->fakeResume('offline-candidate.pdf'), [
            'display_name' => 'Offline Applicant',
            'source' => InternalCandidateResumeSource::Offline,
            'notes' => 'Received at job fair booth.',
        ]);

        $this->assertNull($record->user_id);
        $this->assertSame('Offline Applicant', $record->display_name);
        $this->assertSame(InternalCandidateResumeSource::Offline, $record->source);
    }

    public function test_creating_internal_resume_does_not_create_a_user(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $before = User::query()->count();
        $service = app(InternalCandidateResumeService::class);

        $service->store($recruiter, $this->fakeResume('whatsapp-cv.pdf'), [
            'display_name' => 'WhatsApp Lead',
            'source' => InternalCandidateResumeSource::Whatsapp,
        ]);

        $this->assertSame($before, User::query()->count());
    }

    public function test_internal_resume_file_is_stored_on_private_disk(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $service = app(InternalCandidateResumeService::class);

        $record = $service->store($recruiter, $this->fakeResume('referral.pdf'), [
            'display_name' => 'Referral CV',
            'source' => InternalCandidateResumeSource::Referral,
        ]);

        Storage::disk(CandidatePrivateFileService::DISK)->assertExists($record->storage_path);
        Storage::disk('public')->assertMissing($record->storage_path);
    }

    public function test_unauthorized_users_cannot_store_or_download_internal_resumes(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $candidate = $this->makeCandidate();
        $service = app(InternalCandidateResumeService::class);

        $record = $service->store($recruiter, $this->fakeResume('protected.pdf'), [
            'display_name' => 'Protected Record',
            'source' => InternalCandidateResumeSource::Email,
        ]);

        $this->expectException(AuthorizationException::class);
        $service->download($candidate, $record);
    }

    public function test_authorized_recruitment_staff_can_store_and_download(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $service = app(InternalCandidateResumeService::class);

        $record = $service->store($recruiter, $this->fakeResume('staff-copy.pdf'), [
            'display_name' => 'Staff Copy',
            'source' => InternalCandidateResumeSource::Other,
        ]);

        $response = $service->download($recruiter, $record);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_replacing_file_does_not_create_a_user(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $before = User::query()->count();
        $service = app(InternalCandidateResumeService::class);

        $record = $service->store($recruiter, $this->fakeResume('v1.pdf'), [
            'display_name' => 'Replace Me',
            'source' => InternalCandidateResumeSource::Offline,
        ]);

        $previousPath = $record->storage_path;
        $replaced = $service->replace($recruiter, $record, $this->fakeResume('v2.pdf'));

        $this->assertSame($before, User::query()->count());
        $this->assertNotSame($previousPath, $replaced->storage_path);
        Storage::disk(CandidatePrivateFileService::DISK)->assertExists($replaced->storage_path);
        Storage::disk(CandidatePrivateFileService::DISK)->assertMissing($previousPath);
    }

    public function test_user_link_is_optional_and_explicit_only(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $existing = $this->makeCandidate(['email' => 'linked.candidate@example.com']);
        $before = User::query()->count();
        $service = app(InternalCandidateResumeService::class);

        $record = $service->store($recruiter, $this->fakeResume('link-later.pdf'), [
            'display_name' => 'Later Link',
            'source' => InternalCandidateResumeSource::Email,
        ]);
        $this->assertNull($record->user_id);

        $linked = $service->linkUser($recruiter, $record, $existing);
        $this->assertSame($existing->id, $linked->user_id);
        $this->assertSame($before, User::query()->count());

        $unlinked = $service->linkUser($recruiter, $linked, null);
        $this->assertNull($unlinked->user_id);
        $this->assertSame(1, InternalCandidateResume::query()->count());
    }
}
