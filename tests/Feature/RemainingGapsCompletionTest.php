<?php

namespace Tests\Feature;

use App\Models\CandidateProfile;
use App\Services\PublicSocialSearchService;
use App\Support\CandidatePublicShare;
use Tests\TestCase;

class RemainingGapsCompletionTest extends TestCase
{
    public function test_public_search_includes_share_enabled_candidate_profiles_only(): void
    {
        $visible = $this->makeCandidate([
            'name' => 'Zelda PublicSearch',
            'username' => 'zelda-public-search',
            'is_active' => true,
        ]);
        $visible->candidateProfile?->update([
            'public_share' => array_merge(CandidatePublicShare::defaults(), ['enabled' => true]),
        ]);

        $hidden = $this->makeCandidate([
            'name' => 'Zelda HiddenSearch',
            'username' => 'zelda-hidden-search',
            'is_active' => true,
        ]);
        $hidden->candidateProfile?->update([
            'public_share' => array_merge(CandidatePublicShare::defaults(), ['enabled' => false]),
        ]);

        $results = app(PublicSocialSearchService::class)->search('Zelda PublicSearch');

        $names = collect($results['profiles'])->pluck('name')->all();
        $this->assertContains('Zelda PublicSearch', $names);
        $this->assertNotContains('Zelda HiddenSearch', $names);
    }

    public function test_hiring_dashboard_includes_contextual_search_component(): void
    {
        $employer = $this->makeEmployer([
            'email_verified_at' => now(),
            'mobile_verified_at' => now(),
        ]);

        $source = file_get_contents(base_path('resources/js/Components/Account/RecruiterWorkspaceHeader.tsx'));
        $this->assertIsString($source);
        $this->assertStringContainsString('DashboardContextualSearch', $source);
        $this->assertStringContainsString('scope="employer"', $source);

        $this->actingAs($employer)
            ->getJson(route('account.contextual-search', ['q' => 'QA', 'scope' => 'employer']))
            ->assertOk()
            ->assertJsonStructure(['scope', 'jobs', 'applicants']);
    }

    public function test_global_search_does_not_surface_admin_records(): void
    {
        $admin = $this->makeStaff(\App\Enums\StaffRoleSlug::SuperAdmin->value, [
            'name' => 'UniqueAdminSearchName',
            'username' => 'unique-admin-search',
        ]);

        $results = app(PublicSocialSearchService::class)->search('UniqueAdminSearchName');

        $this->assertSame([], $results['profiles']);
        $this->assertSame([], $results['jobs']);
        $this->assertSame([], $results['companies']);
        $this->assertNotNull($admin->id);
    }
}
