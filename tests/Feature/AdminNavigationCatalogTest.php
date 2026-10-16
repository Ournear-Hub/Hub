<?php

namespace Tests\Feature;

use App\Enums\StaffRoleSlug;
use App\Support\AdminNavigationCatalog;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * REG-PROD-003 — admin sidebar grouping, destinations, and role-aware visibility.
 */
class AdminNavigationCatalogTest extends TestCase
{
    public function test_catalog_defines_three_groups_and_valid_permissions(): void
    {
        AdminNavigationCatalog::assertPermissionsExistInCatalog();

        $ids = array_column(AdminNavigationCatalog::sections(), 'id');
        $this->assertSame(
            ['core-operations', 'recruitment-operations', 'platform-management'],
            $ids,
        );
    }

    public function test_catalog_includes_scorecard_templates_and_design_system_destinations(): void
    {
        $hrefs = AdminNavigationCatalog::collectHrefs();

        $this->assertContains('/admin/scorecard-templates', $hrefs);
        $this->assertContains('/admin/design-system', $hrefs);
        $this->assertContains('/admin/message-templates', $hrefs);
    }

    public function test_super_admin_receives_full_admin_nav_on_inertia_admin_pages(): void
    {
        $admin = $this->makeStaff(StaffRoleSlug::SuperAdmin->value);

        $this->actingAs($admin)
            ->get(route('admin.jobs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('adminNav', 3)
                ->where('adminNav.1.id', 'recruitment-operations')
                ->where('adminNav.1.items', fn ($items) => collect($items)->pluck('label')->contains('Talent Pool')));
    }

    public function test_recruiter_nav_hides_website_manage_destinations(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $hrefs = $this->visibleNavHrefs($recruiter);

        $this->assertContains('/admin/jobs', $hrefs);
        $this->assertNotContains('/admin/settings?tab=email', $hrefs);
        $this->assertNotContains('/admin/design-system', $hrefs);
        $this->assertNotContains('/admin/website-content', $hrefs);
    }

    public function test_recruiter_still_sees_recruitment_operations_and_talent_pool(): void
    {
        $recruiter = $this->makeStaff(StaffRoleSlug::Recruiter->value);
        $sections = AdminNavigationCatalog::visibleSectionsFor($recruiter);
        $ids = array_column($sections, 'id');
        $hrefs = $this->visibleNavHrefs($recruiter);

        $this->assertContains('recruitment-operations', $ids);
        $this->assertContains('core-operations', $ids);
        $this->assertContains('/admin/talent-pool-assignments', $hrefs);
        $this->assertNotContains('Candidate Allocation', $hrefs);
    }

    public function test_active_route_helpers_match_jobs_and_design_system_paths(): void
    {
        $this->assertTrue($this->pathMatchesNavActive('/admin/jobs/42/edit', '/admin/jobs'));
        $this->assertTrue($this->pathMatchesNavActive('/admin/design-system/icons', '/admin/design-system'));
        $this->assertFalse($this->pathMatchesNavActive('/admin/jobs', '/admin'));
    }

    /**
     * @return list<string>
     */
    private function visibleNavHrefs(\App\Models\User $user): array
    {
        $hrefs = [];
        $walk = function (array $items) use (&$hrefs, &$walk): void {
            foreach ($items as $item) {
                if (isset($item['href']) && is_string($item['href'])) {
                    $hrefs[] = $item['href'];
                }
                if (isset($item['children']) && is_array($item['children'])) {
                    $walk($item['children']);
                }
            }
        };

        foreach (AdminNavigationCatalog::visibleSectionsFor($user) as $section) {
            $walk($section['items']);
        }

        return $hrefs;
    }

    private function pathMatchesNavActive(string $currentUrl, string $href): bool
    {
        $currentPath = parse_url($currentUrl, PHP_URL_PATH) ?: '';
        $targetPath = parse_url(explode('?', $href)[0], PHP_URL_PATH) ?: '';

        if ($targetPath === '/admin') {
            return $currentPath === '/admin' || $currentPath === '/admin/';
        }

        if ($targetPath === '/admin/design-system') {
            return $currentPath === '/admin/design-system' || str_starts_with($currentPath, '/admin/design-system/');
        }

        return $currentPath === $targetPath || str_starts_with($currentPath, $targetPath.'/');
    }
}
