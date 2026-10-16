<?php

namespace Tests\Feature;

use App\Enums\StaffRoleSlug;
use App\Models\InternalCandidateResume;
use App\Services\InternalCandidateResumeService;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminInternalCandidateResumeUiTest extends TestCase
{
    public function test_staff_with_candidates_view_can_open_internal_resumes_index(): void
    {
        $staff = $this->makeStaff(StaffRoleSlug::Recruiter->value);

        $this->actingAs($staff, 'admin')
            ->get(route('admin.candidates.internal-resumes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Candidates/InternalResumes/Index'));
    }

    public function test_unauthorized_staff_cannot_download_internal_resume(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $viewer = $this->makeStaff(StaffRoleSlug::Finance->value);

        $record = app(InternalCandidateResumeService::class)->store(
            $recruiter,
            $this->fakeResume('internal-ui.pdf'),
            ['display_name' => 'Internal UI', 'source' => 'email'],
        );

        $this->actingAs($viewer, 'admin')
            ->get(route('admin.candidates.internal-resumes.download', $record))
            ->assertForbidden();
    }

    public function test_authorized_staff_can_download_stored_internal_resume(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $record = app(InternalCandidateResumeService::class)->store(
            $recruiter,
            $this->fakeResume('internal-download.pdf'),
            ['display_name' => 'Download Test', 'source' => 'offline'],
        );

        $this->actingAs($recruiter, 'admin')
            ->get(route('admin.candidates.internal-resumes.download', $record))
            ->assertOk();

        $this->assertSame(1, InternalCandidateResume::query()->count());
    }
}
