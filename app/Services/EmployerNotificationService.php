<?php

namespace App\Services;

use App\Enums\EmployerNotificationEvent;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Notifications\EmployerLifecycleNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class EmployerNotificationService
{
    public function notifyApplicationReceived(Application $application): void
    {
        $application->loadMissing(['job.employer.user']);
        $recipient = $this->employerRecipient($application);
        if (! $recipient instanceof User) {
            return;
        }

        $job = $application->job;
        if (! $job instanceof Job) {
            return;
        }

        $candidateName = trim((string) ($application->full_name ?: $application->user?->name ?: 'A candidate'));
        $jobTitle = trim((string) $job->title);
        $title = 'New application received';
        $body = $candidateName.' applied for '.$jobTitle.'.';

        $url = route('account.employer.jobs.application', [$job, $application], false);

        $this->send(
            $recipient,
            EmployerNotificationEvent::ApplicationReceived,
            $title,
            $body,
            $url,
            [
                'application_id' => $application->id,
                'job_id' => $job->id,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function send(
        User $recipient,
        EmployerNotificationEvent $event,
        string $title,
        string $body,
        string $url,
        array $context,
    ): void {
        $fingerprint = $event->value.':'.collect($context)->sortKeys()->map(fn ($value, $key) => $key.'='.$value)->implode('|');

        DB::afterCommit(function () use ($recipient, $event, $title, $body, $url, $context, $fingerprint) {
            try {
                $fresh = $recipient->fresh();
                if (! $fresh instanceof User || ! $fresh->canAccessHiringWorkspace()) {
                    return;
                }

                if ($fresh->notifications()->where('data->fingerprint', $fingerprint)->exists()) {
                    return;
                }

                Notification::send($fresh, new EmployerLifecycleNotification(
                    $event,
                    $title,
                    $body,
                    $url,
                    $context,
                    $fingerprint,
                ));
            } catch (\Throwable $exception) {
                Log::warning('In-app employer notification could not be stored.', [
                    'event' => $event->value,
                    'message' => $exception->getMessage(),
                ]);
            }
        });
    }

    private function employerRecipient(Application $application): ?User
    {
        $employer = $application->job?->employer;
        if ($employer === null) {
            return null;
        }

        $employer->loadMissing('user');
        $user = $employer->user;

        return $user instanceof User ? $user : null;
    }
}
