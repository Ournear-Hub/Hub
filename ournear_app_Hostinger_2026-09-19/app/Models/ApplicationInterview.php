<?php

namespace App\Models;

use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationInterview extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'scheduled_at',
        'mode',
        'location_or_link',
        'interviewer_name',
        'outcome',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'mode' => InterviewMode::class,
            'outcome' => InterviewOutcome::class,
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
        return [
            'id' => $this->id,
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d\TH:i'),
            'mode' => $this->mode->value,
            'location_or_link' => $this->location_or_link,
            'interviewer_name' => $this->interviewer_name,
            'outcome' => $this->outcome->value,
            'notes' => $this->notes,
        ];
    }
}
