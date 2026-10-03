<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return MorphToMany<User, $this>
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'taggable')->withTimestamps();
    }

    /**
     * @return MorphToMany<CrmLead, $this>
     */
    public function crmLeads(): MorphToMany
    {
        return $this->morphedByMany(CrmLead::class, 'taggable')->withTimestamps();
    }

    /**
     * @return MorphToMany<Investor, $this>
     */
    public function investors(): MorphToMany
    {
        return $this->morphedByMany(Investor::class, 'taggable')->withTimestamps();
    }

    /**
     * @return MorphToMany<InvestmentOpportunity, $this>
     */
    public function investmentOpportunities(): MorphToMany
    {
        return $this->morphedByMany(InvestmentOpportunity::class, 'taggable')->withTimestamps();
    }

    /**
     * @return MorphToMany<Campaign, $this>
     */
    public function campaigns(): MorphToMany
    {
        return $this->morphedByMany(Campaign::class, 'taggable')->withTimestamps();
    }

    public static function normalizeName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    }

    public static function slugFromName(string $name): string
    {
        $slug = Str::slug($name);

        return $slug !== '' ? $slug : 'tag';
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
