<?php

namespace Database\Seeders;

use App\Services\WebsiteContentService;
use Illuminate\Database\Seeder;

class WebsiteContentSeeder extends Seeder
{
    public function run(WebsiteContentService $content): void
    {
        $content->ensureSeeded();
    }
}
