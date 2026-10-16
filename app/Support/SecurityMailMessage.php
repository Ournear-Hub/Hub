<?php

namespace App\Support;

use Illuminate\Notifications\Messages\MailMessage;

final class SecurityMailMessage
{
    public static function make(
        string $subject,
        string $greeting,
        string $introLine,
        string $actionLabel,
        string $actionUrl,
        ?string $outroLine = null,
    ): MailMessage {
        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($introLine)
            ->action($actionLabel, $actionUrl)
            ->line('If the button above does not work, copy and paste this link into your browser:')
            ->line($actionUrl);

        if ($outroLine !== null && $outroLine !== '') {
            $message->line($outroLine);
        }

        return $message;
    }
}
