<?php

namespace App\Models;

use App\Enums\ConversationContextType;
use App\Support\RecruitmentWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'subject',
        'context_type',
        'context_id',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context_type' => ConversationContextType::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<ConversationParticipant, $this>
     */
    public function participantRecords(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /**
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participantRecords()->where('user_id', $user->id)->exists();
    }

    public function isUnreadFor(User $user): bool
    {
        $this->loadMissing(['latestMessage', 'participantRecords']);
        $latest = $this->latestMessage;
        if ($latest === null) {
            return false;
        }

        if ((int) $latest->sender_id === (int) $user->id) {
            return false;
        }

        $participant = $this->participantRecords->firstWhere('user_id', $user->id);
        if ($participant === null) {
            return false;
        }

        return (int) $participant->last_read_message_id !== (int) $latest->id;
    }

    public function contextUrl(): ?string
    {
        if ($this->context_type === null || $this->context_id === null) {
            return null;
        }

        if ($this->context_type === ConversationContextType::Job) {
            $job = Job::query()->find($this->context_id);

            return $job ? '/admin/jobs?job='.$job->slug : null;
        }

        return $this->context_type->url((int) $this->context_id);
    }

    public function contextLabel(): ?string
    {
        if ($this->context_type === null || $this->context_id === null) {
            return null;
        }

        return $this->context_type->value.' #'.$this->context_id;
    }

    public function unreadCountFor(User $user): int
    {
        $this->loadMissing('participantRecords');
        $participant = $this->participantRecords->firstWhere('user_id', $user->id);
        $lastRead = $participant?->last_read_message_id;

        return (int) $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->when($lastRead, fn ($query) => $query->where('id', '>', $lastRead))
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminListArray(User $viewer): array
    {
        $this->loadMissing(['latestMessage.sender:id,name', 'participants:id,name', 'participantRecords']);
        $unreadCount = $this->unreadCountFor($viewer);

        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'context_type' => $this->context_type?->value,
            'context_id' => $this->context_id,
            'context_url' => $this->contextUrl(),
            'context_label' => $this->contextLabel(),
            'chain' => RecruitmentWorkspace::conversationChain($this->context_type, $this->context_id ? (int) $this->context_id : null),
            'is_unread' => $this->isUnreadFor($viewer),
            'unread_count' => $unreadCount,
            'updated_at' => $this->latestMessage?->created_at?->toDateTimeString() ?? $this->updated_at?->toDateTimeString(),
            'latest_preview' => $this->latestMessage?->body
                ? \Illuminate\Support\Str::limit($this->latestMessage->body, 120)
                : null,
            'attachments_available' => false,
            'participants' => $this->participants
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(User $viewer): array
    {
        $this->loadMissing(['messages.sender:id,name', 'creator:id,name', 'participantRecords']);
        $payload = $this->toAdminListArray($viewer);
        $payload['created_by'] = $this->creator?->name;
        $payload['can_reply'] = $this->hasParticipant($viewer);
        $payload['viewer_last_read_at'] = $this->participantRecords
            ->firstWhere('user_id', $viewer->id)
            ?->last_read_at?->toDateTimeString();
        $payload['messages'] = $this->messages
            ->map(fn (Message $message) => $message->toAdminTimelineArray($this->participantRecords))
            ->values()
            ->all();

        return $payload;
    }
}
