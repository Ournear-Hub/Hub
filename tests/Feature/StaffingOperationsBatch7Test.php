<?php

namespace Tests\Feature;

use App\Enums\EmployerNotificationEvent;
use App\Enums\RecruitmentActivityType;
use App\Enums\RecruitmentOperationalVisibility;
use App\Enums\RecruiterTalentPoolAssignmentType;
use App\Enums\StaffRoleSlug;
use App\Enums\StaffingCandidateSubmissionStatus;
use App\Models\EmployerEnquiry;
use App\Models\RecruitmentActivity;
use App\Models\StaffingCandidateSubmission;
use App\Models\User;
use App\Notifications\EmployerLifecycleNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use App\Models\RecruiterTalentPoolAssignment;
use Tests\TestCase;

class StaffingOperationsBatch7Test extends TestCase
{
    public function test_internal_staff_records_staffing_follow_up_visible_on_admin_detail(): void
    {
        [$employer, , $assignment, $admin] = $this->seedStaffingPipeline();
        $recruiter = User::query()->find($assignment->assigned_recruiter_user_id);
        $this->assertNotNull($recruiter);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-staffing-client', $assignment))
            ->assertRedirect();

        $submission = StaffingCandidateSubmission::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.staffing-candidate-submissions.follow-ups.store', $submission), [
                'follow_up_type' => 'phone_call',
                'summary' => 'Called client about interview slot',
                'visibility' => RecruitmentOperationalVisibility::Internal->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('recruitment_activities', [
            'staffing_candidate_submission_id' => $submission->id,
            'type' => RecruitmentActivityType::StaffingFollowUpRecorded->value,
            'visibility' => RecruitmentOperationalVisibility::Internal->value,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.staffing-candidate-submissions.show', $submission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('follow_ups', 1)
                ->where('follow_ups.0.body', 'Called client about interview slot'));
    }

    public function test_client_sees_shared_follow_up_but_not_internal(): void
    {
        [$employer, , $assignment, $admin] = $this->seedStaffingPipeline();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-staffing-client', $assignment))
            ->assertRedirect();

        $submission = StaffingCandidateSubmission::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.staffing-candidate-submissions.follow-ups.store', $submission), [
                'follow_up_type' => 'phone_call',
                'summary' => 'Internal recruiter only note',
                'visibility' => RecruitmentOperationalVisibility::Internal->value,
            ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.staffing-candidate-submissions.follow-ups.store', $submission), [
                'follow_up_type' => 'message',
                'summary' => 'Shared update for client',
                'visibility' => RecruitmentOperationalVisibility::EmployerShared->value,
            ]);

        $this->actingAs($employer)
            ->get(route('account.staffing-submissions.show', $submission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('follow_ups', 1)
                ->where('follow_ups.0.body', 'Shared update for client')
                ->missing('submission.internal_notes'));

        $this->actingAs($employer)
            ->get(route('account.staffing-submissions.show', $submission))
            ->assertInertia(fn (Assert $page) => $page
                ->where('follow_ups.0.body', fn ($body) => $body !== 'Internal recruiter only note'));
    }

    public function test_client_messaging_and_cross_company_denial(): void
    {
        [$employer, , $assignment, $admin] = $this->seedStaffingPipeline();
        $other = $this->makeEmployer(['email' => 'staffing-msg-other-'.uniqid().'@example.test']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-staffing-client', $assignment))
            ->assertRedirect();

        $submission = StaffingCandidateSubmission::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.staffing-candidate-submissions.messages.store', $submission), [
                'body' => 'Client-visible coordination message',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($employer)
            ->get(route('account.staffing-submissions.show', $submission))
            ->assertInertia(fn (Assert $page) => $page->has('messages', 1));

        $this->actingAs($employer)
            ->post(route('account.staffing-submissions.messages.store', $submission), [
                'body' => 'Client reply',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($other)
            ->post(route('account.staffing-submissions.messages.store', $submission), [
                'body' => 'Should fail',
            ])
            ->assertForbidden();
    }

    public function test_follow_up_validation_and_guest_denial(): void
    {
        [$employer, , $assignment, $admin] = $this->seedStaffingPipeline();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-staffing-client', $assignment));

        $submission = StaffingCandidateSubmission::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.staffing-candidate-submissions.follow-ups.store', $submission), [
                'follow_up_type' => 'phone_call',
                'summary' => '',
                'visibility' => RecruitmentOperationalVisibility::Internal->value,
            ])
            ->assertSessionHasErrors('summary');

        auth()->logout();
        auth('admin')->logout();

        $this->post(route('admin.staffing-candidate-submissions.follow-ups.store', $submission), [
            'follow_up_type' => 'phone_call',
            'summary' => 'Guest attempt',
            'visibility' => RecruitmentOperationalVisibility::Internal->value,
        ])->assertRedirect();

        $this->assertSame(0, RecruitmentActivity::query()
            ->where('staffing_candidate_submission_id', $submission->id)
            ->where('body', 'Guest attempt')
            ->count());
    }

    public function test_single_follow_up_post_creates_one_activity_record(): void
    {
        [$employer, , $assignment, $admin] = $this->seedStaffingPipeline();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-staffing-client', $assignment));

        $submission = StaffingCandidateSubmission::query()->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.staffing-candidate-submissions.follow-ups.store', $submission), [
                'follow_up_type' => 'phone_call',
                'summary' => 'One record only',
                'visibility' => RecruitmentOperationalVisibility::EmployerShared->value,
            ]);

        $this->assertSame(1, RecruitmentActivity::query()
            ->where('staffing_candidate_submission_id', $submission->id)
            ->where('type', RecruitmentActivityType::StaffingFollowUpRecorded->value)
            ->count());
    }

    public function test_staffing_submit_notifies_employer_once(): void
    {
        [$employer, , $assignment, $admin] = $this->seedStaffingPipeline();

        Notification::fake();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-staffing-client', $assignment))
            ->assertRedirect();

        Notification::assertSentTo($employer, EmployerLifecycleNotification::class, function (EmployerLifecycleNotification $notification) {
            return $notification->event === EmployerNotificationEvent::StaffingCandidateSubmitted;
        });
        Notification::assertNotSentTo($employer, EmployerLifecycleNotification::class, function (EmployerLifecycleNotification $notification) {
            return $notification->event === EmployerNotificationEvent::StaffingSubmissionMessageReceived;
        });
    }

    public function test_joining_fulfilment_rules_unchanged_after_batch7(): void
    {
        [$employer, $enquiry, $assignment, $admin] = $this->seedStaffingPipeline();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.submit-to-staffing-client', $assignment));

        $submission = StaffingCandidateSubmission::query()->firstOrFail();

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::UnderClientReview->value,
            ]);

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::ClientSelected->value,
            ]);

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::OfferInProgress->value,
            ]);

        $this->actingAs($employer)
            ->patch(route('account.staffing-submissions.update', $submission), [
                'status' => StaffingCandidateSubmissionStatus::JoinedConfirmed->value,
                'joining_date' => '2026-12-01',
            ]);

        $enquiry->refresh();
        $this->assertSame(1, (int) $enquiry->fulfilled_joinings_count);
    }

    /**
     * @return array{0: User, 1: EmployerEnquiry, 2: RecruiterTalentPoolAssignment, 3: User}
     */
    private function seedStaffingPipeline(): array
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $candidate = $this->makeCandidate(['email' => 'staffing-b7-'.uniqid().'@example.test']);
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
        $employer = $this->makeEmployer(['email' => 'staffing-b7-client-'.uniqid().'@example.test']);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);

        $this->actingAs($employer)->post(route('account.company.staffing-access.request'))->assertRedirect();
        $this->actingAs($admin, 'admin')
            ->patch(route('admin.employers.staffing-access.approve', $profile))
            ->assertRedirect();

        $profile->refresh();

        $payload = [
            'company_name' => $profile->displayCompany(),
            'contact_person' => $profile->contact_person ?: $employer->name,
            'mobile_number' => $profile->mobile_number ?: ($employer->mobile ?? '9876543210'),
            'email' => $employer->email,
            'industry' => $profile->industry ?: 'Manufacturing',
            'project_location' => 'Chennai',
            'workforce_type' => 'Contract',
            'number_of_people' => 1,
            'required_skills' => 'Operators',
            'period_from' => '2026-08-01',
            'period_to' => '2026-12-31',
            'training_required' => '0',
        ];

        $this->actingAs($employer)
            ->post(route('account.enquiries.store'), $payload)
            ->assertRedirect(route('account.enquiries.index'))
            ->assertSessionHas('success');

        $enquiry = EmployerEnquiry::query()->where('user_id', $employer->id)->latest('id')->firstOrFail();

        return [$employer, $enquiry];
    }
}
