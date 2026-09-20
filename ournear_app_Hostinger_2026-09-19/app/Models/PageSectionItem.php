<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSectionItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'section_id',
        'content_item_id',
        'sort_order',
    ];

    /**
     * @return BelongsTo<WebsiteSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(WebsiteSection::class, 'section_id');
    }

    /**
     * @return BelongsTo<ContentItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }
}
