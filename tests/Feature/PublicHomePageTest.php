<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicHomePageTest extends TestCase
{
    public function test_public_home_renders_home_inertia_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->has('content')
                ->has('cms')
                ->has('featuredJobs')
                ->has('latestJobs')
                ->has('industries')
                ->has('heroSlides')
                ->has('popularSearchDiscovery'));
    }
}
