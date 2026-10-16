<?php

namespace App\Notifications;

use App\Support\SecurityMailMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountEmailChangeVerificationNotification extends Notification
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
            'Confirm your new email for OurNear',
            'Confirm email change',
            'You requested to use this email address for your OurNear account.',
            'Confirm new email',
            $this->verifyUrl,
            'If you did not request this change, you can ignore this message. Your current email stays active until you confirm.',
        );
    }
}
