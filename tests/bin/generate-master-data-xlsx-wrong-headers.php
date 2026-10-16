<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php generate-master-data-xlsx-wrong-headers.php <output-path>\n");
    exit(1);
}

require __DIR__.'/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet;
$spreadsheet->getActiveSheet()->fromArray([['wrong', 'headers', 'only']], null, 'A1');
@mkdir(dirname($argv[1]), 0777, true);
(new Xlsx($spreadsheet))->save($argv[1]);
echo $argv[1]."\n";
