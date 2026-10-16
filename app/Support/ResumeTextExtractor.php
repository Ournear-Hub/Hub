<?php

namespace App\Support;

use ZipArchive;

final class ResumeTextExtractor
{
    public function extract(string $extension, string $contents): string
    {
        $extension = strtolower($extension);

        return match ($extension) {
            'docx' => $this->fromDocx($contents),
            'pdf' => $this->fromPdf($contents),
            default => $contents,
        };
    }

    private function fromDocx(string $contents): string
    {
        $temporary = tempnam(sys_get_temp_dir(), 'docx');
        if ($temporary === false) {
            return '';
        }

        file_put_contents($temporary, $contents);
        $zip = new ZipArchive;
        $xml = '';
        if ($zip->open($temporary) === true) {
            $xml = (string) $zip->getFromName('word/document.xml');
            $zip->close();
        }
        @unlink($temporary);

        return trim(html_entity_decode(strip_tags($xml)));
    }

    private function fromPdf(string $contents): string
    {
        $parts = [];
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $contents, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = $this->decodePdfStream($stream);
                if ($decoded === '') {
                    continue;
                }
                if (preg_match_all('/\(([^()\\\\]*(?:\\\\.[^()\\\\]*)*)\)/s', $decoded, $textMatches)) {
                    foreach ($textMatches[1] as $fragment) {
                        $parts[] = stripcslashes((string) $fragment);
                    }
                }
            }
        }

        $text = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');

        return $text !== '' ? $text : $contents;
    }

    private function decodePdfStream(string $stream): string
    {
        if (str_contains($stream, 'FlateDecode')) {
            $payload = preg_replace('/^.*?(\r?\n)/s', '', $stream) ?? $stream;

            return @gzuncompress(rtrim($payload)) ?: '';
        }

        return $stream;
    }
}
