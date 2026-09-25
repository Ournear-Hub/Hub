<?php

namespace Tests\Feature;

use App\Models\Job;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    public function test_homepage_loads(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Home'));
    }

    public function test_jobs_index_loads(): void
    {
        Job::factory()->create(['title' => 'Site Engineer']);
        Job::factory()->create(['title' => 'Project Coordinator']);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->has('jobs.data', 2));
    }

    public function test_published_job_detail_loads(): void
    {
        $employer = $this->makeEmployer(['photo_path' => 'users/company-logo.png']);
        $employer->employerProfile()->update(['company_name' => 'Northstar Engineering']);
        Storage::disk('public')->put('users/company-logo.png', 'image');

        $job = Job::factory()->create([
            'title' => 'Quantity Surveyor',
            'employer_id' => $employer->employerProfile->id,
            'salary' => '₹8–10 LPA',
            'experience' => '3-5 years',
            'employment_type' => 'Full-time',
            'responsibilities' => 'Manage project costs.',
            'requirements' => 'Relevant surveying experience.',
            'what_we_offer' => 'Health cover and training.',
        ]);

        $this->get(route('jobs.show', $job))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Show')
                ->where('job.title', 'Quantity Surveyor')
                ->where('job.slug', $job->slug)
                ->where('job.company_name', 'Northstar Engineering')
                ->where('job.company_logo_url', '/storage/users/company-logo.png')
                ->where('job.salary', '₹8–10 LPA')
                ->where('job.experience', '3-5 years')
                ->where('job.employment_type', 'Full-time')
                ->where('job.responsibilities', 'Manage project costs.')
                ->where('job.requirements', 'Relevant surveying experience.')
                ->where('job.what_we_offer', 'Health cover and training.'));

        $cardSource = file_get_contents(resource_path('js/Components/Public/JobCard.tsx'));
        $detailSource = file_get_contents(resource_path('js/Components/Public/JobDetail.tsx'));
        $this->assertIsString($cardSource);
        $this->assertIsString($detailSource);
        $this->assertStringContainsString('company_logo_url', $cardSource);
        $this->assertStringContainsString('Salary / CTC', $cardSource);
        $this->assertStringContainsString('Benefits / What we offer', $detailSource);
        $this->assertStringContainsString("backHref ? ''", $detailSource);
    }

    public function test_draft_job_is_not_public(): void
    {
        $job = Job::factory()->draft()->create();

        $this->get(route('jobs.show', $job))->assertNotFound();
        $this->get(route('jobs.apply', $job))->assertNotFound();
    }

    public function test_guest_job_apply_redirects_to_login(): void
    {
        $job = Job::factory()->create();

        $this->get(route('jobs.apply', $job))->assertRedirect(route('login'));
        $this->post(route('jobs.apply.store', $job))->assertRedirect(route('login'));
    }

    public function test_employer_enquiry_page_loads(): void
    {
        $this->get(route('for-employers'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('ForEmployers'));
    }

    public function test_contact_page_loads(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Contact'));
    }
}
