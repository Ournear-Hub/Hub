<?php

namespace App\Models;

use App\Enums\ApplicationCorrectionRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationCorrectionRequest extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'candidate_user_id',
        'body',
        'status',
        'handled_by_user_id',
        'handled_at',
        'response_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationCorrectionRequestStatus::class,
            'handled_at' => 'datetime',
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
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAccountArray(): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'response_note' => $this->response_note,
            'created_at' => $this->created_at?->toDateTimeString(),
            'handled_at' => $this->handled_at?->toDateTimeString(),
        ];
    }
}
