<?php

namespace Tests\Feature;

use App\Models\EmployerEnquiry;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployerAccountTest extends TestCase
{
    public function test_employer_can_access_account_and_enquiries(): void
    {
        $employer = $this->makeEmployer();
        $enquiry = EmployerEnquiry::factory()->create([
            'user_id' => $employer->id,
            'company_name' => 'North Site Ltd',
            'email' => $employer->email,
        ]);

        $this->actingAs($employer)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Home')
                ->where('role', 'employer'));

        $this->actingAs($employer)
            ->get(route('account.enquiries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Enquiries')
                ->has('enquiries.data', 1)
                ->where('enquiries.data.0.id', $enquiry->id)
                ->where('enquiries.data.0.company_name', 'North Site Ltd'));
    }

    public function test_employer_does_not_see_other_employers_enquiries(): void
    {
        $employer = $this->makeEmployer();
        EmployerEnquiry::factory()->create([
            'user_id' => $this->makeEmployer()->id,
            'company_name' => 'Other Co',
        ]);

        $this->actingAs($employer)
            ->get(route('account.enquiries.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Enquiries')
                ->has('enquiries.data', 0));
    }

    public function test_candidate_cannot_access_employer_enquiries(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.enquiries.index'))
            ->assertForbidden();
    }
}
