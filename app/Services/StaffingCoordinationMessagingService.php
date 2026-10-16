<?php

namespace App\Services;

use App\Enums\ConversationContextType;
use App\Enums\RecruitmentActivityType;
use App\Enums\RecruitmentOperationalVisibility;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\StaffingCandidateSubmission;
use App\Models\User;
use App\Support\OurNearInternalRecruiters;
use App\Support\RecruiterOperationalAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffingCoordinationMessagingService
{
    public function __construct(
        private RecruitmentActivityService $activities,
        private EmployerNotificationService $employerNotifications,
    ) {}

    public function send(User $sender, StaffingCandidateSubmission $submission, string $body): Message
    {
        $body = trim($body);
        if ($body === '') {
            throw ValidationException::withMessages(['body' => 'Enter a message.']);
        }

        if (! RecruiterOperationalAccess::canMessageOnStaffingSubmission($sender, $submission)) {
            throw ValidationException::withMessages(['body' => 'You cannot message on this staffing submission.']);
        }

        $submission->loadMissing(['employerEnquiry.user', 'responsibleRecruiter', 'candidateUser']);
        $employerUser = $submission->employerEnquiry?->user;
        $recruiter = $submission->responsibleRecruiter;

        if (! $employerUser instanceof User) {
            throw ValidationException::withMessages(['body' => 'This submission is not linked to a client account.']);
        }

        if (! $recruiter instanceof User && ! RecruiterOperationalAccess::canManageStaffingSubmissionOperational($sender, $submission)) {
            throw ValidationException::withMessages(['body' => 'This submission is not ready for client messaging.']);
        }

        $recruiter ??= $sender->isAdmin() ? $sender : null;
        if (! $recruiter instanceof User) {
            throw ValidationException::withMessages(['body' => 'No OurNear coordinator is assigned for this submission.']);
        }

        $recipient = $this->counterparty($sender, $employerUser, $recruiter);
        $conversation = $this->findThread($submission);

        return DB::transaction(function () use ($sender, $recipient, $submission, $body, $conversation, $employerUser, $recruiter) {
            if ($conversation instanceof Conversation) {
                if (! $conversation->hasParticipant($sender) && ! RecruiterOperationalAccess::canViewInternalOperational($sender)) {
                    throw ValidationException::withMessages(['body' => 'You are not a participant in this conversation.']);
                }
                if (! $conversation->hasParticipant($sender)) {
                    \App\Models\ConversationParticipant::query()->firstOrCreate([
                        'conversation_id' => $conversation->id,
                        'user_id' => $sender->id,
                    ]);
                }
                $message = Message::query()->create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'body' => $body,
                ]);
                $conversation->touch();
            } else {
                $conversation = Conversation::query()->create([
                    'subject' => 'Staffing: '.($submission->candidateUser?->name ?? 'Candidate'),
                    'context_type' => ConversationContextType::StaffingSubmission,
                    'context_id' => $submission->id,
                    'created_by' => $sender->id,
                ]);
                $now = now();
                $message = Message::query()->create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'body' => $body,
                ]);
                foreach (collect([$sender->id, $recipient->id, $recruiter->id])->unique()->values() as $participantId) {
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
                type: RecruitmentActivityType::StaffingEmployerMessage,
                enquiry: $submission->employerEnquiry,
                body: $body,
                employer: $employerUser->employerProfile,
                staffingSubmission: $submission,
                visibility: RecruitmentOperationalVisibility::EmployerShared,
                operationalMeta: [
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                ],
            );

            if ($sender->isAdmin() && OurNearInternalRecruiters::canActAsInternalRecruiter($sender)) {
                $this->employerNotifications->notifyStaffingSubmissionMessage($submission, $body);
            }

            return $message->fresh() ?? $message;
        });
    }

    /**
     * @return list<array{id: int, sender_name: string, body: string, sent_at: string|null, is_mine: bool}>
     */
    public function threadFor(User $viewer, StaffingCandidateSubmission $submission): array
    {
        if (! RecruiterOperationalAccess::canViewStaffingSubmissionOperational($viewer, $submission)) {
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

    private function findThread(StaffingCandidateSubmission $submission): ?Conversation
    {
        return Conversation::query()
            ->where('context_type', ConversationContextType::StaffingSubmission)
            ->where('context_id', $submission->id)
            ->latest('id')
            ->first();
    }

    private function counterparty(User $sender, User $employerUser, User $recruiter): User
    {
        if ((int) $sender->id === (int) $employerUser->id) {
            return $recruiter;
        }
        if ((int) $sender->id === (int) $recruiter->id || $sender->isAdmin()) {
            return $employerUser;
        }

        throw ValidationException::withMessages(['body' => 'You are not authorized to message on this submission.']);
    }
}
