<?php

namespace App\Models;

use App\Models\Concerns\HasMasterRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Qualification extends Model
{
    use HasMasterRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<CandidateProfile, $this>
     */
    public function candidateProfiles(): HasMany
    {
        return $this->hasMany(CandidateProfile::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function isSelectable(): bool
    {
        return $this->is_active;
    }

    public static function displayName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name) ?? '');
    }

    public static function findOrCreateFromName(string $name): self
    {
        $display = static::displayName($name);
        if ($display === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'qualification' => 'Enter a qualification name.',
            ]);
        }

        $existing = static::findByName($display);
        if ($existing instanceof self) {
            return $existing;
        }

        $maxSort = (int) static::query()->max('sort_order');

        return static::query()->create([
            'name' => $display,
            'is_active' => true,
            'sort_order' => $maxSort + 1,
        ]);
    }
}
