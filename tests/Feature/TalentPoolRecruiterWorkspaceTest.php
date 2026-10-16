<?php

namespace Tests\Feature;

use App\Enums\EmployerSubmissionStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\RecruitmentOperationalVisibility;
use App\Enums\StaffRoleSlug;
use App\Models\RecruitmentActivity;
use App\Models\TalentPoolCompanySubmission;
use App\Models\User;
use App\Services\TalentPoolCompanySubmissionService;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TalentPoolRecruiterWorkspaceTest extends TestCase
{
    private function seedSubmission(User $recruiter, User $candidate, User $employerUser): TalentPoolCompanySubmission
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $profile = $employerUser->employerProfile;
        $this->assertNotNull($profile);

        app(\App\Services\RecruiterTalentPoolAssignmentService::class)->assign(
            $admin,
            $candidate,
            $recruiter,
            \App\Enums\RecruiterTalentPoolAssignmentType::TalentPool,
        );

        return app(TalentPoolCompanySubmissionService::class)->submit(
            $candidate,
            $profile,
            $recruiter,
        );
    }

    public function test_internal_recruiter_workspace_is_scoped_to_own_records(): void
    {
        $recruiterA = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'ws-rec-a@example.test']);
        $recruiterB = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'ws-rec-b@example.test']);
        $candidateA = $this->makeCandidate(['email' => 'ws-cand-a@example.test']);
        $candidateB = $this->makeCandidate(['email' => 'ws-cand-b@example.test']);
        $employerA = $this->makeEmployer(['email' => 'ws-emp-a@example.test']);
        $employerB = $this->makeEmployer(['email' => 'ws-emp-b@example.test']);

        $this->seedSubmission($recruiterA, $candidateA, $employerA);
        $this->seedSubmission($recruiterB, $candidateB, $employerB);

        $this->actingAs($recruiterA, 'admin')
            ->get(route('admin.talent-pool-workspace.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('recruiter_workspace_scoped', true)
                ->where('overview.submissions_count', 1)
                ->where('submissions.0.candidate_name', $candidateA->name));

        $submissionB = TalentPoolCompanySubmission::query()->where('candidate_user_id', $candidateB->id)->firstOrFail();
        $this->actingAs($recruiterA, 'admin')
            ->get(route('admin.talent-pool-company-submissions.show', $submissionB))
            ->assertForbidden();
    }

    public function test_recruiter_records_internal_follow_up_hidden_from_employer(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'fu-rec@example.test']);
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer(['email' => 'fu-emp@example.test']);
        $submission = $this->seedSubmission($recruiter, $candidate, $employer);

        $this->actingAs($recruiter, 'admin')
            ->post(route('admin.talent-pool-company-submissions.follow-ups.store', $submission), [
                'follow_up_type' => 'phone_call',
                'summary' => 'Called HR — internal only issue logged.',
                'issue' => 'Budget freeze',
                'visibility' => RecruitmentOperationalVisibility::Internal->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('recruitment_activities', [
            'talent_pool_company_submission_id' => $submission->id,
            'type' => RecruitmentActivityType::TalentPoolFollowUpRecorded->value,
            'visibility' => RecruitmentOperationalVisibility::Internal->value,
        ]);

        $this->actingAs($employer)
            ->get(route('account.coordinated-talent-pool.show', $submission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('shared_follow_ups', [])
                ->missing('submission.internal_notes'));
    }

    public function test_employer_shared_follow_up_and_messages_respect_participants(): void
    {
        Notification::fake();
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'msg-rec@example.test']);
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer(['email' => 'msg-emp@example.test']);
        $submission = $this->seedSubmission($recruiter, $candidate, $employer);

        $this->actingAs($recruiter, 'admin')
            ->post(route('admin.talent-pool-company-submissions.follow-ups.store', $submission), [
                'follow_up_type' => 'message',
                'summary' => 'Shared update for employer contact.',
                'visibility' => RecruitmentOperationalVisibility::EmployerShared->value,
            ])
            ->assertSessionHas('success');

        $this->actingAs($recruiter, 'admin')
            ->post(route('admin.talent-pool-company-submissions.messages.store', $submission), [
                'body' => 'Please review this profile this week.',
            ])
            ->assertSessionHas('success');

        $this->actingAs($employer)
            ->get(route('account.coordinated-talent-pool.show', $submission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('shared_follow_ups', 1)
                ->has('messages', 1));

        $this->actingAs($employer)
            ->post(route('account.coordinated-talent-pool.messages.store', $submission), [
                'body' => 'We will review by Friday.',
            ])
            ->assertSessionHas('success');

        $this->actingAs($recruiter, 'admin')
            ->get(route('admin.talent-pool-company-submissions.show', $submission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('messages', 2));
    }

    public function test_employer_status_history_keeps_employer_actor(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();
        $submission = $this->seedSubmission($recruiter, $candidate, $employer);

        $this->actingAs($employer)
            ->patch(route('account.coordinated-talent-pool.status.update', $submission), [
                'action' => 'review',
                'notes' => 'Reviewing this week.',
            ])
            ->assertRedirect();

        $this->actingAs($this->makeStaff(StaffRoleSlug::SuperAdmin->value), 'admin')
            ->get(route('admin.talent-pool-company-submissions.show', $submission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('submission.status', EmployerSubmissionStatus::UnderReview->value)
                ->where('status_timeline', function ($timeline) use ($employer) {
                    return collect($timeline)->contains(
                        fn ($row) => ($row['actor_name'] ?? null) === $employer->name
                            && ($row['label'] ?? '') !== '',
                    );
                }));

        $this->assertSame(0, \App\Models\Application::query()->where('user_id', $candidate->id)->count());
    }

    public function test_reassignment_preserves_historical_follow_up_activities(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $recruiterA = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'hist-a@example.test']);
        $recruiterB = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'hist-b@example.test']);
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();
        $submission = $this->seedSubmission($recruiterA, $candidate, $employer);

        $this->actingAs($recruiterA, 'admin')
            ->post(route('admin.talent-pool-company-submissions.follow-ups.store', $submission), [
                'follow_up_type' => 'phone_call',
                'summary' => 'Historical call before reassignment.',
                'visibility' => RecruitmentOperationalVisibility::Internal->value,
            ]);

        $submission->update(['responsible_recruiter_user_id' => $recruiterB->id]);

        $activityCount = RecruitmentActivity::query()
            ->where('talent_pool_company_submission_id', $submission->id)
            ->count();
        $this->assertSame(2, $activityCount);

        $this->actingAs($recruiterB, 'admin')
            ->get(route('admin.talent-pool-company-submissions.show', $submission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('follow_ups', 1));
    }

    public function test_submission_notification_links_for_responsible_recruiter(): void
    {
        Notification::fake();
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value, ['email' => 'notif-rec@example.test']);
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();
        $this->seedSubmission($recruiter, $candidate, $employer);

        Notification::assertSentTo(
            $recruiter,
            \App\Notifications\LifecycleNotification::class,
            fn ($notification) => str_contains((string) data_get($notification->toArray($recruiter), 'url'), 'talent-pool-company-submissions'),
        );
    }
}
