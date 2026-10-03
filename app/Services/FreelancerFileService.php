<?php

namespace App\Services;

use App\Support\CandidateFileRules;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FreelancerFileService
{
    public const DISK = 'freelancer_files';

    /**
     * @param  list<string>  $allowedExtensions
     * @return array{disk: string, path: string, mime: string|null, size: int, original_name: string}
     */
    public function store(UploadedFile $file, string $directory, array $allowedExtensions, string $errorField): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                $errorField => 'The uploaded file type is not allowed.',
            ]);
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $stored = $file->storeAs($directory, $filename, self::DISK);

        if ($stored === false) {
            throw ValidationException::withMessages([
                $errorField => 'The file could not be stored. Please try again.',
            ]);
        }

        return [
            'disk' => self::DISK,
            'path' => $stored,
            'mime' => $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'original_name' => $this->safeOriginalName($file, $extension),
        ];
    }

    public function download(?string $path, string $downloadName, string $disk = self::DISK): StreamedResponse
    {
        if (! is_string($path) || $path === '' || $disk !== self::DISK || ! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($disk)->download($path, $downloadName, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function safeOriginalName(UploadedFile $file, string $extension): string
    {
        $name = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        $safe = Str::of($name)->replaceMatches('/[^\w\s.-]/', '')->trim()->substr(0, 80)->toString();

        return ($safe !== '' ? $safe : 'document').'.'.$extension;
    }

    /**
     * @return list<string>
     */
    public function resumeExtensions(): array
    {
        return CandidateFileRules::resumeExtensions();
    }

    /**
     * @return list<string>
     */
    public function documentExtensions(): array
    {
        return CandidateFileRules::documentExtensions();
    }
}
