<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContentItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'group_key',
        'type',
        'title',
        'description',
        'icon',
        'image_path',
        'media_id',
        'bullets',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bullets' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    /**
     * @return BelongsToMany<WebsiteSection, $this>
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(WebsiteSection::class, 'page_section_items', 'content_item_id', 'section_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'image_path' => $this->image_path,
            'bullets' => $this->bullets ?? [],
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
