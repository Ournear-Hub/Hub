<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use App\Support\AtsStage;
use App\Support\EmployerApplicationPipeline;
use App\Support\QaHiringFixtures;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Focused regression for confirmed QA defects: admin ATS stage vs application status, public jobs
 * company/employer filter, and employer account company URL redirects.
 */
class QaDefectRegressionTest extends TestCase
{
    public function test_reviewing_status_persists_and_admin_ats_stage_agrees_with_candidate_and_employer_views(): void
    {
        $admin = $this->makeAdmin();
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();
        $job = Job::factory()->create(['employer_id' => $employer->employerProfile->id]);
        $application = Application::factory()->create([
            'job_id' => $job->id,
            'user_id' => $candidate->id,
            'status' => ApplicationStatus::New,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.applications.status', $application), ['status' => 'Reviewing'])
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Reviewing, $application->fresh()->status);
        $this->assertSame('Reviewing', $application->fresh()->getRawOriginal('status'));

        // Admin: Status AND ATS stage both say review has started (previously ATS stage read "Applied / New").
        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['application' => $application->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Applications/Index')
                ->where('applications.data.0.id', $application->id)
                ->where('applications.data.0.status', 'Reviewing')
                ->where('applications.data.0.ats_stage', AtsStage::Screening)
                ->where('applications.data.0.ats_stage_label', 'Screening')
                ->where('selected.status', 'Reviewing')
                ->where('selected.ats_stage', AtsStage::Screening));

        // ATS-stage filter mirrors the resolved stage.
        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['ats_stage' => AtsStage::Screening]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications.data', 1)
                ->where('applications.data.0.id', $application->id));
        $this->actingAs($admin)
            ->get(route('admin.applications.index', ['ats_stage' => AtsStage::Applied]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('applications.data', 0));

        // Candidate sees "Under review".
        $this->actingAs($candidate)
            ->get(route('account.applications.show', $application))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('application.status', 'Under review'));

        // Employer stage counts place it under "Under review", not "Submitted".
        $counts = collect(EmployerApplicationPipeline::summaryForJob($job))->pluck('count', 'key');
        $this->assertSame(1, $counts['under_review']);
        $this->assertSame(0, $counts['submitted']);
    }

    public function test_status_change_back_to_new_returns_ats_stage_to_applied(): void
    {
        $admin = $this->makeAdmin();
        $application = Application::factory()->create(['status' => ApplicationStatus::Reviewing]);
        $this->assertSame(AtsStage::Screening, AtsStage::resolve($application));

        $this->actingAs($admin)
            ->patch(route('admin.applications.status', $application), ['status' => 'New'])
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::New, $application->fresh()->status);
        $this->assertSame(AtsStage::Applied, AtsStage::resolve($application->fresh()));
    }

    public function test_public_jobs_company_filter_narrows_for_employer_linked_and_unlinked_jobs(): void
    {
        $acme = $this->makeEmployer();
        $acme->employerProfile->update(['company_name' => 'Acme Hire Co']);
        $other = $this->makeEmployer();
        $other->employerProfile->update(['company_name' => 'Other Hire Co']);

        $acmeJob = Job::factory()->create(['employer_id' => $acme->employerProfile->id, 'project' => 'Acme Hire Co']);
        $otherJob = Job::factory()->create(['employer_id' => $other->employerProfile->id, 'project' => 'Other Hire Co']);
        $unlinkedJob = Job::factory()->create(['employer_id' => null, 'project' => 'Standalone Client Site']);

        // Employer-linked company.
        $this->get('/jobs?company='.urlencode('Acme Hire Co'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->where('filters.company', 'Acme Hire Co')
                ->has('jobs.data', 1)
                ->where('jobs.data.0.slug', $acmeJob->slug));

        // Company shown on the card for an unlinked job (job.project) is filterable and offered as an option.
        $this->get('/jobs?company='.urlencode('Standalone Client Site'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('jobs.data', 1)
                ->where('jobs.data.0.slug', $unlinkedJob->slug)
                ->where('jobs.data.0.company_name', 'Standalone Client Site')
                ->where('options.companies', fn ($companies) => collect($companies)->contains('Standalone Client Site')
                    && collect($companies)->contains('Acme Hire Co')
                    && collect($companies)->contains('Other Hire Co')));

        // Case-insensitive exact match: a different company is not matched by a partial name.
        $this->get('/jobs?company='.urlencode('acme hire co'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('jobs.data', 1)
                ->where('jobs.data.0.slug', $acmeJob->slug));
        $this->get('/jobs?company=Acme')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('jobs.data', 0));

        // Clearing the filter restores all three jobs.
        $this->get('/jobs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('jobs.data', 3)
                ->where('filters.company', ''));

        $this->assertNotNull($otherJob->id);
    }

    public function test_qa_fixture_keyword_and_company_filters_combine_on_public_jobs(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->markTestSkipped('QA hiring fixtures are restricted to local/testing.');
        }

        app(QaHiringFixtures::class)->provision();

        $this->get('/jobs?'.http_build_query([
            'keyword' => 'QA Fixture Published Role',
            'company' => QaHiringFixtures::COMPANY_A,
            'featured' => 0,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->where('filters.keyword', 'QA Fixture Published Role')
                ->where('filters.company', QaHiringFixtures::COMPANY_A)
                ->where('filters.featured', false)
                ->where('jobs.total', fn (int $total) => $total >= 1)
                ->where('jobs.data', fn ($rows) => collect($rows)->every(
                    fn ($row) => ($row['company_name'] ?? '') === QaHiringFixtures::COMPANY_A,
                )));
    }

    public function test_public_jobs_keyword_and_company_filters_combine(): void
    {
        $acme = $this->makeEmployer();
        $acme->employerProfile->update(['company_name' => 'Acme Hire Co']);
        $other = $this->makeEmployer();
        $other->employerProfile->update(['company_name' => 'Other Hire Co']);

        $acmeMatch = Job::factory()->create([
            'employer_id' => $acme->employerProfile->id,
            'title' => 'Fixture Published Role Alpha',
            'project' => 'Acme Hire Co',
        ]);
        Job::factory()->create([
            'employer_id' => $other->employerProfile->id,
            'title' => 'Fixture Published Role Beta',
            'project' => 'Other Hire Co',
        ]);
        Job::factory()->create([
            'employer_id' => $acme->employerProfile->id,
            'title' => 'Unrelated Acme Opening',
            'project' => 'Acme Hire Co',
        ]);

        $this->get('/jobs?'.http_build_query([
            'keyword' => 'Fixture Published Role',
            'company' => 'Acme Hire Co',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->where('filters.keyword', 'Fixture Published Role')
                ->where('filters.company', 'Acme Hire Co')
                ->has('jobs.data', 1)
                ->where('jobs.data.0.slug', $acmeMatch->slug));
    }

    public function test_public_jobs_company_filter_persists_across_pagination(): void
    {
        $employer = $this->makeEmployer();
        $employer->employerProfile->update(['company_name' => 'Paginate Filter Co']);

        for ($index = 0; $index < 10; $index++) {
            Job::factory()->create([
                'employer_id' => $employer->employerProfile->id,
                'project' => 'Paginate Filter Co',
            ]);
        }

        Job::factory()->create([
            'employer_id' => $this->makeEmployer()->employerProfile->id,
            'project' => 'Other Paginate Co',
        ]);

        $this->get('/jobs?company='.urlencode('Paginate Filter Co'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->where('filters.company', 'Paginate Filter Co')
                ->where('jobs.current_page', 1)
                ->where('jobs.last_page', 2)
                ->has('jobs.data', 9));

        $this->get('/jobs?company='.urlencode('Paginate Filter Co').'&page=2')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.company', 'Paginate Filter Co')
                ->where('jobs.current_page', 2)
                ->has('jobs.data', 1)
                ->where('jobs.data.0.company_name', 'Paginate Filter Co'));
    }

    public function test_public_jobs_employer_query_param_narrows_results(): void
    {
        $acme = $this->makeEmployer();
        $acme->employerProfile->update(['company_name' => 'Acme Hire Co']);
        $other = $this->makeEmployer();
        $other->employerProfile->update(['company_name' => 'Other Hire Co']);

        $acmeJob = Job::factory()->create(['employer_id' => $acme->employerProfile->id]);
        Job::factory()->create(['employer_id' => $other->employerProfile->id]);

        $byId = fn (Assert $page) => $page
            ->component('Jobs/Index')
            ->has('jobs.data', 1)
            ->where('jobs.data.0.slug', $acmeJob->slug)
            ->where('filters.company', 'Acme Hire Co');

        $this->get('/jobs?employer='.$acme->employerProfile->id)->assertOk()->assertInertia($byId);
        $this->get('/jobs?employer='.$acme->employerProfile->business_id)->assertOk()->assertInertia($byId);
        $this->get('/jobs?employer='.urlencode('Acme Hire Co'))->assertOk()->assertInertia($byId);

        // Unknown employer narrows to nothing (never silently ignored).
        $this->get('/jobs?employer=999999')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('jobs.data', 0));
        $this->get('/jobs?employer=ONT-E-999999')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('jobs.data', 0));
    }

    public function test_hire_resume_print_styles_do_not_hide_resume_header(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertIsString($css);
        $this->assertStringContainsString(
            'header:not(.ourneer-resume-header)',
            $css,
            'Print styles must hide site chrome headers but keep .ourneer-resume-header visible.',
        );
    }

    public function test_candidate_profile_page_reflects_uploaded_library_resume(): void
    {
        $candidate = $this->makeCandidate();
        $profile = $candidate->candidateProfile;
        $this->assertNotNull($profile);

        $this->actingAs($candidate)
            ->post(route('account.resumes.store'), [
                'file' => $this->fakeResume('qa-module-cv.pdf'),
                'title' => 'QA Module CV',
                'visibility' => 'private',
                'is_primary' => true,
            ])
            ->assertRedirect();

        $this->actingAs($candidate)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.library_resume.has_upload', true)
                ->where('profile.library_resume.title', 'QA Module CV'));
    }

    public function test_account_company_urls_resolve_company_workspace(): void
    {
        $employer = $this->makeEmployer();

        $this->actingAs($employer)
            ->get('/account/company')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Profile')
                ->where('companyWorkspace', true));
        $this->actingAs($employer)
            ->get('/account/profile/company')
            ->assertRedirect('/account/company');

        $this->actingAs($employer)
            ->get('/account/profile')
            ->assertOk();

        auth()->guard('account')->logout();
        $this->get('/account/company')->assertRedirect(route('login'));
    }
}
