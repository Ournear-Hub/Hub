<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'normalized_name',
    ];

    /**
     * @return BelongsToMany<CandidateProfile, $this>
     */
    public function candidateProfiles(): BelongsToMany
    {
        return $this->belongsToMany(CandidateProfile::class, 'candidate_skill')->withTimestamps();
    }

    public static function normalizeName(string $name): string
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? ''));

        return $normalized;
    }

    public static function displayName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name) ?? '');
    }

    public static function findOrCreateFromName(string $name): self
    {
        $display = static::displayName($name);
        $normalized = static::normalizeName($display);

        $existing = static::query()->where('normalized_name', $normalized)->first();
        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'name' => $display,
            'normalized_name' => $normalized,
        ]);
    }
}
