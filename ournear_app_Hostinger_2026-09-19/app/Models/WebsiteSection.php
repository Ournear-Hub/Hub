<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WebsiteSection extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'page_id',
        'section_key',
        'label',
        'heading',
        'subtitle',
        'body',
        'primary_button_label',
        'secondary_button_label',
        'image_path',
        'media_id',
        'extra',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extra' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<WebsitePage, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(WebsitePage::class, 'page_id');
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    /**
     * @return BelongsToMany<ContentItem, $this>
     */
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(ContentItem::class, 'page_section_items', 'section_id', 'content_item_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
