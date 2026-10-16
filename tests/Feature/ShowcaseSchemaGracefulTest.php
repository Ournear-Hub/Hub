<?php

namespace Tests\Feature;

use App\Support\ShowcaseSchema;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ShowcaseSchemaGracefulTest extends TestCase
{
    public function test_homepage_renders_when_showcase_tables_are_missing(): void
    {
        if (! Schema::hasTable('homepage_statistics')) {
            $this->get(route('home'))->assertOk();

            return;
        }

        Schema::drop('showcase_companies');
        Schema::drop('homepage_statistics');

        try {
            $this->assertFalse(ShowcaseSchema::isReady());
            $this->get(route('home'))->assertOk();
        } finally {
            $this->artisan('migrate', ['--force' => true]);
        }
    }

    public function test_media_social_index_renders_when_showcase_table_is_missing(): void
    {
        if (! Schema::hasTable('showcase_companies')) {
            $this->actingAs($this->makeAdmin())
                ->get(route('admin.media-social.index'))
                ->assertOk();

            return;
        }

        Schema::drop('showcase_companies');

        try {
            $this->actingAs($this->makeAdmin())
                ->get(route('admin.media-social.index'))
                ->assertOk();
        } finally {
            $this->artisan('migrate', ['--force' => true]);
        }
    }
}
