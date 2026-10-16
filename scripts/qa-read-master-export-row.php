<?php

declare(strict_types=1);

if ($argc < 4) {
    fwrite(STDERR, "Usage: php qa-read-master-export-row.php <xlsx-path> <id> <column>\n");
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load($argv[1]);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();
$headers = array_map(static fn ($h) => strtolower(trim((string) $h)), $rows[0] ?? []);
$idIndex = array_search('id', $headers, true);
$colIndex = array_search(strtolower($argv[3]), $headers, true);
$targetId = (string) $argv[2];

if ($idIndex === false || $colIndex === false) {
    fwrite(STDERR, "Missing columns\n");
    exit(2);
}

for ($i = 1, $n = count($rows); $i < $n; $i++) {
    if ((string) ($rows[$i][$idIndex] ?? '') === $targetId) {
        echo (string) ($rows[$i][$colIndex] ?? '')."\n";
        exit(0);
    }
}

fwrite(STDERR, "Row not found\n");
exit(3);
