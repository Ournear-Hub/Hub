<?php

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Usage: php qa-master-data-id-by-name.php <type-slug> <name>\n");
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\MasterEntityType;
use App\Models\Industry;
use App\Models\JobCategory;
use App\Models\JobTitle;

$type = MasterEntityType::tryFrom($argv[1]);
$name = $argv[2];

if ($type === null) {
    fwrite(STDERR, "Unknown type\n");
    exit(2);
}

$id = match ($type) {
    MasterEntityType::Industries => Industry::query()->where('name', $name)->value('id'),
    MasterEntityType::Categories => JobCategory::query()->where('name', $name)->value('id'),
    MasterEntityType::JobTitles => JobTitle::query()->where('name', $name)->value('id'),
    default => null,
};

if ($id === null) {
    fwrite(STDERR, "Not found\n");
    exit(3);
}

echo (string) $id."\n";
