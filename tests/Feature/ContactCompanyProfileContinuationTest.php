<?php

namespace Tests\Feature;

use App\Models\ContactEnquiry;
use App\Models\Industry;
use App\Models\Location;
use App\Support\EmployerAccountNav;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactCompanyProfileContinuationTest extends TestCase
{
    public function test_public_contact_page_has_no_submission_form_props(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contact')
                ->missing('interestOptions'));
    }

    public function test_public_contact_submission_route_is_disabled(): void
    {
        $this->post(route('contact.store'), [
            'full_name' => 'Blocked User',
            'mobile_number' => '9900112233',
            'email' => 'blocked@example.test',
            'interested_in' => 'General enquiry',
            'message' => 'Should not persist.',
        ])->assertNotFound();

        $this->assertDatabaseMissing('contact_enquiries', [
            'email' => 'blocked@example.test',
        ]);
    }

    public function test_employer_contact_page_renders_form(): void
    {
        $employer = $this->eligibleEmployer();

        $this->actingAs($employer)
            ->get(route('account.contact'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Contact')
                ->has('interestOptions')
                ->has('defaults'));
    }

    public function test_employer_contact_enquiry_persists_for_admin_workflow(): void
    {
        $employer = $this->eligibleEmployer();

        $this->actingAs($employer)
            ->from(route('account.contact'))
            ->post(route('account.contact.store'), [
                'full_name' => 'Employer Contact',
                'mobile_number' => '9900112244',
                'email' => 'employer.contact@example.test',
                'interested_in' => 'General enquiry',
                'message' => 'Need help with job posting.',
            ])
            ->assertRedirect(route('account.contact'))
            ->assertSessionHas('success');

        $enquiry = ContactEnquiry::query()->where('email', 'employer.contact@example.test')->first();
        $this->assertNotNull($enquiry);
        $this->assertStringContainsString('Account ID:', $enquiry->message);
        $this->assertStringContainsString('Need help with job posting.', $enquiry->message);
    }

    public function test_company_profile_update_does_not_overwrite_personal_identity(): void
    {
        $employer = $this->eligibleEmployer();
        $originalName = $employer->name;
        $originalUsername = $employer->username;
        $location = $this->location();
        $industry = Industry::query()->get()->first(fn (Industry $row) => $row->isSelectable());
        $this->assertNotNull($industry);

        $this->actingAs($employer)
            ->post(route('account.profile.update'), [
                '_company_profile' => '1',
                'company_name' => 'Persistence Co',
                'contact_person' => 'Ops Lead',
                'mobile_number' => '0712345678',
                'industry_id' => $industry->id,
                'about_company' => 'About persistence',
                'company_size' => '11-50',
                'company_website' => 'persistence.example',
                'company_details' => 'More details',
                'location_ids' => [$location->id],
                'benefits' => ['Flexible hours'],
                'socials' => ['linkedin' => 'https://www.linkedin.com/company/persistence'],
            ])
            ->assertRedirect();

        $employer->refresh();
        $profile = $employer->employerProfile;
        $this->assertNotNull($profile);
        $this->assertSame($originalName, $employer->name);
        $this->assertSame($originalUsername, $employer->username);
        $this->assertSame('Persistence Co', $profile->company_name);
        $this->assertSame('https://persistence.example', $profile->company_website);
        $this->assertTrue($profile->locations->contains('id', $location->id));

        $this->actingAs($employer)
            ->get(route('account.company'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.company_name', 'Persistence Co')
                ->where('profile.about_company', 'About persistence')
                ->where('profile.company_website', 'https://persistence.example'));
    }

    public function test_employer_nav_contact_us_points_to_account_contact(): void
    {
        $employer = $this->eligibleEmployer();
        $nav = EmployerAccountNav::items();
        $contact = collect($nav)->firstWhere('label', 'Contact Us');
        $this->assertNotNull($contact);
        $this->assertSame('/account/contact', $contact['href']);
    }

    public function test_recruiter_company_page_exposes_company_logo_separate_from_personal_photo(): void
    {
        $employer = $this->eligibleEmployer();
        $employer->employerProfile?->update(['company_name' => 'Logo Test Co']);

        $this->actingAs($employer)
            ->get(route('account.company'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('profile.name', $employer->name)
                ->where('profile.company_name', 'Logo Test Co')
                ->where('profile.photo_url', $employer->photoUrl())
                ->where('profile.logo_url', $employer->employerProfile?->logoUrl()));
    }

    private function eligibleEmployer()
    {
        return $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);
    }

    private function location(): Location
    {
        return Location::query()->where('is_active', true)->first()
            ?? Location::query()->create(['name' => 'Contact Test City', 'is_active' => true, 'sort_order' => 99]);
    }
}
