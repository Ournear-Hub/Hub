<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BrandingAssetService
{
    public const DISK = 'public';

    public const DIRECTORY = 'branding';

    public const MAX_KILOBYTES = 5120;

    /**
     * @return list<string>
     */
    public static function allowedMimes(): array
    {
        return ['image/jpeg', 'image/png', 'image/webp'];
    }

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'webp'];
    }

    public function store(UploadedFile $file, string $field): string
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: ''));
        $mime = (string) $file->getMimeType();

        if (
            ! in_array($extension, self::allowedExtensions(), true)
            || ! in_array($mime, self::allowedMimes(), true)
        ) {
            throw ValidationException::withMessages([
                $field => 'The file must be a JPG, PNG, or WEBP image.',
            ]);
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs(self::DIRECTORY, $filename, self::DISK);

        if (! is_string($path) || $path === '' || ! str_starts_with($path, self::DIRECTORY.'/')) {
            throw ValidationException::withMessages([
                $field => 'The image could not be stored. Please try again.',
            ]);
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if (! is_string($path) || $path === '' || ! str_starts_with($path, self::DIRECTORY.'/')) {
            return;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function publicUrl(?string $path): ?string
    {
        if (! is_string($path) || $path === '' || ! str_starts_with($path, self::DIRECTORY.'/')) {
            return null;
        }

        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return '/storage/'.ltrim($path, '/');
    }
}
