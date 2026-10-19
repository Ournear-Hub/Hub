<?php

namespace App\Models;

use App\Support\DevelopmentProgramme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevelopmentProgrammeApplication extends Model
{
    protected $fillable = [
        'user_id',
        'programme',
        'motivation',
        'status',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canEdit(): bool
    {
        return $this->status === DevelopmentProgramme::StatusSubmitted;
    }

    /**
     * @return array{programme: string, programme_label: string, status: string, status_label: string, motivation: string|null, submitted_at: string|null, can_edit: bool}
     */
    public function toAccountArray(): array
    {
        return [
            'programme' => $this->programme,
            'programme_label' => DevelopmentProgramme::label($this->programme),
            'status' => $this->status,
            'status_label' => 'Submitted — awaiting review',
            'motivation' => $this->motivation,
            'submitted_at' => $this->created_at?->toIso8601String(),
            'can_edit' => $this->canEdit(),
        ];
    }
}
