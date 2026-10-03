<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSegment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_navigation',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_navigation' => 'boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'is_navigation' => (bool) $this->is_navigation,
        ];
    }
}
