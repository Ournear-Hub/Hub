<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacementOffer extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'placement_id',
        'offered_at',
        'salary',
        'notes',
        'accepted_at',
        'declined_at',
        'decline_notes',
        'withdrawn_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'offered_at' => 'date',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Placement, $this>
     */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing('creator:id,name');

        return [
            'id' => $this->id,
            'offered_at' => $this->offered_at?->toDateString(),
            'salary' => $this->salary,
            'notes' => $this->notes,
            'accepted_at' => $this->accepted_at?->toDateTimeString(),
            'declined_at' => $this->declined_at?->toDateTimeString(),
            'decline_notes' => $this->decline_notes,
            'withdrawn_at' => $this->withdrawn_at?->toDateTimeString(),
            'created_by' => $this->creator?->name,
            'reference' => null,
            'document_url' => null,
            'reference_available' => false,
            'document_available' => false,
        ];
    }
}
