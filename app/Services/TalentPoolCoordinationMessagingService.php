<?php

namespace App\Services;

use App\Enums\ConversationContextType;
use App\Enums\EmployerNotificationEvent;
use App\Enums\RecruitmentActivityType;
use App\Enums\RecruitmentOperationalVisibility;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\TalentPoolCompanySubmission;
use App\Models\User;
use App\Support\OurNearInternalRecruiters;
use App\Support\RecruiterOperationalAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TalentPoolCoordinationMessagingService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private EmployerNotificationService $employerNotifications,
    ) {}

    public function send(User $sender, TalentPoolCompanySubmission $submission, string $body): Message
    {
        $body = trim($body);
        if ($body === '') {
            throw ValidationException::withMessages(['body' => 'Enter a message.']);
        }

        if (! RecruiterOperationalAccess::canViewSubmissionOperational($sender, $submission)) {
            throw ValidationException::withMessages(['body' => 'You cannot message on this submission.']);
        }

        $submission->loadMissing(['employerProfile.user', 'responsibleRecruiter']);
        $employerUser = $submission->employerProfile?->user;
        $recruiter = $submission->responsibleRecruiter;

        if (! $employerUser instanceof User || ! $recruiter instanceof User) {
            throw ValidationException::withMessages(['body' => 'This submission is not ready for employer messaging.']);
        }

        $recipient = $this->counterparty($sender, $employerUser, $recruiter);
        $conversation = $this->findThread($submission);

        return DB::transaction(function () use ($sender, $recipient, $submission, $body, $conversation, $employerUser) {
            if ($conversation instanceof Conversation) {
                if (! $conversation->hasParticipant($sender)) {
                    throw ValidationException::withMessages(['body' => 'You are not a participant in this conversation.']);
                }
                $message = Message::query()->create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'body' => $body,
                ]);
                $conversation->touch();
            } else {
                $conversation = Conversation::query()->create([
                    'subject' => 'Talent Pool: '.($submission->candidateUser?->name ?? 'Candidate'),
                    'context_type' => ConversationContextType::TalentPoolSubmission,
                    'context_id' => $submission->id,
                    'created_by' => $sender->id,
                ]);
                $now = now();
                $message = Message::query()->create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'body' => $body,
                ]);
                $participantIds = collect([$sender->id, $recipient->id])->unique()->values();
                foreach ($participantIds as $participantId) {
                    \App\Models\ConversationParticipant::query()->create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $participantId,
                        'last_read_at' => (int) $participantId === (int) $sender->id ? $now : null,
                        'last_read_message_id' => (int) $participantId === (int) $sender->id ? $message->id : null,
                    ]);
                }
            }

            $this->activities->record(
                actor: $sender,
                type: RecruitmentActivityType::TalentPoolEmployerMessage,
                body: $body,
                employer: $submission->employerProfile,
                talentPoolSubmission: $submission,
                visibility: RecruitmentOperationalVisibility::EmployerShared,
                operationalMeta: [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                ],
            );

            if ($sender->isAdmin() && OurNearInternalRecruiters::canActAsInternalRecruiter($sender)) {
                $this->employerNotifications->notifyTalentPoolSubmissionMessage($submission, $body);
            } elseif ((int) $sender->id === (int) $employerUser->id) {
                app(TalentPoolCoordinationNotificationService::class)->employerReplied($submission, $sender, $body);
            }

            return $message->fresh() ?? $message;
        });
    }

    /**
     * @return list<array{id: int, sender_name: string, body: string, sent_at: string|null, is_mine: bool}>
     */
    public function threadFor(User $viewer, TalentPoolCompanySubmission $submission): array
    {
        if (! RecruiterOperationalAccess::canViewSubmissionOperational($viewer, $submission)) {
            return [];
        }

        $conversation = $this->findThread($submission);
        if (! $conversation instanceof Conversation) {
            return [];
        }

        if (! $conversation->hasParticipant($viewer) && ! RecruiterOperationalAccess::canViewInternalOperational($viewer)) {
            return [];
        }

        return $conversation->messages()
            ->with('sender:id,name')
            ->orderBy('id')
            ->get()
            ->map(fn (Message $message) => [
                'id' => $message->id,
                'sender_name' => $message->sender?->name ?? 'User',
                'body' => $message->body,
                'sent_at' => $message->created_at?->toDateTimeString(),
                'is_mine' => (int) $message->sender_id === (int) $viewer->id,
            ])
            ->values()
            ->all();
    }

    private function findThread(TalentPoolCompanySubmission $submission): ?Conversation
    {
        return Conversation::query()
            ->where('context_type', ConversationContextType::TalentPoolSubmission)
            ->where('context_id', $submission->id)
            ->latest('id')
            ->first();
    }

    private function counterparty(User $sender, User $employerUser, User $recruiter): User
    {
        if ((int) $sender->id === (int) $employerUser->id) {
            return $recruiter;
        }
        if ((int) $sender->id === (int) $recruiter->id) {
            return $employerUser;
        }

        throw ValidationException::withMessages(['body' => 'You are not authorized to message on this submission.']);
    }
}
