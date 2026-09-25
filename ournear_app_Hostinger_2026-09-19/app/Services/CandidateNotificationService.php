<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\CandidateNotificationEvent;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\User;
use App\Notifications\CandidateLifecycleNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CandidateNotificationService
{
    public function notifyApplicationReceived(Application $application): void
    {
        $application->loadMissing('job');

        $this->send(
            $application,
            CandidateNotificationEvent::ApplicationReceived,
            'Application received',
            'We received your application for '.($application->job?->title ?? 'this role').'.',
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
            CandidateNotificationEvent::ApplicationStatusChanged => 'Your application for '.$title.' is now being reviewed.',
            CandidateNotificationEvent::ApplicationShortlisted => 'You have been shortlisted for '.$title.'.',
            CandidateNotificationEvent::ApplicationSelected => 'You have been selected for '.$title.'.',
            CandidateNotificationEvent::ApplicationRejected => 'Your application for '.$title.' was unsuccessful.',
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
            'An interview has been scheduled for '.($application->job?->title ?? 'this role').'.',
            ['application_id' => $application->id, 'interview_id' => $interview->id],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toAccountArray(\Illuminate\Notifications\DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $eventValue = (string) ($data['event'] ?? '');
        $event = CandidateNotificationEvent::tryFrom($eventValue);
        $url = is_string($data['url'] ?? null) ? $data['url'] : route('account.applications.index', absolute: false);

        if (! str_starts_with($url, '/account')) {
            $url = route('account.applications.index', absolute: false);
        }

        return [
            'id' => $notification->id,
            'event' => $eventValue,
            'event_label' => $event?->label() ?? 'Update',
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
    ): void {
        $user = $this->candidateRecipient($application);
        if ($user === null) {
            return;
        }

        $fingerprint = $event->value.':'.collect($context)->sortKeys()->map(fn ($value, $key) => $key.'='.$value)->implode('|');
        $url = route('account.applications.index', absolute: false);

        DB::afterCommit(function () use ($user, $event, $title, $body, $url, $context, $fingerprint) {
            try {
                $fresh = $user->fresh();
                if (! $fresh instanceof User || ! $fresh->isCandidate()) {
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
