<?php

namespace App\Models;

use App\Enums\ScreeningDecision;
use App\Enums\ScreeningStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationScreening extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'decision',
        'notes',
        'rejection_reason_id',
        'screened_by',
        'screened_at',
        'shortlisted_by',
        'shortlisted_at',
        'stage',
        'held_by',
        'held_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => ScreeningDecision::class,
            'stage' => ScreeningStage::class,
            'screened_at' => 'datetime',
            'shortlisted_at' => 'datetime',
            'held_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<RejectionReason, $this>
     */
    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(RejectionReason::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function screener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'screened_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function shortlister(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shortlisted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing(['rejectionReason', 'screener:id,name', 'shortlister:id,name', 'holder:id,name']);

        return [
            'decision' => $this->decision->value,
            'decision_label' => $this->decision->label(),
            'stage' => $this->stage?->value ?? ScreeningStage::Pending->value,
            'stage_label' => $this->stage?->label() ?? ScreeningStage::Pending->label(),
            'notes' => $this->notes,
            'rejection_reason_id' => $this->rejection_reason_id,
            'rejection_reason' => $this->rejectionReason?->name,
            'screened_by' => $this->screener?->name,
            'screened_at' => $this->screened_at?->toDateTimeString(),
            'shortlisted_by' => $this->shortlister?->name,
            'shortlisted_at' => $this->shortlisted_at?->toDateTimeString(),
            'held_by' => $this->holder?->name,
            'held_at' => $this->held_at?->toDateTimeString(),
        ];
    }
}
