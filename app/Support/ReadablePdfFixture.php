<?php

namespace App\Support;

/**
 * Minimal valid single-page PDF for tests and local QA uploads (not a controller stub).
 */
final class ReadablePdfFixture
{
    public static function onePageWithContact(string $email = 'import.candidate@example.com', string $name = 'Import Candidate'): string
    {
        $line = sprintf('(%s %s) Tj', $name, $email);

        return str_replace(
            '(QA readable resume) Tj',
            $line,
            self::minimalOnePage(),
        );
    }

    public static function minimalOnePage(): string
    {
        return <<<'PDF'
%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>
endobj
4 0 obj
<< /Length 44 >>
stream
BT /F1 12 Tf 72 720 Td (QA readable resume) Tj ET
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000266 00000 n 
0000000360 00000 n 
trailer
<< /Size 6 /Root 1 0 R >>
startxref
427
%%EOF
PDF;
    }

    /**
     * @return array{byte_length: int, starts_with_pdf: bool, page_type_markers: int, contains_eof: bool, structurally_valid: bool}
     */
    public static function analyze(string $bytes): array
    {
        $len = strlen($bytes);
        $pageMarkers = preg_match_all('/\/Type\s*\/Page\b/', $bytes) ?: 0;

        return [
            'byte_length' => $len,
            'starts_with_pdf' => str_starts_with($bytes, '%PDF'),
            'page_type_markers' => $pageMarkers,
            'contains_eof' => str_contains($bytes, '%%EOF'),
            'structurally_valid' => str_starts_with($bytes, '%PDF') && $pageMarkers >= 1 && str_contains($bytes, '%%EOF'),
        ];
    }
}
