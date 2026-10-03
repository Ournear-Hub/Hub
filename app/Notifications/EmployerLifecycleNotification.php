<?php

namespace App\Notifications;

use App\Enums\EmployerNotificationEvent;
use Illuminate\Notifications\Notification;

class EmployerLifecycleNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public EmployerNotificationEvent $event,
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
        return ['database'];
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
