<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminReleaseReadinessStage16Test extends TestCase
{
    public function test_admin_dashboard_and_hiring_command_center_load_with_real_stats(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->has('staff_name')
                ->has('stats')
                ->has('stats.pending_actions')
                ->has('recentApplications')
                ->has('recentEmployerEnquiries'));

        $this->actingAs($admin)
            ->get(route('admin.hiring.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Hiring/CommandCenter'));
    }

    public function test_account_users_cannot_access_admin_routes(): void
    {
        $candidate = $this->makeCandidate();
        $employer = $this->makeEmployer();

        $this->actingAs($candidate)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($employer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($candidate)->get(route('admin.hiring.index'))->assertForbidden();

        $this->forgetAuthentication();
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_core_admin_module_routes_resolve_for_admin(): void
    {
        $admin = $this->makeAdmin();

        foreach ([
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
