<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\PublicPageImage;
use App\Support\PublicPageImageRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicPageImageService
{
    public function __construct(private MediaLibraryService $media) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function adminRows(): array
    {
        $saved = PublicPageImage::query()->with('media')->get()->keyBy(
            fn (PublicPageImage $image) => $image->page_slug.'|'.$image->slot_key,
        );

        $rows = [];
        foreach (PublicPageImageRegistry::slots() as $slot) {
            $record = $saved->get($slot['page'].'|'.$slot['key']);
            $required = in_array($slot['page'], PublicPageImageRegistry::REQUIRED_PAGES, true);
            if ($record === null && ! $required) {
                continue;
            }

            $rows[] = $this->adminRow($slot, $record);
        }

        return $rows;
    }

    /**
     * @return array<string, array{url: string, alt: string}>
     */
    public function activeForPage(string $page): array
    {
        $images = [];

        $records = PublicPageImage::query()
            ->with('media')
            ->where('page_slug', $page)
            ->where('is_active', true)
            ->get();

        foreach ($records as $record) {
            $url = $record->media?->url();
            if (! is_string($url) || $url === '') {
                continue;
            }

            $images[$record->slot_key] = [
                'url' => $url,
                'alt' => $record->alt_text,
            ];
        }

        return $images;
    }

    public function store(string $page, string $slot, string $title, string $alt, bool $active, UploadedFile $file): PublicPageImage
    {
        if (PublicPageImage::query()->where('page_slug', $page)->where('slot_key', $slot)->exists()) {
            throw ValidationException::withMessages([
                'slot_key' => 'This section already has an image. Open it from the list to replace it.',
            ]);
        }

        $asset = $this->media->store($file);

        try {
            return PublicPageImage::query()->create([
                'page_slug' => $page,
                'slot_key' => $slot,
                'title' => $title,
                'alt_text' => $alt,
                'is_active' => $active,
                'media_id' => $asset->id,
            ]);
        } catch (\Throwable $exception) {
            $this->media->delete($asset);
            throw $exception;
        }
    }

    public function update(PublicPageImage $image, string $title, string $alt, bool $active, ?UploadedFile $file): PublicPageImage
    {
        $previous = $file !== null ? $image->media : null;
        $replacement = $file !== null ? $this->media->store($file) : null;

        try {
            DB::transaction(function () use ($image, $title, $alt, $active, $replacement): void {
                $image->fill([
                    'title' => $title,
                    'alt_text' => $alt,
                    'is_active' => $active,
                ]);
                if ($replacement !== null) {
                    $image->media_id = $replacement->id;
                }
                $image->save();
            });
        } catch (\Throwable $exception) {
            if ($replacement !== null) {
                $this->media->delete($replacement);
            }
            throw $exception;
        }

        if ($previous instanceof MediaAsset && ! $previous->isInUse()) {
            $this->media->delete($previous);
        }

        return $image->refresh();
    }

    /**
     * @param  array{page: string, page_label: string, key: string, label: string}  $slot
     * @return array<string, mixed>
     */
    private function adminRow(array $slot, ?PublicPageImage $record): array
    {
        $media = $record?->media;

        return [
            'id' => $record?->id,
            'page' => $slot['page'],
            'page_label' => $slot['page_label'],
            'slot_key' => $slot['key'],
            'slot_label' => $slot['label'],
            'title' => $record?->title,
            'alt_text' => $record?->alt_text,
            'is_active' => $record?->is_active ?? false,
            'filename' => $media?->original_name,
            'url' => $media?->url(),
            'updated_at' => $record?->updated_at?->toDayDateTimeString(),
        ];
    }
}
