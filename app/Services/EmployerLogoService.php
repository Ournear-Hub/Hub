<?php

namespace App\Services;

use App\Models\EmployerProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployerLogoService
{
    public const DISK = 'public';

    public const DIRECTORY = 'employers';

    public const MAX_KILOBYTES = 5120;

    /**
     * @return list<string>
     */
    public static function allowedMimes(): array
    {
        return UserPhotoService::allowedMimes();
    }

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return UserPhotoService::allowedExtensions();
    }

    public function store(UploadedFile $file, string $field = 'logo'): string
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: ''));
        $mime = (string) $file->getMimeType();

        if (
            ! in_array($extension, self::allowedExtensions(), true)
            || ! in_array($mime, self::allowedMimes(), true)
        ) {
            throw ValidationException::withMessages([
                $field => 'The logo must be a JPG, PNG, or WEBP image.',
            ]);
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs(self::DIRECTORY, $filename, self::DISK);

        if (! is_string($path) || $path === '' || ! str_starts_with($path, self::DIRECTORY.'/')) {
            throw ValidationException::withMessages([
                $field => 'The logo could not be stored. Please try again.',
            ]);
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if (! $this->isValidPath($path)) {
            return;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /** Public marketing surfaces (published jobs, company directory when enabled). */
    public function publicUrl(?string $path): ?string
    {
        if (! $this->isValidPath($path) || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return '/storage/'.ltrim($path, '/');
    }

    /** Served via {@see EmployerLogoController} — works without a public storage symlink. */
    public function publicListingUrl(?EmployerProfile $profile): ?string
    {
        if (! $profile instanceof EmployerProfile || ! $this->isValidPath($profile->logo_path)) {
            return null;
        }

        if (! Storage::disk(self::DISK)->exists($profile->logo_path)) {
            return null;
        }

        $version = $profile->updated_at?->timestamp ?? time();

        return route('employers.logo.show', $profile, false).'?v='.$version;
    }

    public function authorizedUrl(?EmployerProfile $profile): ?string
    {
        if (! $profile instanceof EmployerProfile || ! $this->isValidPath($profile->logo_path)) {
            return null;
        }

        if (! Storage::disk(self::DISK)->exists($profile->logo_path)) {
            return null;
        }

        $version = $profile->updated_at?->timestamp ?? time();

        return route('employers.logo.show', $profile, false).'?v='.$version;
    }

    public function response(EmployerProfile $profile): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $path = $profile->logo_path;
        abort_unless($this->isValidPath($path) && Storage::disk(self::DISK)->exists($path), 404);

        $mime = Storage::disk(self::DISK)->mimeType($path) ?: 'image/jpeg';

        return Storage::disk(self::DISK)->response($path, null, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function isValidPath(?string $path): bool
    {
        return is_string($path) && $path !== '' && str_starts_with($path, self::DIRECTORY.'/');
    }
}
