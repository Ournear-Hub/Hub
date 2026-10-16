<?php

namespace Tests\Feature;

use App\Models\EmployerEnquiry;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicStaffingGuestSubmissionTest extends TestCase
{
    public function test_guest_can_submit_staffing_requirement_without_login_or_email_verification(): void
    {
        $this->post(route('for-employers.store'), [
            'company_name' => 'Guest Staffing Co',
            'contact_person' => 'Venkat',
            'mobile_number' => '9900112233',
            'email' => 'guest.staffing@example.test',
            'industry' => 'Construction',
            'project_location' => 'Hyderabad',
            'workforce_type' => 'Temporary',
            'number_of_people' => 12,
            'required_skills' => 'Masons',
            'training_required' => '0',
            'from_staffing' => '1',
            'period_from' => '2026-07-01',
            'period_to' => '2026-09-30',
        ])
            ->assertRedirect(route('staffing').'#request-manpower')
            ->assertSessionHas('success');

        $enquiry = EmployerEnquiry::query()->where('email', 'guest.staffing@example.test')->first();
        $this->assertNotNull($enquiry);
        $this->assertNull($enquiry->user_id);
        $this->assertSame('Guest Staffing Co', $enquiry->company_name);
    }

    public function test_submitted_staffing_requirement_appears_in_admin_enquiry_index(): void
    {
        $this->post(route('for-employers.store'), [
            'company_name' => 'Admin List Co',
            'contact_person' => 'Ops',
            'mobile_number' => '9900112244',
            'email' => 'admin.list.staffing@example.test',
            'industry' => 'Manufacturing',
            'project_location' => 'Pune',
            'workforce_type' => 'Contract',
            'number_of_people' => 5,
            'required_skills' => 'Technicians',
            'training_required' => '1',
            'from_staffing' => '1',
            'period_from' => '2026-08-01',
            'period_to' => '2026-10-31',
        ])->assertRedirect();

        $enquiry = EmployerEnquiry::query()->where('email', 'admin.list.staffing@example.test')->firstOrFail();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.employer-enquiries.index', ['search' => 'Admin List Co']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/EmployerEnquiries/Index')
                ->has('enquiries.data', 1)
                ->where('enquiries.data.0.id', $enquiry->id));
    }
}
