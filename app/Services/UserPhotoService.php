<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserPhotoService
{
    public const DISK = 'public';

    public const DIRECTORY = 'users';

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

    public function store(UploadedFile $file, string $field = 'photo'): string
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: ''));
        $mime = (string) $file->getMimeType();

        if (
            ! in_array($extension, self::allowedExtensions(), true)
            || ! in_array($mime, self::allowedMimes(), true)
        ) {
            throw ValidationException::withMessages([
                $field => 'The photo must be a JPG, PNG, or WEBP image.',
            ]);
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs(self::DIRECTORY, $filename, self::DISK);

        if (! is_string($path) || $path === '' || ! str_starts_with($path, self::DIRECTORY.'/')) {
            throw ValidationException::withMessages([
                $field => 'The photo could not be stored. Please try again.',
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
        return $this->authorizedUrl($path);
    }

    public function authorizedUrl(?string $path, ?\App\Models\User $user = null): ?string
    {
        if (! $this->isValidPath($path) || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        if ($user instanceof \App\Models\User && $user->exists) {
            $version = $user->updated_at?->timestamp ?? time();

            return route('users.photo.show', $user, false).'?v='.$version;
        }

        return '/storage/'.ltrim((string) $path, '/');
    }

    public function response(\App\Models\User $user): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $path = $user->photo_path;
        abort_unless($this->isValidPath($path) && Storage::disk(self::DISK)->exists($path), 404);

        $mime = Storage::disk(self::DISK)->mimeType($path) ?: 'image/jpeg';

        return Storage::disk(self::DISK)->response($path, null, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function isValidPath(?string $path): bool
    {
        return is_string($path) && $path !== '' && str_starts_with($path, self::DIRECTORY.'/');
    }
}
