<?php

namespace Tests\Feature;

use App\Support\AccountPortalNavigation;
use App\Support\PublicHeaderAccountNav;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountPortalNavigationTest extends TestCase
{
    public function test_candidate_without_hiring_workspace_has_no_company_chrome(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);

        $this->assertNull(AccountPortalNavigation::for($candidate));
        $this->assertNull(AccountPortalNavigation::companyWorkspaceIdentity($candidate));
        $this->assertFalse(AccountPortalNavigation::showsCompanyWorkspaceAvatar($candidate));
    }

    public function test_employer_role_company_avatar_targets_hiring_workspace(): void
    {
        $employer = $this->makeEmployer(['email_verified_at' => now(), 'mobile_verified_at' => now()]);

        $identity = AccountPortalNavigation::companyWorkspaceIdentity($employer);
        $this->assertNotNull($identity);
        $this->assertSame('/account/hiring', $identity['href']);

        $portals = AccountPortalNavigation::for($employer);
        $this->assertSame('/account/hiring', $portals['company']['href']);
    }

    public function test_candidate_with_employer_profile_sees_company_name_and_recruiter_href(): void
    {
        $candidate = $this->makeCandidate([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), [
                'company_name' => 'Portal Nav Industries',
                'company_phone' => '+919876543210',
                'company_email' => $candidate->email,
                'industry_id' => $this->industryId(),
            ])
            ->assertRedirect(route('account.hiring'));

        $candidate->refresh();
        $portals = AccountPortalNavigation::for($candidate);

        $this->assertTrue($portals['company']['has_company_profile']);
        $this->assertSame('Portal Nav Industries', $portals['company']['label']);
        $this->assertSame('/account/hiring', $portals['company']['href']);
    }

    public function test_public_home_shares_portal_navigation_when_hiring_workspace_exists(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), [
                'company_name' => 'Portal Home Co',
                'company_phone' => '+919876543213',
                'company_email' => $candidate->email,
                'industry_id' => $this->industryId(),
            ]);

        $candidate->refresh();

        $this->actingAs($candidate)
            ->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.portal_navigation.candidate.href', '/account')
                ->where('auth.user.portal_navigation.company.href', '/account/hiring')
                ->where('auth.user.portal_navigation.company.label', 'Portal Home Co'));
    }

    public function test_hiring_enabled_candidate_header_menu_labels_personal_dashboard(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);
        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), [
                'company_name' => 'Menu Test Co',
                'company_phone' => '+919876543211',
                'company_email' => $candidate->email,
                'industry_id' => $this->industryId(),
            ]);

        $candidate->refresh();
        $items = PublicHeaderAccountNav::itemsFor($candidate);

        $this->assertSame('Personal Dashboard', $items[0]['label']);
        $this->assertSame('/account', $items[0]['href']);
    }

    public function test_activated_candidate_can_open_recruiter_dashboard(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);

        $this->actingAs($candidate)
            ->post(route('account.hiring.activate'), [
                'company_name' => 'Recruiter Dash Co',
                'company_phone' => '+919876543212',
                'company_email' => $candidate->email,
                'industry_id' => $this->industryId(),
            ])
            ->assertRedirect(route('account.hiring'));

        $this->actingAs($candidate)
            ->get(route('account.hiring'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Hiring/Dashboard'));
    }

    public function test_candidate_only_cannot_open_protected_employer_job_create_without_profile(): void
    {
        $candidate = $this->makeCandidate(['email_verified_at' => now()]);

        $this->actingAs($candidate)
            ->get(route('account.employer.jobs.create'))
            ->assertRedirect(route('account.hiring', ['destination' => '/account/jobs/create']));
    }

    public function test_public_header_includes_split_personal_and_company_workspace_controls(): void
    {
        $header = file_get_contents(base_path('resources/js/Components/Public/Header.tsx'));

        $this->assertIsString($header);
        $this->assertStringContainsString('CompanyWorkspaceMenu', $header);
        $this->assertStringContainsString('PersonalAccountMenu', $header);
        $this->assertStringContainsString('AccountNotificationsBell', $header);
        $this->assertSame(1, substr_count($header, '<PersonalAccountMenu'));
        $this->assertSame(1, substr_count($header, '<CompanyWorkspaceMenu'));
        $this->assertStringNotContainsString('RecruiterWorkspaceAvatarLink', $header);

        $accountLayout = file_get_contents(base_path('resources/js/Layouts/AccountLayout.tsx'));
        $this->assertIsString($accountLayout);
        $this->assertStringNotContainsString('AccountNotificationsBell', $accountLayout);
        $this->assertStringNotContainsString('PersonalAccountMenu', $accountLayout);
        $this->assertStringNotContainsString('CompanyWorkspaceMenu', $accountLayout);

        $recruiterHeader = file_get_contents(base_path('resources/js/Components/Account/RecruiterWorkspaceHeader.tsx'));
        $this->assertIsString($recruiterHeader);
        $this->assertStringNotContainsString('PersonalAccountMenu', $recruiterHeader);
        $this->assertStringContainsString('EmployerSidebarIdentityCard', file_get_contents(base_path('resources/js/Layouts/AccountLayout.tsx')) ?: '');
    }

    public function test_employer_sidebar_nav_excludes_recruitment_partnership(): void
    {
        $labels = array_column(\App\Support\EmployerAccountNav::items(), 'label');

        $this->assertNotContains('Recruitment Partnership', $labels);
    }

    public function test_employer_sidebar_nav_hiring_pipeline_and_talent_pool_groups(): void
    {
        $items = \App\Support\EmployerAccountNav::items();
        $groups = array_column($items, 'group');
        $labels = array_column($items, 'label');

        $this->assertContains('OVERVIEW', $groups);
        $this->assertContains('HIRING ATS', $groups);
        $this->assertContains('TALENT POOL', $groups);
        $this->assertNotContains('HIRING', $groups);
        $this->assertSame('My Workspace', $labels[0]);
        $this->assertContains('Screening', $labels);
        $this->assertContains('Automation', $labels);
        $this->assertContains('In Review', $labels);
        $this->assertContains('Recruitment Support', $labels);
        $this->assertContains('Assigned Candidates', $labels);
        $this->assertContains('Staffing', $labels);
        $this->assertNotContains('Submit staffing requirement', $labels);
    }
}
