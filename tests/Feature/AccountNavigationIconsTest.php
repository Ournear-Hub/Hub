<?php

namespace Tests\Feature;

use App\Support\CandidateAccountNav;
use App\Support\EmployerAccountNav;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccountNavigationIconsTest extends TestCase
{
    /** @var list<string> Registry names allowed for account nav (resources/js/icons/registry.ts) */
    private const ALLOWED_ICONS = [
        'bell',
        'briefcase',
        'building',
        'check',
        'clipboard',
        'download',
        'edit',
        'eye',
        'eye-off',
        'file',
        'home',
        'info',
        'message',
        'plus',
        'search',
        'settings',
        'user',
    ];

    public function test_candidate_nav_items_include_registry_compatible_icons(): void
    {
        $candidate = $this->makeCandidate();
        $items = CandidateAccountNav::items();

        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertArrayHasKey('icon', $item);
            $this->assertIsString($item['icon']);
            $this->assertContains($item['icon'], self::ALLOWED_ICONS, $item['label']);
        }
    }

    public function test_employer_nav_items_include_registry_compatible_icons(): void
    {
        foreach (EmployerAccountNav::items() as $item) {
            $this->assertArrayHasKey('icon', $item);
            $this->assertIsString($item['icon']);
            $this->assertContains($item['icon'], self::ALLOWED_ICONS, $item['label']);
        }

        $last = EmployerAccountNav::items()[array_key_last(EmployerAccountNav::items())];
        $this->assertSame('/account/contact', $last['href']);
        $this->assertSame('Contact Us', $last['label']);
    }

    public function test_inertia_shares_candidate_nav_icons(): void
    {
        $candidate = $this->makeCandidate();

        $this->actingAs($candidate)
            ->get(route('account.home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('candidateNav')
                ->where('candidateNav.0.icon', 'home')
                ->where('candidateNav.0.label', 'Personal Dashboard'));
    }

    public function test_inertia_shares_employer_nav_icons_on_hiring_routes(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $this->actingAs($employer)
            ->get(route('account.employer.jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('employerNav')
                ->where('employerNav.0.icon', 'home')
                ->where('employerNav.0.label', 'My Workspace'));
    }
}
