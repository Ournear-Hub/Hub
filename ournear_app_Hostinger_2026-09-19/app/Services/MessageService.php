<?php

namespace App\Services;

use App\Enums\ConversationContextType;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Employee;
use App\Models\EmployerProfile;
use App\Models\EmployerSubmission;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Message;
use App\Models\Placement;
use App\Models\Replacement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public function start(
        User $actor,
        User $recipient,
        string $subject,
        string $body,
        ?ConversationContextType $contextType = null,
        ?int $contextId = null,
    ): Conversation {
        $this->assertRecipient($actor, $recipient);
        $this->assertContext($contextType, $contextId);

        return DB::transaction(function () use ($actor, $recipient, $subject, $body, $contextType, $contextId) {
            $conversation = Conversation::query()->create([
                'subject' => $subject,
                'context_type' => $contextType,
                'context_id' => $contextId,
                'created_by' => $actor->id,
            ]);

            $now = now();
            $message = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $actor->id,
                'body' => $body,
            ]);

            ConversationParticipant::query()->insert([
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $actor->id,
                    'last_read_at' => $now,
                    'last_read_message_id' => $message->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $recipient->id,
                    'last_read_at' => null,
                    'last_read_message_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            return $conversation->fresh() ?? $conversation;
        });
    }

    public function reply(Conversation $conversation, User $actor, string $body): Message
    {
        if (! $conversation->hasParticipant($actor)) {
            throw ValidationException::withMessages([
                'conversation' => 'You cannot reply to this conversation.',
            ]);
        }

        return DB::transaction(function () use ($conversation, $actor, $body) {
            $message = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $actor->id,
                'body' => $body,
            ]);

            ConversationParticipant::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $actor->id)
                ->update([
                    'last_read_at' => now(),
                    'last_read_message_id' => $message->id,
                ]);

            $conversation->touch();

            return $message->fresh() ?? $message;
        });
    }

    public function markRead(Conversation $conversation, User $actor): void
    {
        $latestId = $conversation->messages()->latest('id')->value('id');

        ConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $actor->id)
            ->update([
                'last_read_at' => now(),
                'last_read_message_id' => $latestId,
            ]);
    }

    private function assertRecipient(User $actor, User $recipient): void
    {
        if ((int) $actor->id === (int) $recipient->id) {
            throw ValidationException::withMessages([
                'recipient_id' => 'Choose another Admin recipient.',
            ]);
        }

        if (! $recipient->canManageRecruitment()) {
            throw ValidationException::withMessages([
                'recipient_id' => 'In-app messages can only be sent to Admin users until candidate and employer portals are available.',
            ]);
        }
    }

    private function assertContext(?ConversationContextType $type, ?int $id): void
    {
        if ($type === null && $id === null) {
            return;
        }

        if ($type === null || $id === null || $id <= 0) {
            throw ValidationException::withMessages([
                'context_id' => 'A related record type and ID are both required.',
            ]);
        }

        $exists = match ($type) {
            ConversationContextType::Employer => EmployerProfile::query()->whereKey($id)->exists(),
            ConversationContextType::Job => Job::query()->whereKey($id)->exists(),
            ConversationContextType::Application => Application::query()->whereKey($id)->exists(),
            ConversationContextType::Submission => EmployerSubmission::query()->whereKey($id)->exists(),
            ConversationContextType::Placement => Placement::query()->whereKey($id)->exists(),
            ConversationContextType::Employee => Employee::query()->whereKey($id)->exists(),
            ConversationContextType::Replacement => Replacement::query()->whereKey($id)->exists(),
            ConversationContextType::Invoice => Invoice::query()->whereKey($id)->exists(),
        };

        if (! $exists) {
            throw ValidationException::withMessages([
                'context_id' => 'The related record could not be found.',
            ]);
        }
    }
}
