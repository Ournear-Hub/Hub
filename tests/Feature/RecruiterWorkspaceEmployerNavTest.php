<?php

namespace Tests\Feature;

use App\Enums\EmployerStaffingAccessStatus;
use App\Models\EmployerEnquiry;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RecruiterWorkspaceEmployerNavTest extends TestCase
{
    public function test_dual_role_user_gets_employer_nav_on_recruitment_support(): void
    {
        $user = $this->makeEmployer(['email' => 'dual-recruitment-nav@example.test']);
        $user->forceFill(['role' => 'candidate'])->save();

        $this->actingAs($user)
            ->get(route('account.recruitment-support.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/RecruitmentSupport/RequirementForm')
                ->has('employerNav'));
    }

    public function test_staffing_request_route_redirects_to_enquiries(): void
    {
        $employer = $this->makeEmployer();

        $this->actingAs($employer)
            ->get('/account/staffing/request')
            ->assertRedirect('/account/enquiries');
    }

    public function test_dual_role_user_can_open_assigned_executive_index(): void
    {
        $user = $this->makeEmployer(['email' => 'dual-talent-pool@example.test']);
        $user->forceFill(['role' => 'candidate'])->save();

        $this->actingAs($user)
            ->get(route('account.talent-pool.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/TalentPool/Index')
                ->has('assignments.data'));
    }

    public function test_staffing_page_includes_inline_form_props(): void
    {
        $employer = $this->makeEmployer();

        $this->actingAs($employer)
            ->get(route('account.enquiries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Enquiries')
                ->has('staffing_form.industries')
                ->has('staffing_form.defaults')
                ->missing('support_href'));
    }

    public function test_employer_can_submit_staffing_requirement_from_enquiries_page(): void
    {
        $employer = $this->makeEmployer(['email' => 'staffing-form@example.test']);
        $employer->employerProfile?->update([
            'staffing_access_status' => EmployerStaffingAccessStatus::Approved,
        ]);

        $this->actingAs($employer)
            ->post(route('account.enquiries.store'), [
                'company_name' => 'Form Test Co',
                'contact_person' => 'Venkat',
                'mobile_number' => '9900112200',
                'email' => 'staffing-form@example.test',
                'industry' => 'Construction',
                'project_location' => 'Site C',
                'workforce_type' => 'Temporary',
                'number_of_people' => 4,
                'required_skills' => 'Operators',
                'training_required' => '0',
                'period_from' => '2026-07-01',
                'period_to' => '2026-09-30',
            ])
            ->assertRedirect(route('account.enquiries.index'))
            ->assertSessionHas('success');

        $this->assertNotNull(
            EmployerEnquiry::query()->where('email', 'staffing-form@example.test')->first()
        );

        $this->actingAs($employer)
            ->get(route('account.enquiries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('enquiries.data'));
    }
}
