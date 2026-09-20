<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\EmployerEnquiry;
use App\Models\Industry;
use App\Models\Job;
use App\Models\User;
use Tests\TestCase;

class PolicyFoundationTest extends TestCase
{
    public function test_admin_is_authorized_for_core_admin_resources(): void
    {
        $admin = $this->makeAdmin();
        $job = Job::factory()->create();
        $application = Application::factory()->create();
        $enquiry = EmployerEnquiry::factory()->create();
        $profile = CandidateProfile::query()->firstOrCreate(['user_id' => $this->makeCandidate()->id]);

        $this->assertTrue($admin->can('viewAny', Job::class));
        $this->assertTrue($admin->can('update', $job));
        $this->assertTrue($admin->can('viewAny', Application::class));
        $this->assertTrue($admin->can('update', $application));
        $this->assertTrue($admin->can('viewAny', EmployerEnquiry::class));
        $this->assertTrue($admin->can('update', $enquiry));
        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('update', $profile));
        $this->assertTrue($admin->can('viewAny', Industry::class));
        $this->assertTrue($admin->canManageMasterData());
    }

    public function test_candidate_cannot_manage_admin_resources_but_can_access_own_application(): void
    {
        $candidate = $this->makeCandidate();
        $own = Application::factory()->create([
            'user_id' => $candidate->id,
            'email' => $candidate->email,
        ]);
        $other = Application::factory()->create();
        $job = Job::factory()->create();

        $this->assertFalse($candidate->can('viewAny', Job::class));
        $this->assertFalse($candidate->can('update', $job));
        $this->assertFalse($candidate->can('viewAny', Application::class));
        $this->assertFalse($candidate->can('update', $own));
        $this->assertTrue($candidate->can('view', $own));
        $this->assertTrue($candidate->can('withdraw', $own));
        $this->assertFalse($candidate->can('view', $other));
        $this->assertFalse($candidate->can('withdraw', $other));
        $this->assertFalse($candidate->can('viewAny', Industry::class));
        $this->assertFalse($candidate->canManageMasterData());
    }

    public function test_employer_cannot_manage_jobs_or_applications(): void
    {
        $employer = $this->makeEmployer();
        $job = Job::factory()->create();
        $application = Application::factory()->create();
        $ownEnquiry = EmployerEnquiry::factory()->create(['user_id' => $employer->id]);
        $otherEnquiry = EmployerEnquiry::factory()->create();

        $this->assertFalse($employer->can('update', $job));
        $this->assertFalse($employer->can('update', $application));
        $this->assertFalse($employer->can('viewAny', EmployerEnquiry::class));
        $this->assertTrue($employer->can('view', $ownEnquiry));
        $this->assertFalse($employer->can('view', $otherEnquiry));
        $this->assertFalse($employer->can('update', $ownEnquiry));
    }
}
