<?php

namespace App\Models;

use App\Enums\CommunicationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCommunicationPreference extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'channel',
        'opted_in',
        'consented_at',
        'source',
        'opted_out_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => CommunicationChannel::class,
            'opted_in' => 'boolean',
            'consented_at' => 'datetime',
            'opted_out_at' => 'datetime',
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
        $channel = $this->channel instanceof CommunicationChannel
            ? $this->channel
            : CommunicationChannel::tryFrom((string) $this->channel);

        return [
            'id' => $this->id,
            'channel' => $channel?->value ?? (string) $this->channel,
            'channel_label' => $channel?->label() ?? (string) $this->channel,
            'opted_in' => (bool) $this->opted_in,
            'consented_at' => optional($this->consented_at)?->toDateTimeString(),
            'source' => $this->source,
            'opted_out_at' => optional($this->opted_out_at)?->toDateTimeString(),
        ];
    }
}
