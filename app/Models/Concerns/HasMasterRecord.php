<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasMasterRecord
{
    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function findByName(string $name): ?static
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        return static::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
    }
}
