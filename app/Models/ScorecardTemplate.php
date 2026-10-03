<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScorecardTemplate extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'criteria',
        'is_active',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<InterviewScorecard, $this>
     */
    public function scorecards(): HasMany
    {
        return $this->hasMany(InterviewScorecard::class);
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function criteriaList(): array
    {
        $criteria = $this->criteria;

        if (! is_array($criteria)) {
            return [];
        }

        $normalized = [];
        foreach ($criteria as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $key = trim((string) ($item['key'] ?? 'criterion_'.((int) $index + 1)));
            $label = trim((string) ($item['label'] ?? $key));
            if ($key === '' || $label === '') {
                continue;
            }

            $normalized[] = [
                'key' => $key,
                'label' => $label,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'criteria' => $this->criteriaList(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
