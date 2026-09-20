<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'platform',
        'label',
        'url',
        'is_enabled',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'label' => $this->label,
            'url' => $this->url ?? '',
            'is_enabled' => $this->is_enabled,
            'sort_order' => $this->sort_order,
        ];
    }

    /**
     * @return array{label: string, href: string, platform: string}
     */
    public function toPublicArray(): array
    {
        return [
            'platform' => $this->platform,
            'label' => $this->label,
            'href' => (string) $this->url,
        ];
    }
}
