<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class StaffingPageDefaultMedia
{
    public const STORAGE_PATH = 'cms/defaults/staffing-hero-default.jpg';

    public const BUNDLE_FILENAME = 'staffing-hero-default.jpg';

    public static function bundlePath(): string
    {
        return resource_path('cms-defaults/'.self::BUNDLE_FILENAME);
    }

    public static function ensureDefaultHeroMediaAsset(): ?MediaAsset
    {
        $existing = MediaAsset::query()
            ->where('disk', 'public')
            ->where('path', self::STORAGE_PATH)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $bundle = self::bundlePath();
        if (! File::isFile($bundle)) {
            return null;
        }

        Storage::disk('public')->makeDirectory('cms/defaults');
        Storage::disk('public')->put(self::STORAGE_PATH, File::get($bundle));

        return MediaAsset::query()->create([
            'disk' => 'public',
            'path' => self::STORAGE_PATH,
            'original_name' => self::BUNDLE_FILENAME,
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => File::size($bundle),
        ]);
    }

    public static function defaultHeroImageUrl(): ?string
    {
        $media = self::ensureDefaultHeroMediaAsset();

        return $media?->url();
    }
}
