<?php

namespace Tests\Feature\Api\V1;

use App\Models\WebsiteSetting;
use Tests\TestCase;

class PublicSiteBrandingApiTest extends TestCase
{
    public function test_public_branding_reflects_website_settings(): void
    {
        WebsiteSetting::query()->updateOrCreate(
            ['lock_key' => 'default'],
            [
                'company_name' => 'OurNear QA',
                'website_name' => 'OurNear Hire QA',
                'logo_path' => null,
                'icon_path' => null,
            ],
        );

        $response = $this->getJson(route('api.v1.site.branding'))
            ->assertOk()
            ->assertJsonPath('data.app_name', 'OurNear Hire QA');

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=300', $cacheControl);
    }
}
