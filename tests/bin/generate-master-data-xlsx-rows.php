<?php

declare(strict_types=1);

if ($argc < 4) {
    fwrite(STDERR, "Usage: php generate-master-data-xlsx-rows.php <type> <output-path> <row-count>\n");
    exit(1);
}

require __DIR__.'/../../vendor/autoload.php';

use App\Enums\MasterEntityType;
use App\Services\MasterData\MasterDataExcelSchema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$type = MasterEntityType::tryFrom($argv[1]);
if ($type === null) {
    fwrite(STDERR, "Unknown type {$argv[1]}\n");
    exit(1);
}

$count = (int) $argv[3];
$headers = MasterDataExcelSchema::headers($type);
$spreadsheet = new Spreadsheet;
$sheet = $spreadsheet->getActiveSheet();
$sheet->fromArray([$headers], null, 'A1');

for ($i = 0; $i < $count; $i++) {
    $row = [];
    foreach ($headers as $header) {
        $row[] = match ($header) {
            'id' => '',
            'name' => "Bulk Row {$i}",
            'description' => '',
            'sort_order' => (string) $i,
            'is_active' => 'yes',
            'industry_name' => 'Mining',
            'job_category_name' => 'Bulk Category',
            'code' => '',
            default => '',
        };
    }
    $sheet->fromArray([$row], null, 'A'.($i + 2));
}

@mkdir(dirname($argv[2]), 0777, true);
(new Xlsx($spreadsheet))->save($argv[2]);
echo $argv[2]."\n";
