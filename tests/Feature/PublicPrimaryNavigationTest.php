<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPrimaryNavigationTest extends TestCase
{
    public function test_primary_navigation_excludes_about(): void
    {
        $source = file_get_contents(base_path('resources/js/content/navigation.ts'));

        $this->assertIsString($source);
        $this->assertStringContainsString("'Jobs'", $source);
        $this->assertStringContainsString("'Hiring'", $source);
        $this->assertStringContainsString("'Talent Pool'", $source);
        $this->assertStringContainsString("'Staffing'", $source);
        $this->assertStringNotContainsString("'About'", $source);
    }

    public function test_homepage_industry_catalogue_has_thirty_three_entries(): void
    {
        $catalogue = \App\Support\WebsiteContentDefaults::homepageIndustryCatalogue();

        $this->assertCount(33, $catalogue);
        $this->assertSame('Mining', $catalogue[0]['title']);
        $this->assertSame('Renewable Energy', $catalogue[32]['title']);
    }
}
