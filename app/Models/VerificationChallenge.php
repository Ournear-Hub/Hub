<?php

namespace App\Models;

use App\Enums\VerificationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationChallenge extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'channel',
        'destination',
        'code_hash',
        'expires_at',
        'attempts',
        'max_attempts',
        'used_at',
        'requested_ip',
        'verified_ip',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'code_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => VerificationChannel::class,
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function hasExhaustedAttempts(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    public function isActive(): bool
    {
        return ! $this->isUsed() && ! $this->isExpired() && ! $this->hasExhaustedAttempts();
    }
}
