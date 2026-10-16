<?php

declare(strict_types=1);

if ($argc < 4) {
    fwrite(STDERR, "Usage: php generate-master-data-xlsx.php <type> <output-path> <json-rows>\n");
    exit(1);
}

require __DIR__.'/../../vendor/autoload.php';

use App\Enums\MasterEntityType;
use Tests\Support\MasterDataImportWorkbookFactory;

$type = MasterEntityType::tryFrom($argv[1]);
if ($type === null) {
    fwrite(STDERR, "Unknown master-data type: {$argv[1]}\n");
    exit(1);
}

/** @var list<array<string, string>> $rows */
$rows = json_decode($argv[3], true, 512, JSON_THROW_ON_ERROR);

MasterDataImportWorkbookFactory::write($type, $rows, $argv[2]);

echo $argv[2]."\n";
