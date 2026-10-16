<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php qa-read-xlsx-headers.php <path.xlsx>\n");
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1];
if (! is_file($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(2);
}

$rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
$headers = $rows[0] ?? [];
echo json_encode(['headers' => $headers, 'row_count' => count($rows)], JSON_THROW_ON_ERROR);
