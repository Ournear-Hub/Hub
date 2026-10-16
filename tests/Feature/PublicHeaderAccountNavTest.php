<?php

namespace Tests\Feature;

use App\Support\AccountPortalNavigation;
use App\Support\PublicHeaderAccountNav;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicHeaderAccountNavTest extends TestCase
{
    public function test_employer_company_header_preserves_workspace_destinations(): void
    {
        $employer = $this->makeEmployer();
        $items = PublicHeaderAccountNav::companyWorkspaceHeaderItems($employer);

        $this->assertSame('/account/hiring', $items[0]['href']);
        $this->assertSame('Company dashboard', $items[0]['label']);
        $this->assertSame('/account/jobs', $items[1]['href']);
    }

    public function test_employer_personal_header_is_separate_from_company_menu(): void
    {
        $items = PublicHeaderAccountNav::personalHeaderItems($this->makeEmployer());

        $this->assertSame(['/account/profile#personal'], array_column($items, 'href'));
    }

    public function test_employer_header_icons_reuse_employer_sidebar_metadata(): void
    {
        $employer = $this->makeEmployer();
        $items = PublicHeaderAccountNav::companyWorkspaceHeaderItems($employer);
        $byHref = collect($items)->keyBy('href');

        $this->assertSame('briefcase', $byHref['/account/jobs']['icon']);
        $this->assertSame('building', $byHref['/account/company']['icon']);
    }

    public function test_candidate_header_menu_is_curated_without_recruiter_or_profile_sidebar_duplicates(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), [
                'company_name' => 'Header Nav Co',
                'company_phone' => '+919876543299',
                'company_email' => $candidate->email,
                'industry_id' => $this->industryId(),
            ]);

        $items = PublicHeaderAccountNav::personalHeaderItems($candidate->fresh());
        $labels = array_column($items, 'label');
        $hrefs = array_column($items, 'href');

        $this->assertSame('Personal Dashboard', $labels[0]);
        $this->assertContains('Personal Profile', $labels);
        $this->assertContains('My Applications', $labels);
        $this->assertNotContains('Job Preferences', $labels);
        $this->assertNotContains('Hire Resume', $labels);
        $this->assertNotContains('Header Nav Co', $labels);
        $this->assertNotContains('/account/hiring', $hrefs);
    }

    public function test_public_home_inertia_shares_split_personal_and_company_header_nav_for_employer(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $response = $this->actingAs($employer)
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('headerAccountNav', 1)
                ->has('companyHeaderNav', 5)
                ->has('employerNav', fn (Assert $nav) => $nav->where('0.label', 'My Workspace')->etc())
                ->where('headerAccountNav.0.label', 'Personal profile')
                ->where('companyHeaderNav.0.label', 'Company dashboard'));

        $employerNav = $response->inertiaPage()['props']['employerNav'] ?? [];
        $this->assertSame('/account/hiring', $employerNav[0]['href'] ?? null);
        $this->assertGreaterThan(5, count($employerNav));
    }

    public function test_verified_candidate_without_hiring_workspace_has_no_company_header_nav(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
        ]);

        $this->assertFalse(AccountPortalNavigation::showsCompanyWorkspaceAvatar($candidate));
    }

    public function test_header_nav_is_curated_while_employer_sidebar_metadata_remains_available(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($employer)
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('headerAccountNav', 1)
                ->has('companyHeaderNav', 5)
                ->has('employerNav', fn (Assert $nav) => $nav->where('0.label', 'My Workspace')->etc()));
    }
}
