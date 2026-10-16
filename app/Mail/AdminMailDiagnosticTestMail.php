<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailIdentity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminMailDiagnosticTestMail extends Mailable
{
    use Queueable, SerializesModels, UsesCompanyMailIdentity;

    public function __construct(public User $requestedBy) {}

    public function envelope(): Envelope
    {
        return $this->companyEnvelope('[OurNear Hire] Mail delivery test', 'system');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin-mail-diagnostic-test',
            with: [
                'requestedBy' => $this->requestedBy,
                'sentAt' => now()->toDateTimeString(),
            ],
        );
    }
}
