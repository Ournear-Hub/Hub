<?php

namespace App\Models;

use App\Enums\ScreeningDecision;
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
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => ScreeningDecision::class,
            'screened_at' => 'datetime',
            'shortlisted_at' => 'datetime',
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
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing(['rejectionReason', 'screener:id,name', 'shortlister:id,name']);

        return [
            'decision' => $this->decision->value,
            'decision_label' => $this->decision->label(),
            'notes' => $this->notes,
            'rejection_reason_id' => $this->rejection_reason_id,
            'rejection_reason' => $this->rejectionReason?->name,
            'screened_by' => $this->screener?->name,
            'screened_at' => $this->screened_at?->toDateTimeString(),
            'shortlisted_by' => $this->shortlister?->name,
            'shortlisted_at' => $this->shortlisted_at?->toDateTimeString(),
        ];
    }
}
