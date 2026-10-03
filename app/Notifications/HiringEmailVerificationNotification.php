<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HiringEmailVerificationNotification extends Notification
{
    public function __construct(private string $verifyUrl) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify recruiter email for OurNear Hire')
            ->greeting('Verify recruiter email')
            ->line('Confirm this email to activate hiring services on your existing OurNear account.')
            ->action('Verify Recruiter Email', $this->verifyUrl)
            ->line('This does not create another account.');
    }
}
