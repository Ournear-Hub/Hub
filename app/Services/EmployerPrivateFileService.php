<?php

namespace App\Services;

use App\Support\EmployerFileRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployerPrivateFileService
{
    public const DISK = 'employer_files';

    /**
     * @param  list<string>  $allowedExtensions
     * @return array{disk: string, path: string, mime_type: string|null, extension: string, file_size: int, original_filename: string}
     */
    public function store(UploadedFile $file, string $directory, array $allowedExtensions): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file type is not allowed.',
            ]);
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $path = trim($directory, '/').'/'.$filename;
        $stored = $file->storeAs($directory, $filename, self::DISK);

        if ($stored === false) {
            throw ValidationException::withMessages([
                'file' => 'The file could not be stored. Please try again.',
            ]);
        }

        return [
            'disk' => self::DISK,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'file_size' => (int) $file->getSize(),
            'original_filename' => $this->safeOriginalName($file, $extension),
        ];
    }

    public function delete(?string $path, string $disk = self::DISK): void
    {
        if (! is_string($path) || $path === '' || $disk !== self::DISK) {
            return;
        }

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    public function download(string $path, string $downloadName, bool $inline = false, string $disk = self::DISK): StreamedResponse
    {
        if ($disk !== self::DISK || ! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($inline) {
            return Storage::disk($disk)->response($path, $downloadName, $headers);
        }

        return Storage::disk($disk)->download($path, $downloadName, $headers);
    }

    /**
     * @return list<string>
     */
    public function documentExtensions(): array
    {
        return EmployerFileRules::documentExtensions();
    }

    private function safeOriginalName(UploadedFile $file, string $extension): string
    {
        $name = basename((string) $file->getClientOriginalName());
        $name = preg_replace('/[^\w.\- ()]+/', '', $name) ?: ('file.'.$extension);

        return Str::limit($name, 180, '');
    }
}
