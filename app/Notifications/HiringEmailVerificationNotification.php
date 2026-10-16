<?php

namespace App\Notifications;

use App\Support\SecurityMailMessage;
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
        return SecurityMailMessage::make(
            'Verify recruiter email for OurNear Hire',
            'Verify recruiter email',
            'Confirm this email to activate hiring services on your existing OurNear account.',
            'Verify recruiter email',
            $this->verifyUrl,
            'This does not create another account.',
        );
    }
}
