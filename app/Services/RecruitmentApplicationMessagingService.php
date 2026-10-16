<?php

namespace App\Services;

use App\Enums\ConversationContextType;
use App\Enums\EmployerNotificationEvent;
use App\Enums\RecruitmentActivityType;
use App\Models\Application;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RecruitmentApplicationMessagingService
{
    public function __construct(
        private MessageService $messages,
        private RecruitmentActivityService $activities,
        private EmployerNotificationService $employerNotifications,
        private CandidateNotificationService $candidateNotifications,
    ) {}

    public function send(User $sender, Application $application, string $body, ?string $subject = null): Message
    {
        $body = trim($body);
        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => 'Enter a message.',
            ]);
        }

        $application->loadMissing(['job.employer.user', 'user']);
        $recipient = $this->counterparty($sender, $application);
        $conversation = $this->findThread($sender, $recipient, $application);

        if ($conversation instanceof Conversation) {
            $message = $this->messages->reply($conversation, $sender, $body);
        } else {
            $subject = trim((string) ($subject ?: $this->defaultSubject($application)));
            $conversation = $this->messages->startRecruitmentApplicationMessage(
                $sender,
                $recipient,
                $application,
                $subject,
                $body,
            );
            $message = $conversation->messages()->latest('id')->first();
            if (! $message instanceof Message) {
                throw ValidationException::withMessages([
                    'body' => 'Message could not be sent.',
                ]);
            }
        }

        $this->activities->record(
            actor: $sender,
            type: RecruitmentActivityType::CandidateContacted,
            application: $application,
            body: $sender->isEmployer()
                ? 'Employer sent an in-app message to the candidate.'
                : 'Candidate sent an in-app message to the employer.',
        );

        if ($sender->isCandidate()) {
            $this->employerNotifications->notifyApplicationMessageReceived($application, $body);
        } elseif ($sender->isEmployer()) {
            $this->candidateNotifications->notifyApplicationMessageReceived($application, $body);
        }

        return $message;
    }

    /**
     * @return list<array{id: int, sender_name: string, body: string, sent_at: string|null, is_mine: bool}>
     */
    public function threadFor(User $viewer, Application $application): array
    {
        $application->loadMissing(['job.employer.user', 'user']);
        if (! $this->viewerCanAccess($viewer, $application)) {
            return [];
        }

        $conversation = $this->findThreadForViewer($viewer, $application);
        if (! $conversation instanceof Conversation) {
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

    private function defaultSubject(Application $application): string
    {
        $label = $application->business_id ?: ('Application #'.$application->id);

        return 'Regarding '.$label;
    }

    private function counterparty(User $sender, Application $application): User
    {
        $employerUser = $application->job?->employer?->user;
        $candidateUser = $application->user;

        if ($sender->isEmployer()) {
            if (! $candidateUser instanceof User) {
                throw ValidationException::withMessages([
                    'body' => 'This application has no linked candidate account.',
                ]);
            }

            return $candidateUser;
        }

        if (! $employerUser instanceof User) {
            throw ValidationException::withMessages([
                'body' => 'The employer account for this job could not be found.',
            ]);
        }

        return $employerUser;
    }

    private function findThread(User $sender, User $recipient, Application $application): ?Conversation
    {
        /** @var Collection<int, Conversation> $conversations */
        $conversations = Conversation::query()
            ->where('context_type', ConversationContextType::Application)
            ->where('context_id', $application->id)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $sender->id))
            ->whereHas('participants', fn ($query) => $query->where('user_id', $recipient->id))
            ->orderByDesc('id')
            ->get();

        return $conversations->first();
    }

    private function findThreadForViewer(User $viewer, Application $application): ?Conversation
    {
        return Conversation::query()
            ->where('context_type', ConversationContextType::Application)
            ->where('context_id', $application->id)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $viewer->id))
            ->orderByDesc('id')
            ->first();
    }

    private function viewerCanAccess(User $viewer, Application $application): bool
    {
        if ($viewer->isCandidate() && (int) $application->user_id === (int) $viewer->id) {
            return true;
        }

        if ($viewer->isEmployer()) {
            return \App\Support\EmployerApplicationAccess::managesApplication($viewer, $application);
        }

        return false;
    }
}
