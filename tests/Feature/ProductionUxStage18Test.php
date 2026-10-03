<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\BusinessIdPrefix;
use App\Enums\DocumentType;
use App\Enums\EmployerDocumentType;
use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Enums\PermissionName;
use App\Enums\RecruitmentActivityType;
use App\Enums\RelationshipReviewDirection;
use App\Enums\RelationshipReviewStatus;
use App\Enums\StaffRoleSlug;
use App\Enums\UserActivityType;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\CandidateDocument;
use App\Models\Employee;
use App\Models\EmployerDocument;
use App\Models\EmployerSubmission;
use App\Models\Job;
use App\Models\Placement;
use App\Models\RecruitmentActivity;
use App\Models\RelationshipReview;
use App\Models\User;
use App\Models\UserActivity;
use App\Services\ScorecardService;
use App\Services\ScreeningMatchService;
use App\Support\EmployerPublicShare;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductionUxStage18Test extends TestCase
{
    public function test_joined_employer_and_worker_can_each_submit_one_private_review(): void
    {
        [$employer, $candidate, $profile, $job, $application, $submission, $placement, $employee] = $this->joinedRelationship();

        $this->actingAs($employer)
            ->get(route('account.employer.reviews.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Reviews/Index')
                ->has('relationships', 1)
                ->where('relationships.0.id', $employee->id)
                ->where('relationships.0.can_submit', true)
                ->where('relationships.0.own_review', null)
                ->missing('relationships.0.joining_notes')
                ->missing('relationships.0.match_percent')
                ->missing('relationships.0.scorecards')
                ->missing('relationships.0.internal_notes'));

        $this->actingAs($employer)
            ->from(route('account.employer.reviews.index'))
            ->post(route('account.employer.reviews.store', $employee), [
                'rating' => 5,
                'body' => 'Reliable after joining.',
                'employee_id' => 999999,
                'employer_profile_id' => 999999,
                'candidate_profile_id' => 999999,
                'reviewer_user_id' => $candidate->id,
                'direction' => RelationshipReviewDirection::WorkerToEmployer->value,
            ])
            ->assertRedirect();

        $employerReview = RelationshipReview::query()->where('direction', RelationshipReviewDirection::EmployerToWorker)->first();
        $this->assertNotNull($employerReview);
        $this->assertSame('ONT-REV-000001', $employerReview->business_id);
        $this->assertSame(5, $employerReview->rating);
        $this->assertSame('Reliable after joining.', $employerReview->body);
        $this->assertSame($employee->id, $employerReview->employee_id);
        $this->assertSame($profile->id, $employerReview->employer_profile_id);
        $this->assertSame($candidate->fresh()->candidateProfile?->id, $employerReview->candidate_profile_id);
        $this->assertSame($employer->id, $employerReview->reviewer_user_id);
        $this->assertSame(RelationshipReviewDirection::EmployerToWorker, $employerReview->direction);
        $this->assertSame(RelationshipReviewStatus::Submitted, $employerReview->status);

        $this->actingAs($employer)
            ->from(route('account.employer.reviews.index'))
            ->post(route('account.employer.reviews.store', $employee), [
                'rating' => 4,
                'body' => 'Second employer review.',
            ])
            ->assertSessionHasErrors('employee');

        $this->actingAs($candidate)
            ->get(route('account.reviews.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Reviews/Index')
                ->has('relationships', 1)
                ->where('relationships.0.can_submit', true)
                ->where('relationships.0.counterpart_review.rating', 5)
                ->missing('relationships.0.joining_notes')
                ->missing('relationships.0.match_percent')
                ->missing('relationships.0.scorecards'));

        $this->actingAs($candidate)
            ->post(route('account.reviews.store', $employee), [
                'rating' => 1,
                'body' => 'Clear onboarding after I joined.',
            ])
            ->assertRedirect();

        $workerReview = RelationshipReview::query()->where('direction', RelationshipReviewDirection::WorkerToEmployer)->first();
        $this->assertNotNull($workerReview);
        $this->assertSame('ONT-REV-000002', $workerReview->business_id);
        $this->assertSame(1, $workerReview->rating);
        $this->assertSame($candidate->id, $workerReview->reviewer_user_id);
        $this->assertSame(2, RelationshipReview::query()->count());

        $this->actingAs($candidate)
            ->from(route('account.reviews.index'))
            ->post(route('account.reviews.store', $employee), [
                'rating' => 2,
                'body' => 'Second worker review.',
            ])
            ->assertSessionHasErrors('employee');

        $this->assertTrue(
            UserActivity::query()
                ->where('user_id', $employer->id)
                ->where('type', UserActivityType::SubmittedRelationshipReview->value)
                ->whereJsonContains('metadata->rating', 5)
                ->exists()
        );
        $this->assertFalse(
            UserActivity::query()
                ->where('type', UserActivityType::SubmittedRelationshipReview->value)
                ->where('description', 'like', '%Reliable after joining.%')
                ->exists()
        );
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('employee_id', $employee->id)
                ->where('type', RecruitmentActivityType::RelationshipReviewSubmitted)
                ->exists()
        );

        $this->assertSame($job->id, $employee->job_id);
        $this->assertSame($application->id, $employee->application_id);
        $this->assertSame($submission->id, $employee->employer_submission_id);
        $this->assertSame($placement->id, $employee->placement_id);
    }

    public function test_rating_and_feedback_validation_rejects_invalid_values(): void
    {
        [, , , , , , , $employee] = $this->joinedRelationship();
        $employer = $employee->employer?->user;
        $this->assertNotNull($employer);

        foreach ([0, 6, -1, 5.5, 'abc', null] as $rating) {
            $this->actingAs($employer)
                ->from(route('account.employer.reviews.index'))
                ->post(route('account.employer.reviews.store', $employee), [
                    'rating' => $rating,
                    'body' => 'Valid feedback text.',
                ])
                ->assertSessionHasErrors('rating');
        }

        $this->actingAs($employer)
            ->from(route('account.employer.reviews.index'))
            ->post(route('account.employer.reviews.store', $employee), [
                'rating' => 3,
                'body' => '',
            ])
            ->assertSessionHasErrors('body');

        $this->actingAs($employer)
            ->from(route('account.employer.reviews.index'))
            ->post(route('account.employer.reviews.store', $employee), [
                'rating' => 3,
                'body' => str_repeat('a', 5001),
            ])
            ->assertSessionHasErrors('body');

        $this->actingAs($employer)
            ->post(route('account.employer.reviews.store', $employee), [
                'rating' => 3,
                'body' => '<script>alert(1)</script>Safe feedback',
            ])
            ->assertRedirect();

        $review = RelationshipReview::query()->first();
        $this->assertNotNull($review);
        $this->assertSame(3, $review->rating);
        $this->assertSame('alert(1)Safe feedback', $review->body);
        $this->assertStringNotContainsString('<script>', $review->body);
        $this->assertSame(1, RelationshipReview::query()->count());
    }

    public function test_reviews_require_a_joined_employee_relationship(): void
    {
        [$employer, $candidate, , $job, , , , $employee] = $this->joinedRelationship();

        $applicant = $this->makeCandidate(['name' => 'Only Applied']);
        Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $applicant->id,
            'full_name' => 'Only Applied',
            'status' => ApplicationStatus::New,
        ]);

        $interviewed = $this->makeCandidate(['name' => 'Only Interviewed']);
        $interviewApplication = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $interviewed->id,
            'full_name' => 'Only Interviewed',
            'status' => ApplicationStatus::Shortlisted,
        ]);
        ApplicationInterview::query()->create([
            'application_id' => $interviewApplication->id,
            'sort_order' => 1,
            'scheduled_at' => now()->addDay(),
            'mode' => InterviewMode::Video,
            'interviewer_name' => 'Host',
            'outcome' => InterviewOutcome::Pending,
            'created_by' => $this->makeAdmin()->id,
        ]);

        $submittedOnly = $this->makeCandidate(['name' => 'Only Submitted']);
        $submittedApplication = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $submittedOnly->id,
            'full_name' => 'Only Submitted',
            'status' => ApplicationStatus::Shortlisted,
        ]);
        $this->actingAs($this->makeAdmin())->post(route('admin.applications.submit', $submittedApplication));
        $this->assertNotNull(EmployerSubmission::query()->where('application_id', $submittedApplication->id)->first());

        foreach ([$applicant, $interviewed, $submittedOnly] as $ineligible) {
            $this->actingAs($ineligible)
                ->get(route('account.reviews.index'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->has('relationships', 0));

            $this->actingAs($ineligible)
                ->post(route('account.reviews.store', $employee), [
                    'rating' => 4,
                    'body' => 'Should not be allowed.',
                ])
                ->assertForbidden();
        }

        $unrelatedEmployer = $this->makeEmployer();
        $this->actingAs($unrelatedEmployer)
            ->get(route('account.employer.reviews.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('relationships', 0));
        $this->actingAs($unrelatedEmployer)
            ->post(route('account.employer.reviews.store', $employee), [
                'rating' => 4,
                'body' => 'Should not be allowed.',
            ])
            ->assertForbidden();

        $this->actingAs($candidate)
            ->post(route('account.employer.reviews.store', $employee), [
                'rating' => 4,
                'body' => 'Candidate cannot use employer review route.',
            ])
            ->assertForbidden();

        $this->assertSame(0, RelationshipReview::query()->count());
        $this->assertSame($employer->id, $employee->employer?->user_id);
    }

    public function test_idor_and_spoofed_relationship_ids_cannot_change_ownership(): void
    {
        [$employerA, $candidateA, $profileA, , , , , $employeeA] = $this->joinedRelationship('Asha A');
        [$employerB, $candidateB, $profileB, , , , , $employeeB] = $this->joinedRelationship('Ravi B');

        $this->actingAs($employerA)->post(route('account.employer.reviews.store', $employeeA), [
            'rating' => 4,
            'body' => 'Employer A review.',
        ])->assertRedirect();

        $reviewA = RelationshipReview::query()->where('employee_id', $employeeA->id)->first();
        $this->assertNotNull($reviewA);

        $this->actingAs($employerB)
            ->get(route('account.employer.reviews.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('relationships', 1)
                ->where('relationships.0.id', $employeeB->id)
                ->where('relationships.0.own_review', null));

        $this->actingAs($employerB)->get(route('account.employer.reviews.show', $reviewA))->assertForbidden();
        $this->actingAs($employerB)->get('/account/employee-reviews/'.$reviewA->business_id)->assertNotFound();
        $this->actingAs($employerB)->post(route('account.employer.reviews.store', $employeeA), [
            'rating' => 2,
            'body' => 'Employer B hijack.',
        ])->assertForbidden();
        $this->actingAs($employerB)->post(route('admin.employees.reviews.hide', [$employeeA, $reviewA]))->assertForbidden();

        $this->actingAs($employerB)->post(route('account.employer.reviews.store', $employeeB), [
            'rating' => 3,
            'body' => 'Employer B own review.',
            'employee_id' => $employeeA->id,
            'employer_profile_id' => $profileA->id,
            'candidate_profile_id' => $candidateA->fresh()->candidateProfile?->id,
            'reviewer_user_id' => $employerA->id,
        ])->assertRedirect();

        $reviewB = RelationshipReview::query()->where('employee_id', $employeeB->id)->first();
        $this->assertNotNull($reviewB);
        $this->assertSame($profileB->id, $reviewB->employer_profile_id);
        $this->assertSame($employerB->id, $reviewB->reviewer_user_id);
        $this->assertNotSame($profileA->id, $reviewB->employer_profile_id);

        $this->actingAs($candidateA)->post(route('account.reviews.store', $employeeA), [
            'rating' => 5,
            'body' => 'Worker A review.',
        ])->assertRedirect();
        $workerReviewA = RelationshipReview::query()
            ->where('employee_id', $employeeA->id)
            ->where('direction', RelationshipReviewDirection::WorkerToEmployer)
            ->first();
        $this->assertNotNull($workerReviewA);

        $this->actingAs($candidateB)->get(route('account.reviews.show', $workerReviewA))->assertForbidden();
        $this->actingAs($candidateB)->post(route('account.reviews.store', $employeeA), [
            'rating' => 1,
            'body' => 'Worker B hijack.',
            'candidate_profile_id' => $candidateA->fresh()->candidateProfile?->id,
            'reviewer_user_id' => $candidateA->id,
        ])->assertForbidden();
    }

    public function test_guest_and_public_pages_do_not_expose_reviews(): void
    {
        [$employer, $candidate, $profile, , , , , $employee] = $this->joinedRelationship();
        $profile->forceFill([
            'company_name' => 'Private Reviews Co',
            'public_share' => EmployerPublicShare::normalize(['enabled' => true]),
        ])->save();
        $candidate->forceFill(['username' => 'asha-stage18'])->save();

        $this->actingAs($employer)->post(route('account.employer.reviews.store', $employee), [
            'rating' => 5,
            'body' => 'Must stay private.',
        ])->assertRedirect();

        $review = RelationshipReview::query()->first();
        $this->assertNotNull($review);

        $this->forgetAuthentication();
        $this->get(route('account.employer.reviews.index'))->assertRedirect(route('login'));
        $this->get(route('account.reviews.index'))->assertRedirect(route('login'));
        $this->post(route('account.employer.reviews.store', $employee), [
            'rating' => 4,
            'body' => 'Guest review',
        ])->assertRedirect(route('login'));
        $this->get(route('account.employer.reviews.show', $review))->assertRedirect(route('login'));

        $this->get(route('companies.show', $profile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Companies/Show')
                ->where('company.company_name', 'Private Reviews Co')
                ->missing('company.reviews')
                ->missing('company.rating')
                ->missing('company.average_rating')
                ->missing('reviews'));

        $this->get(route('profiles.show', ['user' => $candidate->username]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profiles/Show')
                ->missing('profile.reviews')
                ->missing('reviews')
                ->missing('rating'));
    }

    public function test_admin_can_view_and_hide_reviews_with_existing_employee_permissions(): void
    {
        [$employer, $candidate, , , , , , $employee] = $this->joinedRelationship();
        $this->actingAs($employer)->post(route('account.employer.reviews.store', $employee), [
            'rating' => 4,
            'body' => 'Admin can read this.',
        ])->assertRedirect();
        $review = RelationshipReview::query()->first();
        $this->assertNotNull($review);

        $content = $this->makeStaff(StaffRoleSlug::ContentManager->value);
        $this->actingAs($content)
            ->get(route('admin.employees.index', ['employee' => $employee->id]))
            ->assertForbidden();
        $this->actingAs($content)
            ->post(route('admin.employees.reviews.hide', [$employee, $review]))
            ->assertForbidden();

        $viewer = $this->makeStaff(StaffRoleSlug::Viewer->value);
        $this->actingAs($viewer)
            ->get(route('admin.employees.index', ['employee' => $employee->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Employees/Index')
                ->where('canHideReviews', false)
                ->has('selected.reviews', 1)
                ->where('selected.reviews.0.rating', 4)
                ->where('selected.reviews.0.body', 'Admin can read this.')
                ->where('selected.reviews.0.reviewer_name', $employer->name)
                ->missing('selected.reviews.0.match_percent')
                ->missing('selected.reviews.0.scorecards')
                ->missing('selected.reviews.0.joining_notes'));
        $this->actingAs($viewer)
            ->post(route('admin.employees.reviews.hide', [$employee, $review]))
            ->assertForbidden();

        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $this->actingAs($recruiter)
            ->post(route('admin.employees.reviews.hide', [$employee, $review]))
            ->assertRedirect();

        $review->refresh();
        $this->assertSame(RelationshipReviewStatus::Hidden, $review->status);
        $this->assertSame($recruiter->id, $review->hidden_by);
        $this->assertNotNull($review->hidden_at);
        $this->assertSame('Admin can read this.', $review->body);
        $this->assertTrue(
            RecruitmentActivity::query()
                ->where('employee_id', $employee->id)
                ->where('type', RecruitmentActivityType::RelationshipReviewHidden)
                ->exists()
        );

        $this->actingAs($candidate)
            ->get(route('account.reviews.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('relationships.0.counterpart_review', null));

        $this->actingAs($employer)
            ->get(route('account.employer.reviews.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('relationships.0.own_review.status', RelationshipReviewStatus::Hidden->value)
                ->where('relationships.0.own_review.body', 'Admin can read this.'));
    }

    public function test_stage_17_and_recruitment_surfaces_remain_protected(): void
    {
        $owner = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $other = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => now()]);
        $this->assertNotNull($owner->employerProfile);
        $this->assertNotNull($other->employerProfile);

        $this->actingAs($owner)->post(route('account.employer.documents.store'), [
            'file' => $this->fakeDocument('owner.pdf'),
            'document_type' => EmployerDocumentType::CompanyDocument->value,
            'title' => 'Owner file',
        ])->assertRedirect();
        $document = EmployerDocument::query()->where('employer_profile_id', $owner->employerProfile->id)->first();
        $this->assertNotNull($document);
        $this->actingAs($other)->get(route('account.employer.documents.download', $document))->assertForbidden();

        $candidate = $this->makeCandidate();
        $resume = $this->giveCandidateResume($candidate, 'library.pdf');
        $this->actingAs($candidate)->post(route('account.documents.store'), [
            'file' => $this->fakeDocument('certificate.pdf'),
            'document_type' => DocumentType::Certificate->value,
            'title' => 'Safety certificate',
        ])->assertRedirect();
        $candidateDocument = CandidateDocument::query()
            ->where('candidate_profile_id', $candidate->fresh()->candidateProfile?->id)
            ->first();
        $this->assertNotNull($candidateDocument);
        $this->actingAs($owner)->get(route('account.resumes.download', $resume))->assertForbidden();
        $this->actingAs($owner)->get(route('account.documents.download', $candidateDocument))->assertForbidden();

        $job = Job::factory()->create(['employer_id' => $owner->employerProfile?->id]);
        $snapshot = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'resume_path' => 'applications/owner-snapshot.pdf',
        ]);
        Storage::disk('resumes')->put('applications/owner-snapshot.pdf', '%PDF-1.4');
        $this->actingAs($owner)->get(route('account.employer.applications.resume', $snapshot))->assertOk();
        $this->actingAs($other)->get(route('account.employer.applications.resume', $snapshot))->assertForbidden();

        $shortlisted = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => $candidate->name,
            'status' => ApplicationStatus::Shortlisted,
        ]);
        $this->actingAs($this->makeAdmin())->post(route('admin.applications.submit', $shortlisted));
        $submission = EmployerSubmission::query()->where('application_id', $shortlisted->id)->first();
        $this->assertNotNull($submission);
        $this->actingAs($owner)->post(route('admin.submissions.select', $submission))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.screening.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('admin.scorecard-templates.index'))->assertForbidden();

        $match = file_get_contents(app_path('Services/ScreeningMatchService.php'));
        $this->assertIsString($match);
        $this->assertStringContainsString(ScreeningMatchService::DISCLAIMER, $match);
        $scorecard = file_get_contents(app_path('Services/ScorecardService.php'));
        $this->assertIsString($scorecard);
        $this->assertStringContainsString('never change application, screening, submission, or placement status', $scorecard);
        $this->assertSame(ScorecardService::class, ScorecardService::class);
    }

    public function test_permission_catalog_and_existing_business_ids_remain_unchanged(): void
    {
        $this->assertCount(34, PermissionName::cases());
        $names = array_map(fn (PermissionName $permission) => $permission->value, PermissionName::cases());
        $this->assertNotContains('reviews.view', $names);
        $this->assertNotContains('reviews.manage', $names);

        $this->assertSame('ONT-U', BusinessIdPrefix::User->value);
        $this->assertSame('ONT-C', BusinessIdPrefix::Candidate->value);
        $this->assertSame('ONT-E', BusinessIdPrefix::Employer->value);
        $this->assertSame('ONT-V', BusinessIdPrefix::Resume->value);
        $this->assertSame('ONT-D', BusinessIdPrefix::Document->value);
        $this->assertSame('ONT-P', BusinessIdPrefix::Placement->value);
        $this->assertSame('ONT-W', BusinessIdPrefix::Employee->value);
        $this->assertSame('ONT-EDC', BusinessIdPrefix::EmployerDocument->value);
        $this->assertSame('ONT-REV', BusinessIdPrefix::RelationshipReview->value);

        $service = file_get_contents(app_path('Services/RelationshipReviewService.php'));
        $this->assertIsString($service);
        $this->assertStringNotContainsString('StaffRoleSlug::', $service);
        $this->assertStringNotContainsString('reviews.view', $service);
    }

    /**
     * @return array{0: User, 1: User, 2: \App\Models\EmployerProfile, 3: Job, 4: Application, 5: EmployerSubmission, 6: Placement, 7: Employee}
     */
    private function joinedRelationship(string $candidateName = 'Asha Candidate'): array
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $job = Job::factory()->create(['employer_id' => $profile->id, 'title' => 'Site Engineer']);
        $candidate = $this->makeCandidate(['name' => $candidateName]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => $candidateName,
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($admin)->post(route('admin.applications.submit', $application));
        $submission = EmployerSubmission::query()->where('application_id', $application->id)->first();
        $this->assertNotNull($submission);
        $this->actingAs($admin)->post(route('admin.submissions.select', $submission));
        $this->actingAs($admin)->post(route('admin.submissions.place', $submission->fresh()));
        $placement = Placement::query()->where('employer_submission_id', $submission->id)->first();
        $this->assertNotNull($placement);
        $this->actingAs($admin)->post(route('admin.placements.offer', $placement), [
            'offered_at' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.accept', $placement));
        $this->actingAs($admin)->post(route('admin.placements.confirm-joining', $placement), [
            'expected_joining_date' => now()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.placements.join', $placement), [
            'actual_joining_date' => now()->toDateString(),
        ]);

        $placement->refresh();
        $employee = $placement->employee;
        $this->assertNotNull($employee);

        return [$employer->fresh(), $candidate->fresh(), $profile->fresh(), $job, $application->fresh(), $submission->fresh(), $placement, $employee->fresh()];
    }
}
