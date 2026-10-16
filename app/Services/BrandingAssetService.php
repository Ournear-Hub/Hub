<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BrandingAssetService
{
    public const DISK = 'public';

    public const DIRECTORY = 'branding';

    public const MAX_KILOBYTES = 5120;

    public const AUTH_SPLASH_FILENAME = 'auth-splash.mp4';

    public const AUTH_SPLASH_MAX_KILOBYTES = 51200;

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

    public function copyFromMediaAsset(MediaAsset $asset, string $field): string
    {
        $mime = (string) ($asset->mime_type ?? '');
        $extension = strtolower((string) ($asset->extension ?: ''));

        if (! str_starts_with($mime, 'image/') || ! in_array($extension, self::allowedExtensions(), true)) {
            throw ValidationException::withMessages([
                $field => 'The selected media file must be a JPG, PNG, or WEBP image.',
            ]);
        }

        if (! in_array($mime, self::allowedMimes(), true)) {
            throw ValidationException::withMessages([
                $field => 'The selected media file must be a JPG, PNG, or WEBP image.',
            ]);
        }

        if (! $asset->fileExists()) {
            throw ValidationException::withMessages([
                $field => 'The selected media file is missing from storage.',
            ]);
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $destination = self::DIRECTORY.'/'.$filename;
        $sourceDisk = Storage::disk($asset->disk);
        $targetDisk = Storage::disk(self::DISK);

        if (! $targetDisk->put($destination, $sourceDisk->get($asset->path))) {
            throw ValidationException::withMessages([
                $field => 'The image could not be copied from the media library.',
            ]);
        }

        return $destination;
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

    public function authSplashStoragePath(): string
    {
        return self::DIRECTORY.'/'.self::AUTH_SPLASH_FILENAME;
    }

    public function authSplashPublicUrl(): ?string
    {
        return $this->publicUrl($this->authSplashStoragePath());
    }

    public function storeAuthSplashVideo(UploadedFile $file): void
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: ''));
        $mime = (string) $file->getMimeType();
        $allowedMimes = ['video/mp4', 'application/octet-stream'];
        $allowedExtensions = ['mp4'];

        if (
            ! in_array($extension, $allowedExtensions, true)
            || (! in_array($mime, $allowedMimes, true) && ! str_starts_with($mime, 'video/'))
        ) {
            throw ValidationException::withMessages([
                'auth_splash' => 'The splash video must be an MP4 file.',
            ]);
        }

        if ($file->getSize() > self::AUTH_SPLASH_MAX_KILOBYTES * 1024) {
            throw ValidationException::withMessages([
                'auth_splash' => 'The splash video must not be larger than 50 MB.',
            ]);
        }

        $destination = $this->authSplashStoragePath();
        $disk = Storage::disk(self::DISK);
        $backup = self::DIRECTORY.'/auth-splash-backup.mp4';
        $tempName = 'auth-splash-upload-'.Str::uuid()->toString().'.mp4';

        $stored = $file->storeAs(self::DIRECTORY, $tempName, self::DISK);
        if (! is_string($stored) || $stored === '') {
            throw ValidationException::withMessages([
                'auth_splash' => 'The splash video could not be stored. Please try again.',
            ]);
        }

        if ($disk->exists($destination)) {
            $disk->copy($destination, $backup);
        }

        try {
            if ($disk->exists($destination)) {
                $disk->delete($destination);
            }
            $disk->move($stored, $destination);
        } catch (\Throwable $exception) {
            if ($disk->exists($backup)) {
                $disk->move($backup, $destination);
            }
            if ($disk->exists($stored)) {
                $disk->delete($stored);
            }

            throw $exception;
        }

        if ($disk->exists($backup)) {
            $disk->delete($backup);
        }
    }

    public function removeAuthSplashVideo(): void
    {
        $path = $this->authSplashStoragePath();
        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
