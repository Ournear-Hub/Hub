<?php

namespace App\Models;

use App\Enums\LoginEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'email',
        'event',
        'ip_address',
        'user_agent',
        'succeeded',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => LoginEvent::class,
            'succeeded' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $event = $this->event instanceof LoginEvent ? $this->event : LoginEvent::tryFrom((string) $this->event);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'email' => $this->email,
            'event' => $event?->value ?? (string) $this->event,
            'event_label' => $event?->label() ?? (string) $this->event,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'succeeded' => (bool) $this->succeeded,
            'metadata' => $this->metadata ?? [],
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}
