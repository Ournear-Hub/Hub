<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailIdentity;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationSelectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailIdentity;

    public function __construct(public Application $application) {}

    public function envelope(): Envelope
    {
        return $this->companyEnvelope('You have been selected', 'recruitment');
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
