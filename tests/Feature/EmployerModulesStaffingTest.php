<?php

namespace Tests\Feature;

use App\Enums\EmployerStaffingAccessStatus;
use App\Enums\JobListingScope;
use App\Enums\JobPublishingStatus;
use App\Enums\RecruiterTalentPoolAssignmentType;
use App\Enums\StaffRoleSlug;
use App\Models\EmployerEnquiry;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use App\Notifications\EmployerLifecycleNotification;
use App\Notifications\LifecycleNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployerModulesStaffingTest extends TestCase
{
    public function test_registration_does_not_auto_approve_staffing(): void
    {
        $employer = $this->makeEmployer(['email' => 'staffing-module@example.test']);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $this->assertSame(
            EmployerStaffingAccessStatus::NotRequested,
            $profile->staffing_access_status ?? EmployerStaffingAccessStatus::NotRequested,
        );
    }

    public function test_company_can_request_staffing_access_once(): void
    {
        Notification::fake();
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $employer = $this->makeEmployer(['email' => 'staffing-request@example.test']);

        $this->actingAs($employer)
            ->post(route('account.company.staffing-access.request'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $employer->employerProfile?->refresh();
        $this->assertSame(EmployerStaffingAccessStatus::PendingApproval, $employer->employerProfile?->staffing_access_status);

        Notification::assertSentTo($admin, LifecycleNotification::class);

        $this->actingAs($employer)
            ->post(route('account.company.staffing-access.request'))
            ->assertSessionHasErrors('staffing_access');
    }

    public function test_admin_can_approve_staffing_and_unapproved_cannot_submit_requirement(): void
    {
        Notification::fake();
        $employer = $this->makeEmployer(['email' => 'staffing-gate@example.test']);
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $this->actingAs($employer)
            ->post(route('account.company.staffing-access.request'))
            ->assertRedirect();

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.employers.staffing-access.approve', $profile))
            ->assertRedirect()
            ->assertSessionHas('success');

        $profile->refresh();
        $this->assertSame(EmployerStaffingAccessStatus::Approved, $profile->staffing_access_status);

        Notification::assertSentTo($employer, EmployerLifecycleNotification::class);
    }

    public function test_talent_pool_assignment_type_does_not_require_job_or_staffing(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'tp-module-recruiter@example.test']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $recruiter->id,
                'assignment_type' => RecruiterTalentPoolAssignmentType::TalentPool->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_company_without_staffing_requirement_is_not_in_requirement_selector(): void
    {
        $employer = $this->makeEmployer(['email' => 'no-req-co@example.test']);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $this->actingAs($admin, 'admin')
            ->patch(route('admin.employers.staffing-access.approve', $profile))
            ->assertRedirect();

        $options = \App\Support\StaffingRequirementAllocationQuery::optionsForAdminAllocation();
        $match = collect($options)->first(
            fn (array $row) => ($row['employer_profile_id'] ?? null) === $profile->id,
        );

        $this->assertNull($match, 'Approved companies without submitted requirements must not appear in the requirement selector.');
    }

    public function test_talent_pool_internal_job_is_not_publicly_visible_when_published(): void
    {
        $employer = $this->makeEmployer(['email' => 'tp-job-scope@example.test']);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $job = Job::factory()->create([
            'employer_id' => $profile->id,
            'publishing_status' => JobPublishingStatus::Published,
            'active' => true,
            'listing_scope' => JobListingScope::TalentPoolInternal,
        ]);

        $this->assertFalse($job->isPubliclyVisible());
    }
}
