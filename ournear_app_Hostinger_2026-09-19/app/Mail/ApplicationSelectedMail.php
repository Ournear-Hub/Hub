<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationSelectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Application $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been selected',
        );
    }

    public function content(): Content
    {
        $this->application->loadMissing('job');

        return new Content(
            markdown: 'mail.application-selected',
            with: [
                'application' => $this->application,
                'job' => $this->application->job,
            ],
        );
    }
}
