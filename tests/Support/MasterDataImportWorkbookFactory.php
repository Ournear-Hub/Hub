<?php

namespace Tests\Support;

use App\Enums\MasterEntityType;
use App\Services\MasterData\MasterDataExcelSchema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class MasterDataImportWorkbookFactory
{
    /**
     * @param  list<array<string, string>>  $rows
     */
    public static function write(MasterEntityType $type, array $rows, string $absolutePath): void
    {
        $headers = MasterDataExcelSchema::headers($type);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headers], null, 'A1');

        $lineNumber = 2;
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            $sheet->fromArray([$line], null, 'A'.$lineNumber);
            $lineNumber++;
        }

        @mkdir(dirname($absolutePath), 0777, true);
        (new Xlsx($spreadsheet))->save($absolutePath);
    }
}
