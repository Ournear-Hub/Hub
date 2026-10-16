<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\RecruiterTalentPoolAssignmentStatus;
use App\Enums\StaffRoleSlug;
use App\Models\Application;
use App\Models\Job;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\User;
use App\Notifications\LifecycleNotification;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecruiterTalentPoolAssignmentTest extends TestCase
{
    public function test_admin_can_assign_shortlisted_candidate_to_internal_recruiter_job(): void
    {
        Notification::fake();

        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $recruiterA = $this->makeInternalRecruiter(['email' => 'recruiter-a-tp@example.test']);
        $recruiterB = $this->makeInternalRecruiter(['email' => 'recruiter-b-tp@example.test']);
        $employer = $this->makeEmployer(['email' => 'job-owner-tp@example.test']);
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'job_id' => $job->id,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $recruiterA->id,
                'assignment_type' => 'job',
                'application_id' => $application->id,
                'job_id' => $job->id,
                'admin_notes' => 'Priority manpower support',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $assignment = RecruiterTalentPoolAssignment::query()->first();
        $this->assertNotNull($assignment);
        $this->assertSame($recruiterA->id, $assignment->assigned_recruiter_user_id);
        $this->assertSame($application->id, $assignment->application_id);
        $this->assertSame(RecruiterTalentPoolAssignmentStatus::PendingReview, $assignment->status);

        $this->actingAs($recruiterA, 'admin')
            ->get(route('admin.talent-pool-assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('assignments.data', 1)
                ->where('assignments.data.0.candidate_name', $candidate->name));

        $this->actingAs($recruiterB, 'admin')
            ->get(route('admin.talent-pool-assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('assignments.data', 0));

        Notification::assertSentTo($recruiterA, LifecycleNotification::class);
    }

    public function test_internal_recruiter_status_update_persists_and_is_visible_to_admin(): void
    {
        Notification::fake();

        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        [$assignment, $recruiter] = $this->seedAssignment($admin);

        $this->actingAs($recruiter, 'admin')
            ->put(route('admin.talent-pool-assignments.status.update', $assignment), [
                'progress_stage' => 'contacted',
                'notes' => 'Review started',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $assignment->refresh();
        $this->assertSame(RecruiterTalentPoolAssignmentStatus::InProgress, $assignment->status);
        $this->assertSame('contacted', $assignment->progress_stage?->value);
        $this->assertSame('Review started', $assignment->recruiter_notes);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.talent-pool-assignments.index', ['assignment' => $assignment->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('assignments.data.0.status', RecruiterTalentPoolAssignmentStatus::InProgress->value));

    }

    public function test_internal_recruiter_cannot_update_another_recruiters_assignment(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        [$assignment] = $this->seedAssignment($admin);
        $otherRecruiter = $this->makeInternalRecruiter(['email' => 'other-recruiter-tp@example.test']);

        $this->actingAs($otherRecruiter, 'admin')
            ->put(route('admin.talent-pool-assignments.status.update', $assignment), [
                'status' => RecruiterTalentPoolAssignmentStatus::Declined->value,
            ])
            ->assertForbidden();
    }

    public function test_duplicate_active_assignment_for_same_recruiter_job_is_rejected(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        [$assignment, $recruiter] = $this->seedAssignment($admin);
        $candidate = User::query()->findOrFail($assignment->candidate_user_id);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $recruiter->id,
                'assignment_type' => 'job',
                'job_id' => $assignment->job_id,
            ])
            ->assertSessionHasErrors('assignment');
    }

    public function test_general_follow_up_can_assign_without_shortlisted_application(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $recruiter = $this->makeInternalRecruiter();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $recruiter->id,
                'assignment_type' => 'general',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('recruiter_talent_pool_assignments', [
            'candidate_user_id' => $candidate->id,
            'assigned_recruiter_user_id' => $recruiter->id,
            'assignment_type' => 'general',
            'job_id' => null,
            'employer_submission_id' => null,
        ]);
    }

    public function test_talent_pool_assignment_type_labels_are_consistent_in_admin_views(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate(['email' => 'tp-label@example.test']);
        $recruiter = $this->makeInternalRecruiter(['email' => 'tp-label-recruiter@example.test']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $recruiter->id,
                'assignment_type' => 'talent_pool',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $assignment = RecruiterTalentPoolAssignment::query()->latest('id')->first();
        $this->assertNotNull($assignment);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.talent-pool-assignments.index', ['assignment' => $assignment->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected_assignment.assignment_type', 'talent_pool')
                ->where('selected_assignment.assignment_type_label', 'Talent Pool')
                ->where('selected_assignment.requirement_label', null));

        $this->actingAs($recruiter, 'admin')
            ->get(route('admin.talent-pool-assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('assignments.data', 1)
                ->where('assignments.data.0.assignment_type_label', 'Talent Pool'));
    }

    public function test_internal_recruiter_appears_in_admin_recruiter_list(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $recruiter = $this->makeInternalRecruiter(['name' => 'Internal Recruiter QA']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.talent-pool-assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/TalentPoolAssignments/Index')
                ->where('recruiters', fn ($recruiters) => collect($recruiters)->contains(
                    fn (array $row) => $row['id'] === $recruiter->id,
                )));
    }

    public function test_employer_account_is_rejected_for_candidate_assignment(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $employer->id,
                'assignment_type' => 'talent_pool',
            ])
            ->assertSessionHasErrors('assigned_recruiter_user_id');
    }

    public function test_admin_can_bulk_assign_multiple_candidates(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        $candidateA = $this->makeCandidate(['email' => 'bulk-a@example.test']);
        $candidateB = $this->makeCandidate(['email' => 'bulk-b@example.test']);
        $recruiter = $this->makeInternalRecruiter(['email' => 'bulk-recruiter@example.test']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_ids' => [$candidateA->id, $candidateB->id],
                'assigned_recruiter_user_id' => $recruiter->id,
                'assignment_type' => 'general',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(2, RecruiterTalentPoolAssignment::query()->count());
    }

    public function test_archive_preserves_assignment_record(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);
        [$assignment, $recruiter] = $this->seedAssignment($admin);

        $this->actingAs($recruiter, 'admin')
            ->post(route('admin.talent-pool-assignments.archive', $assignment))
            ->assertRedirect(route('admin.talent-pool-assignments.index'));

        $assignment->refresh();
        $this->assertNotNull($assignment->archived_at);
        $this->assertDatabaseHas('recruiter_talent_pool_assignments', ['id' => $assignment->id]);
        $this->assertDatabaseHas('users', ['id' => $assignment->candidate_user_id]);
    }

    private function makeInternalRecruiter(array $overrides = []): User
    {
        return $this->makeStaff(StaffRoleSlug::Recruiter->value, $overrides);
    }

    /**
     * @return array{0: RecruiterTalentPoolAssignment, 1: User}
     */
    private function seedAssignment(User $admin): array
    {
        $candidate = $this->makeCandidate();
        $recruiter = $this->makeInternalRecruiter(['email' => 'seed-recruiter-tp@example.test']);
        $employer = $this->makeEmployer(['email' => 'seed-job-owner-tp@example.test']);
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'job_id' => $job->id,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.talent-pool-assignments.store'), [
                'candidate_user_id' => $candidate->id,
                'assigned_recruiter_user_id' => $recruiter->id,
                'assignment_type' => 'job',
                'application_id' => $application->id,
                'job_id' => $job->id,
            ])
            ->assertRedirect();

        $assignment = RecruiterTalentPoolAssignment::query()->firstOrFail();

        return [$assignment, $recruiter];
    }
}
