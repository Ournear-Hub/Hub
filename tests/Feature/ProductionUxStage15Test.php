<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\EmployerSubmissionStatus;
use App\Models\Application;
use App\Models\CandidateDocument;
use App\Models\EmployerEnquiry;
use App\Models\EmployerSubmission;
use App\Models\Job;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductionUxStage15Test extends TestCase
{
    public function test_employer_dashboard_is_ownership_scoped(): void
    {
        $owner = $this->eligibleEmployer();
        $other = $this->eligibleEmployer();
        $ownerCandidate = $this->makeCandidate(['name' => 'Owner Applicant']);
        $otherCandidate = $this->makeCandidate(['name' => 'Other Applicant']);

        $ownedJob = Job::factory()->create([
            'employer_id' => $owner->employerProfile?->id,
            'title' => 'Owner Role',
        ]);
        $foreignJob = Job::factory()->create([
            'employer_id' => $other->employerProfile?->id,
            'title' => 'Foreign Role',
        ]);

        $ownedApplication = Application::factory()->create([
            'job_id' => $ownedJob->id,
            'user_id' => $ownerCandidate->id,
            'full_name' => 'Owner Applicant',
            'email' => 'owner-applicant@example.com',
        ]);
        Application::factory()->create([
            'job_id' => $foreignJob->id,
            'user_id' => $otherCandidate->id,
            'full_name' => 'Other Applicant',
            'email' => 'other-applicant@example.com',
        ]);

        EmployerSubmission::query()->create([
            'employer_profile_id' => $owner->employerProfile?->id,
            'job_id' => $ownedJob->id,
            'application_id' => $ownedApplication->id,
            'status' => EmployerSubmissionStatus::Submitted,
            'submitted_by' => $owner->id,
            'submitted_at' => now(),
        ]);
        EmployerSubmission::query()->create([
            'employer_profile_id' => $other->employerProfile?->id,
            'job_id' => $foreignJob->id,
            'application_id' => Application::query()->where('job_id', $foreignJob->id)->value('id'),
            'status' => EmployerSubmissionStatus::Submitted,
            'submitted_by' => $other->id,
            'submitted_at' => now(),
        ]);

        EmployerEnquiry::factory()->create([
            'user_id' => $owner->id,
            'company_name' => 'Owner Enquiry Co',
            'internal_notes' => [['body' => 'Secret staff note', 'author_id' => 1, 'author_name' => 'Admin', 'created_at' => now()->toDateTimeString()]],
        ]);
        EmployerEnquiry::factory()->create([
            'user_id' => $other->id,
            'company_name' => 'Other Enquiry Co',
        ]);

        $this->actingAs($owner)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('dashboard.jobs_count', 1)
                ->where('dashboard.applicants_count', 1)
                ->where('dashboard.submissions_count', 1)
                ->where('dashboard.enquiries_count', 1)
                ->has('dashboard.recent_applicants', 1)
                ->where('dashboard.recent_applicants.0.name', 'Owner Applicant')
                ->where('dashboard.recent_applicants.0.job.title', 'Owner Role')
                ->missing('dashboard.recent_applicants.0.email')
                ->missing('dashboard.recent_applicants.0.mobile')
                ->missing('dashboard.recent_applicants.0.match_percent')
                ->missing('dashboard.recent_applicants.0.screening_notes')
                ->has('dashboard.recent_submissions', 1)
                ->where('dashboard.recent_submissions.0.name', 'Owner Applicant')
                ->missing('dashboard.recent_submissions.0.notes')
                ->has('dashboard.next_actions'));

        $this->actingAs($other)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('dashboard.jobs_count', 1)
                ->where('dashboard.applicants_count', 1)
                ->where('dashboard.submissions_count', 1)
                ->where('dashboard.enquiries_count', 1)
                ->where('dashboard.recent_applicants.0.name', 'Other Applicant')
                ->where('dashboard.recent_applicants.0.job.title', 'Foreign Role'));
    }

    public function test_employer_applicants_are_paginated_privacy_safe_and_job_scoped(): void
    {
        $owner = $this->eligibleEmployer();
        $other = $this->eligibleEmployer();
        $candidate = $this->makeCandidate(['name' => 'Visible Page Applicant']);
        $job = Job::factory()->create([
            'employer_id' => $owner->employerProfile?->id,
            'title' => 'Paginated Role',
        ]);
        $foreign = Job::factory()->create(['employer_id' => $other->employerProfile?->id]);

        Application::factory()->count(15)->create(['job_id' => $job->id]);
        Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => 'Visible Page Applicant',
            'email' => 'visible-page@example.com',
            'mobile' => '07000000001',
            'created_at' => now()->addMinute(),
            'updated_at' => now()->addMinute(),
        ]);
        Application::factory()->create(['job_id' => $foreign->id, 'full_name' => 'Foreign Applicant']);

        $this->actingAs($owner)
            ->get(route('account.employer.jobs.applicants', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Applicants/Index')
                ->where('job.title', 'Paginated Role')
                ->where('job.slug', $job->slug)
                ->where('applications.total', 16)
                ->where('applications.current_page', 1)
                ->has('applications.data', 15)
                ->where('applications.data', fn ($rows) => collect($rows)->contains(
                    fn ($row) => ($row['name'] ?? null) === 'Visible Page Applicant'
                ))
                ->missing('applications.data.0.email')
                ->missing('applications.data.0.mobile')
                ->missing('applications.data.0.current_salary')
                ->missing('applications.data.0.salary')
                ->missing('applications.data.0.notice_period')
                ->missing('applications.data.0.skills')
                ->missing('applications.data.0.certifications')
                ->missing('applications.data.0.languages')
                ->missing('applications.data.0.documents')
                ->missing('applications.data.0.match_percent')
                ->missing('applications.data.0.screening_notes')
                ->missing('applications.data.0.scorecards')
                ->missing('applications.data.0.resume_path'));

        $this->actingAs($owner)
            ->get(route('account.employer.jobs.applicants', ['job' => $job, 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Applicants/Index')
                ->where('applications.current_page', 2)
                ->has('applications.data', 1)
                ->where('applications.total', 16));

        $this->actingAs($other)->get(route('account.employer.jobs.applicants', $job))->assertForbidden();
        $this->actingAs($this->makeCandidate())->get(route('account.employer.jobs.applicants', $job))->assertForbidden();
        $this->forgetAuthentication();
        $this->get(route('account.employer.jobs.applicants', $job))->assertRedirect(route('login'));
    }

    public function test_employer_submissions_and_enquiries_remain_ownership_scoped(): void
    {
        $owner = $this->eligibleEmployer();
        $other = $this->eligibleEmployer();
        $candidate = $this->makeCandidate(['name' => 'Submitted Visible']);
        $job = Job::factory()->create([
            'employer_id' => $owner->employerProfile?->id,
            'title' => 'Submission Role',
        ]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'full_name' => 'Submitted Visible',
            'email' => 'submitted-visible@example.com',
            'internal_notes' => [['body' => 'Recruiter only', 'author_id' => 1, 'author_name' => 'Admin', 'created_at' => now()->toDateTimeString()]],
        ]);

        $submission = EmployerSubmission::query()->create([
            'employer_profile_id' => $owner->employerProfile?->id,
            'job_id' => $job->id,
            'application_id' => $application->id,
            'status' => EmployerSubmissionStatus::UnderReview,
            'notes' => 'Internal recruiter note',
            'submitted_by' => $owner->id,
            'submitted_at' => now(),
        ]);

        EmployerEnquiry::factory()->create([
            'user_id' => $owner->id,
            'company_name' => 'Owner Enquiry Co',
            'workforce_type' => 'Contract',
            'required_skills' => 'Steel fixing',
            'project_location' => 'Manchester',
            'number_of_people' => 8,
            'additional_requirements' => 'CSCS required',
            'internal_notes' => [['body' => 'Do not show employer', 'author_id' => 1, 'author_name' => 'Admin', 'created_at' => now()->toDateTimeString()]],
        ]);
        EmployerEnquiry::factory()->create([
            'user_id' => $other->id,
            'company_name' => 'Hidden Enquiry Co',
            'internal_notes' => [['body' => 'Other employer secret', 'author_id' => 1, 'author_name' => 'Admin', 'created_at' => now()->toDateTimeString()]],
        ]);

        $this->actingAs($owner)
            ->get(route('account.employer.submissions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Submissions/Index')
                ->has('summary')
                ->where('summary.candidate_submissions', 1)
                ->has('requirements', 1)
                ->has('submissions.data', 1)
                ->where('submissions.data.0.name', 'Submitted Visible')
                ->where('submissions.data.0.submission_id', $submission->id)
                ->where('submissions.data.0.business_id', $submission->business_id)
                ->where('submissions.data.0.status', EmployerSubmissionStatus::UnderReview->value)
                ->where('submissions.data.0.job.title', 'Submission Role')
                ->where('submissions.data.0.submission_href', route('account.employer.submissions.show', $submission, false))
                ->missing('submissions.data.0.email')
                ->missing('submissions.data.0.notes')
                ->missing('submissions.data.0.internal_notes')
                ->missing('submissions.data.0.match_percent')
                ->missing('submissions.data.0.decided_by'));

        $this->actingAs($owner)
            ->get(route('account.employer.submissions.show', $submission))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Submissions/Show')
                ->where('submission.name', 'Submitted Visible')
                ->missing('submission.notes'));

        $this->actingAs($owner)
            ->get(route('account.employer.submissions.requirement', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Submissions/Requirement')
                ->where('requirement.title', 'Submission Role')
                ->where('submissions.total', 1));

        $this->actingAs($other)
            ->get(route('account.employer.submissions.show', $submission))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('account.employer.submissions.requirement', Job::factory()->create([
                'employer_id' => $other->employerProfile?->id,
            ])))
            ->assertNotFound();

        $this->actingAs($other)
            ->get(route('account.employer.submissions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Employer/Submissions/Index')
                ->has('submissions.data', 0));

        $ownerEnquiry = EmployerEnquiry::query()
            ->where('user_id', $owner->id)
            ->where('company_name', 'Owner Enquiry Co')
            ->firstOrFail();

        $this->actingAs($owner)
            ->get(route('account.enquiries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Enquiries')
                ->has('summary')
                ->where('summary.total_requests', 1)
                ->where('summary.active_requests', 1)
                ->has('enquiries.data', 1)
                ->where('enquiries.data.0.company_name', 'Owner Enquiry Co')
                ->where('enquiries.data.0.workforce_type', 'Contract')
                ->where('enquiries.data.0.required_skills', 'Steel fixing')
                ->where('enquiries.data.0.project_location', 'Manchester')
                ->where('enquiries.data.0.number_of_people', 8)
                ->where('enquiries.data.0.additional_requirements', 'CSCS required')
                ->where('enquiries.data.0.status', 'Received')
                ->where('enquiries.data.0.detail_href', route('account.enquiries.show', $ownerEnquiry, false))
                ->missing('enquiries.data.0.internal_notes')
                ->missing('enquiries.data.0.crm_lead_id'));

        $this->actingAs($owner)
            ->get(route('account.enquiries.show', $ownerEnquiry))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Enquiries/Show')
                ->where('enquiry.company_name', 'Owner Enquiry Co')
                ->missing('enquiry.internal_notes'));

        $this->actingAs($other)
            ->get(route('account.enquiries.show', $ownerEnquiry))
            ->assertNotFound();

        $this->actingAs($other)
            ->get(route('account.enquiries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Enquiries')
                ->has('enquiries.data', 1)
                ->where('enquiries.data.0.company_name', 'Hidden Enquiry Co')
                ->missing('enquiries.data.0.internal_notes'));

        $this->actingAs($this->makeCandidate())->get(route('account.enquiries.index'))->assertForbidden();
        $this->actingAs($this->makeCandidate())->get(route('account.employer.submissions.index'))->assertForbidden();
    }

    public function test_application_snapshot_resume_stays_ownership_scoped_and_supports_inline_pdf(): void
    {
        $owner = $this->eligibleEmployer();
        $other = $this->eligibleEmployer();
        $candidate = $this->makeCandidate();
        $job = Job::factory()->create(['employer_id' => $owner->employerProfile?->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'resume_path' => 'applications/owner-snapshot.pdf',
        ]);
        Storage::disk('resumes')->put('applications/owner-snapshot.pdf', '%PDF-1.4');

        $docx = Application::factory()->create([
            'job_id' => $job->id,
            'resume_path' => 'applications/owner-snapshot.docx',
        ]);
        Storage::disk('resumes')->put('applications/owner-snapshot.docx', 'not-a-pdf');

        $download = $this->actingAs($owner)->get(route('account.employer.applications.resume', $application));
        $download->assertOk();
        $this->assertStringContainsString('attachment', strtolower((string) $download->headers->get('Content-Disposition')));
        $download->assertDontSee('applications/owner-snapshot.pdf', false);

        $inline = $this->actingAs($owner)
            ->get(route('account.employer.applications.resume', $application).'?disposition=inline');
        $inline->assertOk();
        $this->assertStringContainsString('inline', strtolower((string) $inline->headers->get('Content-Disposition')));

        $forcedDownload = $this->actingAs($owner)
            ->get(route('account.employer.applications.resume', $docx).'?disposition=inline');
        $forcedDownload->assertOk();
        $this->assertStringContainsString('attachment', strtolower((string) $forcedDownload->headers->get('Content-Disposition')));

        $this->actingAs($other)->get(route('account.employer.applications.resume', $application))->assertForbidden();
        $this->actingAs($candidate)->get(route('account.employer.applications.resume', $application))->assertForbidden();
        $this->forgetAuthentication();
        $this->get(route('account.employer.applications.resume', $application))->assertRedirect(route('login'));
        $this->actingAs($other)->get(route('account.employer.jobs.edit', $job))->assertForbidden();
    }

    public function test_employer_cannot_access_admin_screening_or_candidate_private_files(): void
    {
        $employer = $this->eligibleEmployer();
        $candidate = $this->makeCandidate();
        $resume = $this->giveCandidateResume($candidate, 'library.pdf');

        $this->actingAs($candidate)->post(route('account.documents.store'), [
            'file' => $this->fakeDocument('certificate.pdf'),
            'document_type' => DocumentType::Certificate->value,
            'title' => 'Safety certificate',
        ])->assertRedirect();

        $document = CandidateDocument::query()
            ->where('candidate_profile_id', $candidate->fresh()->candidateProfile?->id)
            ->first();
        $this->assertNotNull($document);

        $this->actingAs($employer)->get(route('admin.screening.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.interviews.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.candidates.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('account.resumes.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('account.resumes.builder'))->assertForbidden();
        $this->actingAs($employer)->get(route('account.resumes.download', $resume))->assertForbidden();
        $this->actingAs($employer)->get(route('account.resumes.view', $resume))->assertForbidden();
        $this->actingAs($employer)->get(route('account.documents.index'))->assertForbidden();
        $this->actingAs($employer)->get(route('account.documents.download', $document))->assertForbidden();
        $this->actingAs($employer)->get(route('account.documents.view', $document))->assertForbidden();
    }

    private function eligibleEmployer()
    {
        return $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
    }
}
