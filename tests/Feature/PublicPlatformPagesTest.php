<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPlatformPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_platform_pages_render(): void
    {
        $this->get(route('companies.index'))->assertOk()->assertInertia(fn ($page) => $page->component('Companies/Index'));
        $this->get(route('hire'))->assertOk()->assertInertia(fn ($page) => $page->component('Platform/Hire'));
        $this->get(route('talent-pool'))->assertOk()->assertInertia(fn ($page) => $page->component('Platform/TalentPool'));
        $this->get(route('staffing'))->assertOk()->assertInertia(fn ($page) => $page->component('Platform/Staffing'));
    }
}
