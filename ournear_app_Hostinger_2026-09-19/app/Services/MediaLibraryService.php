<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaLibraryService
{
    public const DISK = 'public';

    public const MAX_KILOBYTES = 5120;

    public const MAX_WIDTH = 4096;

    public const MAX_HEIGHT = 4096;

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

    public function store(UploadedFile $file): MediaAsset
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs('media', $filename, self::DISK);

        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([
                'image' => 'The image could not be stored.',
            ]);
        }

        $size = Storage::disk(self::DISK)->size($path) ?: $file->getSize();
        $dimensions = @getimagesizefromstring((string) Storage::disk(self::DISK)->get($path));

        return MediaAsset::query()->create([
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => (string) $file->getMimeType(),
            'extension' => $extension,
            'size_bytes' => (int) $size,
            'width' => is_array($dimensions) ? ($dimensions[0] ?? null) : null,
            'height' => is_array($dimensions) ? ($dimensions[1] ?? null) : null,
        ]);
    }

    public function delete(MediaAsset $media): void
    {
        if ($media->isInUse()) {
            throw ValidationException::withMessages([
                'media' => 'This image is assigned in Website Content and cannot be deleted until it is unassigned.',
            ]);
        }

        if ($media->fileExists()) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();
    }
}
