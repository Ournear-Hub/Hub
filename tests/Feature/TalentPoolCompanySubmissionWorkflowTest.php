<?php

namespace Tests\Feature;

use App\Enums\CompanyRelationshipRecruiterAssignmentStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\RecruiterTalentPoolAssignmentType;
use App\Enums\StaffRoleSlug;
use App\Enums\TalentPoolSubmissionOwnerResolution;
use App\Models\Application;
use App\Models\CompanyRelationshipRecruiterAssignment;
use App\Models\EmployerProfile;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\TalentPoolCompanySubmission;
use App\Models\TalentPoolSubmissionStatusEvent;
use App\Models\User;
use App\Services\CompanyRelationshipRecruiterAssignmentService;
use App\Services\RecruiterTalentPoolAssignmentService;
use App\Services\TalentPoolCompanySubmissionService;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TalentPoolCompanySubmissionWorkflowTest extends TestCase
{
    public function test_internal_assignment_does_not_create_submission_or_application(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $recruiter->id,
                'assignment_type' => RecruiterTalentPoolAssignmentType::TalentPool->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, TalentPoolCompanySubmission::query()->count());
        $this->assertSame(0, Application::query()->where('user_id', $candidate->id)->count());

        $this->actingAs($recruiter, 'admin')
            ->get(route('admin.talent-pool-assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('recruiter_workspace_scoped', true)
                ->has('assignments.data', 1));
    }

    public function test_assigned_recruiter_submits_candidate_to_company(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'tp-submit-rec@example.test']);
        $employer = $this->makeEmployer(['email' => 'tp-submit-co@example.test']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $recruiter->id,
                'assignment_type' => 'talent_pool',
            ]);

        $assignment = RecruiterTalentPoolAssignment::query()->firstOrFail();

        $this->actingAs($recruiter, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-company', $assignment), [
                'employer_profile_id' => $employer->employerProfile->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $submission = TalentPoolCompanySubmission::query()->first();
        $this->assertNotNull($submission);
        $this->assertSame($candidate->id, $submission->candidate_user_id);
        $this->assertSame($employer->employerProfile->id, $submission->employer_profile_id);
        $this->assertSame($recruiter->id, $submission->responsible_recruiter_user_id);
        $this->assertSame(EmployerSubmissionStatus::Submitted, $submission->status);

        $this->actingAs($employer)
            ->get(route('account.coordinated-talent-pool.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('submissions.data', 1));
    }

    public function test_admin_direct_submit_records_company_owner_when_no_candidate_assignment(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $companyRecruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'tp-co-owner@example.test']);
        $employer = User::factory()->employer()->create();
        $profile = $employer->employerProfile;
        $this->assertInstanceOf(EmployerProfile::class, $profile);

        app(CompanyRelationshipRecruiterAssignmentService::class)->assign($profile, $companyRecruiter, $admin);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-company-submissions.store'), [
                'candidate_user_id' => $candidate->id,
                'employer_profile_id' => $profile->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $submission = TalentPoolCompanySubmission::query()->firstOrFail();
        $this->assertSame($companyRecruiter->id, $submission->responsible_recruiter_user_id);
        $this->assertSame(TalentPoolSubmissionOwnerResolution::CompanyAssignment, $submission->owner_resolution);
    }

    public function test_conflict_uses_candidate_owner_and_preserves_company_owner_ids(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $candidateRecruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'tp-cand-owner@example.test']);
        $companyRecruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'tp-comp-owner@example.test']);
        $employer = User::factory()->employer()->create();
        $profile = $employer->employerProfile;
        $this->assertInstanceOf(EmployerProfile::class, $profile);

        app(CompanyRelationshipRecruiterAssignmentService::class)->assign($profile, $companyRecruiter, $admin);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $candidateRecruiter->id,
                'assignment_type' => 'talent_pool',
            ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-company-submissions.store'), [
                'candidate_user_id' => $candidate->id,
                'employer_profile_id' => $profile->id,
            ])
            ->assertSessionHas('success');

        $submission = TalentPoolCompanySubmission::query()->firstOrFail();
        $this->assertSame($candidateRecruiter->id, $submission->responsible_recruiter_user_id);
        $this->assertSame($companyRecruiter->id, $submission->company_owner_recruiter_user_id);
        $this->assertSame(TalentPoolSubmissionOwnerResolution::CandidateOverCompanyConflict, $submission->owner_resolution);

        $this->assertDatabaseHas('company_relationship_recruiter_assignments', [
            'employer_profile_id' => $profile->id,
            'assigned_recruiter_user_id' => $companyRecruiter->id,
            'status' => CompanyRelationshipRecruiterAssignmentStatus::Active->value,
        ]);
    }

    public function test_employer_can_review_submission_and_history_attributes_actor(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;

        app(TalentPoolCompanySubmissionService::class)->submit(
            $candidate,
            $profile,
            $admin,
        );

        $submission = TalentPoolCompanySubmission::query()->firstOrFail();

        $this->actingAs($employer)
            ->patch(route('account.coordinated-talent-pool.status.update', $submission), [
                'action' => 'review',
                'notes' => 'Reviewing profile',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $submission->refresh();
        $this->assertSame(EmployerSubmissionStatus::UnderReview, $submission->status);
        $this->assertSame($employer->id, $submission->decided_by_user_id);

        $event = TalentPoolSubmissionStatusEvent::query()->where('talent_pool_company_submission_id', $submission->id)->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertSame($employer->id, $event->actor_id);
    }

    public function test_other_employer_cannot_view_or_update_submission(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $employerA = $this->makeEmployer(['email' => 'tp-emp-a@example.test']);
        $employerB = $this->makeEmployer(['email' => 'tp-emp-b@example.test']);

        app(TalentPoolCompanySubmissionService::class)->submit(
            $candidate,
            $employerA->employerProfile,
            $admin,
        );

        $submission = TalentPoolCompanySubmission::query()->firstOrFail();

        $this->actingAs($employerB)
            ->get(route('account.coordinated-talent-pool.show', $submission))
            ->assertForbidden();

        $this->actingAs($employerB)
            ->patch(route('account.coordinated-talent-pool.status.update', $submission), [
                'action' => 'review',
            ])
            ->assertForbidden();

        $this->actingAs($employerB)
            ->get(route('account.coordinated-talent-pool.resume', $submission))
            ->assertForbidden();
    }

    public function test_duplicate_active_submission_is_rejected(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();
        $service = app(TalentPoolCompanySubmissionService::class);

        $service->submit($candidate, $employer->employerProfile, $admin);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-company-submissions.store'), [
                'candidate_user_id' => $candidate->id,
                'employer_profile_id' => $employer->employerProfile->id,
            ])
            ->assertSessionHasErrors('submission');
    }

    public function test_submission_does_not_create_job_application(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();
        $before = Application::query()->count();

        app(TalentPoolCompanySubmissionService::class)->submit(
            $candidate,
            $employer->employerProfile,
            $admin,
        );

        $this->assertSame($before, Application::query()->count());
    }

    public function test_scoped_recruiter_sees_assigned_candidate_in_admin_list(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate(['name' => 'Scoped Visibility Candidate']);
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $other = $this->makeCandidate(['email' => 'other-vis@example.test', 'name' => 'Other Person']);

        app(RecruiterTalentPoolAssignmentService::class)->assign(
            $admin,
            $candidate,
            $recruiter,
            RecruiterTalentPoolAssignmentType::TalentPool,
        );

        $this->actingAs($recruiter, 'admin')
            ->get(route('admin.talent-pool-assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('candidates.data', fn ($rows) => collect($rows)->contains(
                    fn (array $row) => $row['user_id'] === $candidate->id,
                ))
                ->where('candidates.data', fn ($rows) => ! collect($rows)->contains(
                    fn (array $row) => $row['user_id'] === $other->id,
                )));
    }
}
