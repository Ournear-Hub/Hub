<?php

namespace Tests\Feature;

use Tests\TestCase;

class ResponsiveStage17Test extends TestCase
{
    /** @return array<int, string> */
    private function publicMarketingRoutes(): array
    {
        return [
            '/',
            '/jobs',
            '/companies',
            '/hire',
            '/talent-pool',
            '/staffing',
            '/about',
            '/contact',
            '/privacy-policy',
            '/terms-and-conditions',
        ];
    }

    public function test_public_marketing_pages_render_without_server_error(): void
    {
        foreach ($this->publicMarketingRoutes() as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_candidate_account_shell_pages_render(): void
    {
        $candidate = $this->makeCandidate();

        foreach ([
            'account.home',
            'account.profile.edit',
            'account.resumes.index',
            'account.settings.account',
            'account.settings.security',
            'account.settings.verification',
            'account.settings.password',
            'account.notifications.index',
            'account.applications.index',
            'account.saved-jobs.index',
        ] as $route) {
            $this->actingAs($candidate)->get(route($route))->assertOk();
        }

        $this->actingAs($candidate)
            ->get(route('account.settings.index'))
            ->assertRedirect(route('account.settings.account'));

        $this->actingAs($candidate)
            ->get(route('account.settings.communication'))
            ->assertRedirect(route('account.settings.account').'#communication');
    }

    public function test_admin_core_pages_render_for_super_admin(): void
    {
        $admin = $this->makeAdmin();

        foreach ([
            'admin.dashboard',
            'admin.hiring.index',
            'admin.jobs.index',
            'admin.applications.index',
            'admin.candidates.index',
            'admin.employers.index',
            'admin.submissions.index',
            'admin.employer-enquiries.index',
            'admin.website-content.index',
            'admin.settings.edit',
        ] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }
}
