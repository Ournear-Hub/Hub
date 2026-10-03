<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsitePage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'seo_title',
        'meta_description',
    ];

    /**
     * @return HasMany<WebsiteSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(WebsiteSection::class, 'page_id')->orderBy('sort_order');
    }
}
