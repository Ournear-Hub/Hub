<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Industry;
use App\Models\JobCategory;

$industryId = (int) ($argv[1] ?? 8);
$industry = Industry::query()->find($industryId);
echo 'Industry: '.($industry?->name ?? 'missing').PHP_EOL;
echo JobCategory::query()
    ->where('industry_id', $industryId)
    ->orderBy('name')
    ->get(['id', 'name', 'industry_id'])
    ->toJson(JSON_PRETTY_PRINT).PHP_EOL;
