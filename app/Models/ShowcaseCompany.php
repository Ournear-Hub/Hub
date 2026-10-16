<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShowcaseCompany extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'employer_profile_id',
        'media_asset_id',
        'is_active',
        'sort_order',
        'show_on_home',
        'show_on_talent_pool',
        'show_on_staffing',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'show_on_home' => 'boolean',
            'show_on_talent_pool' => 'boolean',
            'show_on_staffing' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<MediaAsset, $this>
     */
    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    /**
     * Optional link to the canonical employer company. Placement flags stay on this row.
     *
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employerProfile(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing('mediaAsset');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'employer_profile_id' => $this->employer_profile_id,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'show_on_home' => $this->show_on_home,
            'show_on_talent_pool' => $this->show_on_talent_pool,
            'show_on_staffing' => $this->show_on_staffing,
            'media_asset_id' => $this->media_asset_id,
            'logo_url' => $this->mediaAsset?->url(),
            'logo_filename' => $this->mediaAsset?->original_name,
        ];
    }

    /**
     * @return array{name: string, logo_url: string|null}
     */
    public function toPublicArray(): array
    {
        $this->loadMissing('mediaAsset');

        return [
            'name' => $this->name,
            'logo_url' => $this->mediaAsset?->url(),
        ];
    }
}
