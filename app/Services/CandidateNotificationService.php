<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\CandidateNotificationEvent;
use App\Enums\EmployerNotificationEvent;
use App\Enums\SystemMessageTemplateKey;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\User;
use App\Notifications\CandidateLifecycleNotification;
use App\Services\MessageTemplateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CandidateNotificationService
{
    public function __construct(
        private ?MessageTemplateService $messageTemplates = null,
        private ?CommunicationPreferenceService $preferences = null,
    ) {}

    public function notifyApplicationReceived(Application $application): void
    {
        $application->loadMissing('job');

        $this->send(
            $application,
            CandidateNotificationEvent::ApplicationReceived,
            'Application received',
            $this->inAppBody(
                SystemMessageTemplateKey::ApplicationReceivedInApp->value,
                $application,
                'We received your application for '.($application->job?->title ?? 'this role').'.',
            ),
            ['application_id' => $application->id],
        );
    }

    public function notifyStatusChanged(Application $application, ApplicationStatus $from, ApplicationStatus $to): void
    {
        if ($from === $to) {
            return;
        }

        $event = match ($to) {
            ApplicationStatus::Reviewing => CandidateNotificationEvent::ApplicationStatusChanged,
            ApplicationStatus::Shortlisted => CandidateNotificationEvent::ApplicationShortlisted,
            ApplicationStatus::Selected, ApplicationStatus::Placed => CandidateNotificationEvent::ApplicationSelected,
            ApplicationStatus::Rejected => CandidateNotificationEvent::ApplicationRejected,
            default => null,
        };

        if ($event === null) {
            return;
        }

        $application->loadMissing('job');
        $title = $application->job?->title ?? 'this role';

        $body = match ($event) {
            CandidateNotificationEvent::ApplicationStatusChanged => $this->inAppBody(
                SystemMessageTemplateKey::ApplicationReviewingInApp->value,
                $application,
                'Your application for '.$title.' is now being reviewed.',
            ),
            CandidateNotificationEvent::ApplicationShortlisted => $this->inAppBody(
                SystemMessageTemplateKey::ApplicationShortlistedInApp->value,
                $application,
                'You have been shortlisted for '.$title.'.',
            ),
            CandidateNotificationEvent::ApplicationSelected => $this->inAppBody(
                SystemMessageTemplateKey::ApplicationSelectedInApp->value,
                $application,
                'You have been selected for '.$title.'.',
            ),
            CandidateNotificationEvent::ApplicationRejected => $this->inAppBody(
                SystemMessageTemplateKey::ApplicationRejectedInApp->value,
                $application,
                'Your application for '.$title.' was unsuccessful.',
            ),
            default => 'There is an update on your application for '.$title.'.',
        };

        $this->send(
            $application,
            $event,
            $event->label(),
            $body,
            ['application_id' => $application->id, 'status' => $to->value],
        );
    }

    public function notifyInterviewScheduled(Application $application, ApplicationInterview $interview): void
    {
        $application->loadMissing('job');

        $this->send(
            $application,
            CandidateNotificationEvent::InterviewScheduled,
            'Interview scheduled',
            $this->inAppBody(
                SystemMessageTemplateKey::InterviewScheduledInApp->value,
                $application,
                'An interview has been scheduled for '.($application->job?->title ?? 'this role').'.',
                $interview,
            ),
            ['application_id' => $application->id, 'interview_id' => $interview->id],
        );
    }

    public function notifyInterviewRescheduled(Application $application, ApplicationInterview $interview): void
    {
        $application->loadMissing('job');

        $this->send(
            $application,
            CandidateNotificationEvent::InterviewRescheduled,
            'Interview rescheduled',
            $this->inAppBody(
                SystemMessageTemplateKey::InterviewRescheduledInApp->value,
                $application,
                'Your interview for '.($application->job?->title ?? 'this role').' has been rescheduled.',
                $interview,
            ),
            ['application_id' => $application->id, 'interview_id' => $interview->id],
        );
    }

    public function notifyInterviewCancelled(Application $application, ApplicationInterview $interview): void
    {
        $application->loadMissing('job');

        $this->send(
            $application,
            CandidateNotificationEvent::InterviewCancelled,
            'Interview cancelled',
            $this->inAppBody(
                SystemMessageTemplateKey::InterviewCancelledInApp->value,
                $application,
                'Your interview for '.($application->job?->title ?? 'this role').' was cancelled.',
                $interview,
            ),
            ['application_id' => $application->id, 'interview_id' => $interview->id],
        );
    }

    public function notifyInterviewReminder(Application $application, ApplicationInterview $interview): void
    {
        $application->loadMissing('job');

        $this->send(
            $application,
            CandidateNotificationEvent::InterviewReminder,
            'Interview reminder',
            $this->inAppBody(
                SystemMessageTemplateKey::InterviewReminderInApp->value,
                $application,
                'Reminder: you have an upcoming interview for '.($application->job?->title ?? 'this role').'.',
                $interview,
            ),
            ['application_id' => $application->id, 'interview_id' => $interview->id],
        );
    }

    public function notifyApplicationMessageReceived(Application $application, string $preview): void
    {
        $application->loadMissing('job');

        $title = 'New message about your application';
        $body = str($preview)->limit(120)->toString();
        $url = route('account.applications.show', $application, false);

        $this->send(
            $application,
            CandidateNotificationEvent::ApplicationMessageReceived,
            $title,
            $body,
            ['application_id' => $application->id],
            $url,
        );
    }

    public function notifyIncompleteProfile(User $user): void
    {
        if (! $user->isCandidate()) {
            return;
        }

        $items = collect($user->profileCompletionItems())->where('complete', false)->values();
        if ($items->isEmpty() || (int) $user->profile_completion >= 80) {
            return;
        }

        $fingerprint = CandidateNotificationEvent::ProfileIncomplete->value.':user='.$user->id;
        $url = route('account.profile.edit', absolute: false);
        $title = 'Complete Your Personal & Professional Information';
        $body = 'Complete your personal profile and professional details to improve your chances of being shortlisted for suitable job opportunities. Accurate, complete information helps recruiters assess your qualifications and experience more effectively.';

        try {
            if (! $this->canDeliverProductNotifications($user)) {
                return;
            }

            if ($user->notifications()->where('data->fingerprint', $fingerprint)->exists()) {
                return;
            }

            Notification::send($user, new CandidateLifecycleNotification(
                CandidateNotificationEvent::ProfileIncomplete,
                $title,
                $body,
                $url,
                ['missing' => $items->pluck('key')->all()],
                $fingerprint,
            ));
        } catch (\Throwable $exception) {
            Log::warning('Incomplete profile notification could not be stored.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function toAccountArray(\Illuminate\Notifications\DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $eventValue = (string) ($data['event'] ?? '');
        $candidateEvent = CandidateNotificationEvent::tryFrom($eventValue);
        $employerEvent = EmployerNotificationEvent::tryFrom($eventValue);
        $defaultUrl = $employerEvent instanceof EmployerNotificationEvent
            ? route('account.hiring', absolute: false)
            : route('account.applications.index', absolute: false);
        $url = is_string($data['url'] ?? null) ? $data['url'] : $defaultUrl;

        if (! str_starts_with($url, '/account')) {
            $url = $defaultUrl;
        }

        $eventLabel = $candidateEvent?->label()
            ?? $employerEvent?->label()
            ?? 'Update';

        return [
            'id' => $notification->id,
            'event' => $eventValue,
            'event_label' => $eventLabel,
            'title' => (string) ($data['title'] ?? 'Notification'),
            'body' => (string) ($data['body'] ?? ''),
            'url' => $url,
            'read_at' => $notification->read_at?->toDateTimeString(),
            'created_at' => $notification->created_at?->toDateTimeString(),
            'is_unread' => $notification->read_at === null,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function send(
        Application $application,
        CandidateNotificationEvent $event,
        string $title,
        string $body,
        array $context,
        ?string $url = null,
    ): void {
        $user = $this->candidateRecipient($application);
        if ($user === null) {
            return;
        }

        $fingerprint = $event->value.':'.collect($context)->sortKeys()->map(fn ($value, $key) => $key.'='.$value)->implode('|');
        $url ??= route('account.applications.index', absolute: false);

        DB::afterCommit(function () use ($user, $event, $title, $body, $url, $context, $fingerprint) {
            try {
                $fresh = $user->fresh();
                if (! $fresh instanceof User || ! $fresh->isCandidate()) {
                    return;
                }

                if (! $this->canDeliverProductNotifications($fresh)) {
                    return;
                }

                if ($fresh->notifications()->where('data->fingerprint', $fingerprint)->exists()) {
                    return;
                }

                Notification::send($fresh, new CandidateLifecycleNotification(
                    $event,
                    $title,
                    $body,
                    $url,
                    $context,
                    $fingerprint,
                ));
            } catch (\Throwable $exception) {
                Log::warning('In-app candidate notification could not be stored.', [
                    'event' => $event->value,
                    'message' => $exception->getMessage(),
                ]);
            }
        });
    }

    private function templates(): MessageTemplateService
    {
        return $this->messageTemplates ?? app(MessageTemplateService::class);
    }

    private function preferenceService(): CommunicationPreferenceService
    {
        return $this->preferences ?? app(CommunicationPreferenceService::class);
    }

    private function canDeliverProductNotifications(User $user): bool
    {
        return $this->preferenceService()->canSendRecruitmentInApp($user);
    }

    private function inAppBody(
        string $templateKey,
        Application $application,
        string $fallback,
        ?ApplicationInterview $interview = null,
    ): string {
        $rendered = $this->templates()->renderForApplication($templateKey, $application, $interview, null, $fallback);

        return (string) ($rendered['body'] ?? $fallback);
    }

    private function candidateRecipient(Application $application): ?User
    {
        if ($application->user_id === null) {
            return null;
        }

        $application->loadMissing('user');
        $user = $application->user;

        if (! $user instanceof User || ! $user->isCandidate()) {
            return null;
        }

        return $user;
    }
}
