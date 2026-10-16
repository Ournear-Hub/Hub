<?php

namespace Tests\Feature;

use App\Support\CandidateAccountNav;
use Tests\TestCase;

class CandidateAccountNavigationTest extends TestCase
{
    public function test_candidate_nav_order_and_required_destinations(): void
    {
        $candidate = $this->makeCandidate();
        $items = CandidateAccountNav::items();
        $labels = array_column($items, 'label');
        $hrefs = array_column($items, 'href');

        $this->assertSame('Personal Dashboard', $labels[0]);
        $this->assertContains('Personal Profile', $labels);
        $this->assertContains('Professional Profile', $labels);
        $this->assertNotContains('Job Preferences', $labels);
        $this->assertNotContains('Professional Languages', $labels);
        $this->assertNotContains('Privacy', $labels);
        $this->assertContains('My Applications', $labels);
        $this->assertContains('Saved Jobs', $labels);
        $this->assertContains('Settings', $labels);
        $this->assertSame('Contact Us', $labels[array_key_last($labels)]);
        $this->assertSame('/account/contact', $hrefs[array_key_last($hrefs)]);

        $this->assertContains('/account/profile#personal', $hrefs);
        $this->assertNotContains('/account/profile#preferences', $hrefs);
        $this->assertNotContains('/account/profile#languages', $hrefs);
        $this->assertContains('/account/settings/account', $hrefs);
    }

    public function test_candidate_nav_never_includes_recruiter_workspace_links(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->assertNotNull($employer->employerProfile);

        $hrefs = array_column(CandidateAccountNav::items(), 'href');
        $this->assertNotContains('/account/hiring', $hrefs);
        $this->assertNotContains('/account/company', $hrefs);
    }
}
