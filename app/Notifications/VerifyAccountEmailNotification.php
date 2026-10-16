<?php

namespace App\Notifications;

use App\Support\ApplicationUrlResolver;
use App\Support\SecurityMailMessage;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyAccountEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return SecurityMailMessage::make(
            'Verify your email address',
            'Welcome to OurNear Hire',
            'Please confirm your email address to activate your account.',
            'Verify your email address',
            $url,
            'If you did not create an account, no further action is required.',
        );
    }

    public function previewVerificationUrl(object $notifiable): string
    {
        return $this->verificationUrl($notifiable);
    }

    protected function verificationUrl($notifiable): string
    {
        app(ApplicationUrlResolver::class)->applyForUrlGeneration();

        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}
