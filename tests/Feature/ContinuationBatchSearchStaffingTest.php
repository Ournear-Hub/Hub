<?php

namespace Tests\Feature;

use App\Models\EmployerEnquiry;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContinuationBatchSearchStaffingTest extends TestCase
{
    public function test_public_social_search_page_renders_for_queries(): void
    {
        $this->get(route('search', ['q' => 'QA']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Search/Index')
                ->where('query', 'QA')
                ->has('results.jobs')
                ->has('results.companies')
                ->has('results.profiles'));
    }

    public function test_staffing_enquiry_rejects_end_date_before_start_date(): void
    {
        $this->from(route('staffing'))
            ->post(route('for-employers.store'), [
                'company_name' => 'Period Test Co',
                'contact_person' => 'Tester',
                'mobile_number' => '9900112299',
                'email' => 'period.test@example.test',
                'industry' => 'Construction',
                'project_location' => 'Site A',
                'workforce_type' => 'Temporary',
                'number_of_people' => 5,
                'required_skills' => 'General labour',
                'training_required' => '0',
                'from_staffing' => '1',
                'period_from' => '2026-12-01',
                'period_to' => '2026-11-01',
            ])
            ->assertSessionHasErrors('period_to');
    }

    public function test_staffing_enquiry_persists_period_in_additional_requirements(): void
    {
        $this->post(route('for-employers.store'), [
            'company_name' => 'Period Save Co',
            'contact_person' => 'Tester',
            'mobile_number' => '9900112288',
            'email' => 'period.save@example.test',
            'industry' => 'Construction',
            'project_location' => 'Site B',
            'workforce_type' => 'Temporary',
            'number_of_people' => 3,
            'required_skills' => 'Operators',
            'training_required' => '0',
            'from_staffing' => '1',
            'period_from' => '2026-06-01',
            'period_to' => '2026-08-31',
        ])->assertRedirect();

        $enquiry = EmployerEnquiry::query()->where('email', 'period.save@example.test')->first();
        $this->assertNotNull($enquiry);
        $this->assertStringContainsString('Staffing period:', (string) $enquiry->additional_requirements);
        $this->assertStringContainsString('2026-08-31', (string) $enquiry->additional_requirements);
    }

    public function test_account_contextual_search_requires_authentication(): void
    {
        $this->get(route('account.contextual-search', ['q' => 'qa']))
            ->assertRedirect();
    }

    public function test_candidate_contextual_search_returns_json(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);

        $this->actingAs($candidate)
            ->getJson(route('account.contextual-search', ['q' => 'QA', 'scope' => 'candidate']))
            ->assertOk()
            ->assertJsonStructure(['scope', 'applications', 'saved_jobs']);
    }
}
