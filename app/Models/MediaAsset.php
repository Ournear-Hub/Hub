<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\ShowcaseSchema;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'disk',
        'path',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
        'width',
        'height',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * @return HasMany<WebsiteSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(WebsiteSection::class, 'media_id');
    }

    /**
     * @return HasMany<ContentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ContentItem::class, 'media_id');
    }

    /**
     * @return HasMany<PublicPageImage, $this>
     */
    public function pageImages(): HasMany
    {
        return $this->hasMany(PublicPageImage::class, 'media_id');
    }

    /**
     * @return HasMany<ShowcaseCompany, $this>
     */
    public function showcaseCompanies(): HasMany
    {
        return $this->hasMany(ShowcaseCompany::class, 'media_asset_id');
    }

    public function fileExists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    public function url(): ?string
    {
        if (! $this->fileExists()) {
            return null;
        }

        return '/storage/'.ltrim($this->path, '/');
    }

    /**
     * @return list<string>
     */
    public function usageLabels(): array
    {
        $labels = [];

        $relations = ['sections.page', 'items'];
        if (ShowcaseSchema::isReady()) {
            $relations[] = 'showcaseCompanies';
        }
        $this->loadMissing($relations);

        foreach ($this->sections as $section) {
            $page = $section->page?->title ?? 'Website';
            $labels[] = $page.' · '.$section->label;
        }

        foreach ($this->items as $item) {
            $labels[] = 'Content item · '.$item->title;
        }

        $this->loadMissing('pageImages');
        foreach ($this->pageImages as $image) {
            $labels[] = $image->page_slug.' · '.$image->slot_key;
        }

        if (ShowcaseSchema::isReady()) {
            foreach ($this->showcaseCompanies as $company) {
                $labels[] = 'Showcase company · '.$company->name;
            }
        }

        return $labels;
    }

    public function isInUse(): bool
    {
        return $this->sections()->exists()
            || $this->items()->exists()
            || $this->pageImages()->exists()
            || (ShowcaseSchema::isReady() && $this->showcaseCompanies()->exists());
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $usage = $this->usageLabels();

        return [
            'id' => $this->id,
            'url' => $this->url(),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size_bytes' => $this->size_bytes,
            'size_label' => $this->humanSize(),
            'created_at' => $this->created_at?->toDayDateTimeString(),
            'in_use' => $usage !== [],
            'used_on' => $usage,
        ];
    }

    /**
     * @return array{id: int, url: string|null, original_name: string}
     */
    public function toPickerArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url(),
            'original_name' => $this->original_name,
        ];
    }

    private function humanSize(): string
    {
        $bytes = max(0, $this->size_bytes);
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }
}
