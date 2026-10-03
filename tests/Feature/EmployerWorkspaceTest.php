<?php

namespace Tests\Feature;

use App\Models\EmployerEnquiry;
use App\Models\Industry;
use App\Models\Job;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerWorkspaceTest extends TestCase
{
    public function test_admin_can_list_and_open_employer_workspace(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer(['email' => 'buildco@example.com']);
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update([
            'company_name' => 'BuildCo Ltd',
            'contact_person' => 'Sam Contact',
            'mobile_number' => '07123456789',
            'industry' => 'Construction',
        ]);
        $this->makeEmployer(['name' => 'Other Employer']);

        $enquiry = EmployerEnquiry::factory()->create([
            'user_id' => $employer->id,
            'company_name' => 'BuildCo Ltd',
            'email' => 'buildco@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.employers.index', ['search' => 'BuildCo']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Employers/Index')
                ->has('employers.data', 1)
                ->where('employers.data.0.company_name', 'BuildCo Ltd')
                ->where('employers.data.0.business_id', $profile->business_id));

        $this->actingAs($admin)
            ->get(route('admin.employers.index', [
                'search' => $profile->business_id,
                'employer' => $profile->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.id', $profile->id)
                ->where('selected.business_id', $profile->business_id)
                ->where('selected.email', 'buildco@example.com')
                ->has('enquiries', 1)
                ->where('enquiries.0.id', $enquiry->id));

        $this->actingAs($admin)
            ->get(route('admin.employer-enquiries.index', ['enquiry' => $enquiry->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/EmployerEnquiries/Index')
                ->where('selected.id', $enquiry->id));
    }

    public function test_admin_can_assign_employer_to_job_and_unassigned_jobs_remain_valid(): void
    {
        $admin = $this->makeAdmin();
        $employer = $this->makeEmployer();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $profile->update(['company_name' => 'North Hub Ltd', 'contact_person' => 'Alex']);

        $unassigned = Job::factory()->create(['title' => 'Unassigned Role']);
        $this->assertNull($unassigned->employer_id);

        $assigned = Job::factory()->create(['title' => 'Assigned Role']);
        $industry = Industry::findByName($assigned->industry) ?? Industry::query()->first();
        $this->assertNotNull($industry);

        $this->actingAs($admin)
            ->from(route('admin.jobs.index', ['job' => $assigned->slug, 'mode' => 'edit']))
            ->put(route('admin.jobs.update', $assigned), [
                'title' => $assigned->title,
                'project' => $assigned->project,
                'employer_id' => $profile->id,
                'salary' => $assigned->salary,
                'vacancy' => $assigned->vacancy,
                'posted_date' => $assigned->posted_date?->toDateString(),
                'closing_date' => $assigned->closing_date?->toDateString(),
                'featured' => $assigned->featured,
                'active' => $assigned->active,
                'publishing_status' => $assigned->publishing_status->value,
                'description' => $assigned->description,
                'responsibilities' => $assigned->responsibilities,
                'requirements' => $assigned->requirements,
                'what_we_offer' => $assigned->what_we_offer,
            ])
            ->assertRedirect();

        $this->assertSame($profile->id, $assigned->fresh()->employer_id);

        $this->actingAs($admin)
            ->get(route('admin.jobs.index', ['job' => $assigned->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.employer.business_id', $profile->business_id)
                ->where('selected.employer.company_name', 'North Hub Ltd'));

        $this->actingAs($admin)
            ->get(route('admin.jobs.index', ['job' => $unassigned->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selected.title', 'Unassigned Role')
                ->where('selected.employer', null));
    }

    public function test_candidate_and_employer_cannot_access_admin_employer_workspace(): void
    {
        $this->actingAs($this->makeCandidate())
            ->get(route('admin.employers.index'))
            ->assertForbidden();

        $this->actingAs($this->makeEmployer())
            ->get(route('admin.employers.index'))
            ->assertForbidden();
    }
}
