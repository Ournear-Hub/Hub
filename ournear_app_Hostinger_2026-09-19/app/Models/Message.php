<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
    ];

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $this->loadMissing('sender:id,name');

        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->sender?->name,
            'body' => $this->body,
            'created_at' => $this->created_at?->toDateTimeString(),
            'attachments_available' => false,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ConversationParticipant>|iterable<int, ConversationParticipant>  $participants
     * @return array<string, mixed>
     */
    public function toAdminTimelineArray(iterable $participants): array
    {
        $payload = $this->toAdminArray();
        $others = collect($participants)->filter(
            fn (ConversationParticipant $participant) => (int) $participant->user_id !== (int) $this->sender_id,
        );
        $payload['read_state'] = $others->isNotEmpty() && $others->every(
            fn (ConversationParticipant $participant) => $participant->last_read_message_id !== null
                && (int) $participant->last_read_message_id >= (int) $this->id,
        ) ? 'Read by recipients' : 'Unread by recipients';

        return $payload;
    }
}
