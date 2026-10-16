<?php

namespace Tests\Feature;

use App\Enums\EmployerEnquiryStatus;
use App\Enums\EmployerStaffingAccessStatus;
use App\Enums\RecruiterTalentPoolAssignmentType;
use App\Enums\StaffingCandidateSubmissionStatus;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\EmployerEnquiry;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\RejectionReason;
use App\Models\StaffingCandidateSubmission;
use App\Models\TalentPoolCompanySubmission;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StaffingOperationsBatch6Test extends TestCase
{
    public function test_approved_client_can_submit_staffing_requirement(): void
    {
        $employer = $this->makeEmployer(['email' => 'staffing-req-batch6@example.test']);
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $this->actingAs($employer)->post(route('account.company.staffing-access.request'))->assertRedirect();
        $this->actingAs($admin, 'admin')
            ->patch(route('admin.employers.staffing-access.approve', $profile))
            ->assertRedirect();

        $profile->refresh();

        $this->actingAs($employer)
            ->post(route('account.enquiries.store'), $this->requirementPayloadFor($employer, $profile))
            ->assertRedirect(route('account.enquiries.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('employer_enquiries', [
            'user_id' => $employer->id,
            'company_name' => $profile->displayCompany(),
        ]);
    }

    public function test_unapproved_client_cannot_submit_staffing_requirement(): void
    {
        $employer = $this->makeEmployer(['email' => 'staffing-blocked-batch6@example.test']);

        $this->actingAs($employer)
            ->post(route('account.enquiries.store'), $this->requirementPayload())
            ->assertSessionHasErrors('staffing_access');
    }

    public function test_admin_staffing_workspace_lists_metrics(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.staffing-workspace.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/StaffingOperations/Index')
                ->has('metrics.open_requirements')
                ->has('metrics.joined_total'));
    }

    public function test_staffing_allocation_submit_and_client_decision_flow(): void
    {
        [$employer, $enquiry, $assignment, $admin] = $this->seedStaffingAssignment();
        $candidateId = $assignment->candidate_user_id;

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-staffing-client', $assignment), [
                'internal_notes' => 'Ready for client review',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $submission = StaffingCandidateSubmission::query()->first();
        $this->assertNotNull($submission);
        $this->assertSame($enquiry->id, $submission->employer_enquiry_id);
        $this->assertSame(StaffingCandidateSubmissionStatus::SubmittedToClient, $submission->status);
        $this->assertSame(0, Application::query()->where('user_id', $candidateId)->count());
        $this->assertSame(0, TalentPoolCompanySubmission::query()->count());

        $this->actingAs($employer)
            ->get(route('account.staffing-submissions.show', $submission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->missing('submission.internal_notes')
                ->where('submission.name', User::query()->find($candidateId)?->name));

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::UnderClientReview->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::ClientSelected->value,
                'client_feedback' => 'Strong fit',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $submission->refresh();
        $this->assertSame(StaffingCandidateSubmissionStatus::ClientSelected, $submission->status);
        $this->assertSame($employer->id, $submission->decided_by_user_id);

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::OfferInProgress->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::JoinedConfirmed->value,
                'joining_date' => '2026-11-01',
                'joining_confirmation_notes' => 'Reported on site',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $enquiry->refresh();
        $submission->refresh();
        $this->assertSame(StaffingCandidateSubmissionStatus::JoinedConfirmed, $submission->status);
        $this->assertSame(1, (int) $enquiry->fulfilled_joinings_count);
        $this->assertSame(EmployerEnquiryStatus::Completed, $enquiry->status);

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::JoinedConfirmed->value,
                'joining_date' => '2026-11-02',
            ])
            ->assertRedirect();

        $this->assertSame(1, (int) $enquiry->fresh()->fulfilled_joinings_count);
    }

    public function test_admin_cannot_confirm_joining_on_behalf_of_client(): void
    {
        [$employer, , $assignment, $admin] = $this->seedStaffingAssignment();
        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-staffing-client', $assignment))
            ->assertRedirect();

        $submission = StaffingCandidateSubmission::query()->firstOrFail();

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::UnderClientReview->value,
            ])
            ->assertRedirect();

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::ClientSelected->value,
            ])
            ->assertRedirect();

        $this->assertFalse($admin->can('updateClientDecision', $submission));

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Services\StaffingCandidateSubmissionService::class)->confirmJoining(
            $submission->fresh(),
            $admin,
            now(),
            'Attempted staff confirmation',
        );
    }

    public function test_other_client_cannot_view_staffing_submission(): void
    {
        [$employerA, , $assignment, $admin] = $this->seedStaffingAssignment();
        $employerB = $this->makeEmployer(['email' => 'staffing-other-co@example.test']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-staffing-client', $assignment))
            ->assertRedirect();

        $submission = StaffingCandidateSubmission::query()->firstOrFail();

        $this->actingAs($employerB)
            ->get(route('account.staffing-submissions.show', $submission))
            ->assertForbidden();
    }

    public function test_revoked_staffing_access_blocks_client_submission_view(): void
    {
        [$employer, , $assignment, $admin] = $this->seedStaffingAssignment();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-staffing-client', $assignment))
            ->assertRedirect();

        $submission = StaffingCandidateSubmission::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.employers.staffing-access.revoke', $profile))
            ->assertRedirect();

        $profile->refresh();
        $this->assertSame(EmployerStaffingAccessStatus::Revoked, $profile->staffing_access_status);

        $this->actingAs($employer)
            ->get(route('account.staffing-submissions.show', $submission))
            ->assertForbidden();
    }

    public function test_staffing_assignment_does_not_require_shortlisted_application(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $candidate = $this->makeCandidate(['email' => 'staffing-no-app@example.test']);
        [$employer, $enquiry] = $this->seedApprovedRequirement($admin);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $recruiter->id,
                'assignment_type' => RecruiterTalentPoolAssignmentType::Staffing->value,
                'employer_enquiry_id' => $enquiry->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $assignment = RecruiterTalentPoolAssignment::query()->first();
        $this->assertNotNull($assignment);
        $this->assertSame(RecruiterTalentPoolAssignmentType::Staffing, $assignment->assignment_type);
    }

    public function test_client_rejection_is_attributed_with_reason(): void
    {
        [$employer, , $assignment, $admin] = $this->seedStaffingAssignment();
        $reason = RejectionReason::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-staffing-client', $assignment))
            ->assertRedirect();

        $submission = StaffingCandidateSubmission::query()->firstOrFail();

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::UnderClientReview->value,
            ])
            ->assertRedirect();

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::ClientRejected->value,
                'rejection_reason_id' => $reason->id,
                'client_feedback' => 'Skills mismatch',
            ])
            ->assertRedirect();

        $submission->refresh();
        $this->assertSame(StaffingCandidateSubmissionStatus::ClientRejected, $submission->status);
        $this->assertSame($employer->id, $submission->decided_by_user_id);
        $this->assertSame($reason->id, $submission->rejection_reason_id);
    }

    /**
     * @return array{0: User, 1: EmployerEnquiry, 2: RecruiterTalentPoolAssignment, 3: User}
     */
    private function seedStaffingAssignment(): array
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $candidate = $this->makeCandidate(['email' => 'staffing-candidate-'.uniqid().'@example.test']);
        [$employer, $enquiry] = $this->seedApprovedRequirement($admin);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $recruiter->id,
                'assignment_type' => RecruiterTalentPoolAssignmentType::Staffing->value,
                'employer_enquiry_id' => $enquiry->id,
            ])
            ->assertRedirect();

        $assignment = RecruiterTalentPoolAssignment::query()->firstOrFail();

        return [$employer, $enquiry, $assignment, $admin];
    }

    /**
     * @return array{0: User, 1: EmployerEnquiry}
     */
    private function seedApprovedRequirement(User $admin): array
    {
        $employer = $this->makeEmployer(['email' => 'staffing-client-'.uniqid().'@example.test']);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $this->actingAs($employer)->post(route('account.company.staffing-access.request'))->assertRedirect();
        $this->actingAs($admin, 'admin')
            ->patch(route('admin.employers.staffing-access.approve', $profile))
            ->assertRedirect();

        $profile->refresh();

        $this->actingAs($employer)
            ->post(route('account.enquiries.store'), $this->requirementPayloadFor($employer, $profile, ['number_of_people' => 1]))
            ->assertRedirect(route('account.enquiries.index'))
            ->assertSessionHas('success');

        $enquiry = EmployerEnquiry::query()->where('user_id', $employer->id)->latest('id')->firstOrFail();

        return [$employer, $enquiry];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function requirementPayloadFor(User $employer, \App\Models\EmployerProfile $profile, array $overrides = []): array
    {
        return array_merge([
            'company_name' => $profile->displayCompany(),
            'contact_person' => $profile->contact_person ?: $employer->name,
            'mobile_number' => $profile->mobile_number ?: ($employer->mobile ?? '9876543210'),
            'email' => $employer->email,
            'industry' => $profile->industry ?: 'Manufacturing',
            'project_location' => 'Chennai',
            'workforce_type' => 'Contract',
            'number_of_people' => 2,
            'required_skills' => 'Operators',
            'period_from' => '2026-08-01',
            'period_to' => '2026-12-31',
            'training_required' => '0',
            'additional_requirements' => 'Night shift OK',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function requirementPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Batch6 Staffing Co',
            'contact_person' => 'Client Lead',
            'mobile_number' => '9876543210',
            'email' => 'batch6.staffing.client@example.test',
            'industry' => 'Manufacturing',
            'project_location' => 'Chennai',
            'workforce_type' => 'Contract',
            'number_of_people' => 2,
            'required_skills' => 'Operators',
            'period_from' => '2026-08-01',
            'period_to' => '2026-12-31',
            'training_required' => '0',
            'additional_requirements' => 'Night shift OK',
        ], $overrides);
    }
}
