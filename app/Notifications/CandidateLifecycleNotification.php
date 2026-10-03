<?php

namespace App\Notifications;

use App\Enums\CandidateNotificationEvent;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CandidateLifecycleNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public CandidateNotificationEvent $event,
        public string $title,
        public string $body,
        public string $url,
        public array $context = [],
        public string $fingerprint = '',
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->event === CandidateNotificationEvent::ProfileIncomplete
            ? ['database', 'mail']
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body)
            ->action('Complete Profile', url($this->url))
            ->line('Completing these fields helps screening compare your record against job requirements. It does not guarantee shortlisting.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event->value,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'context' => $this->context,
            'fingerprint' => $this->fingerprint,
        ];
    }
}
