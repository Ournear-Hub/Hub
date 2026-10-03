<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailIdentity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ImportedCandidateWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailIdentity;

    public function __construct(
        public User $candidate,
        public string $ournearId,
        public string $activationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return $this->companyEnvelope('Welcome to OurNear Hire', 'recruitment');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.imported-candidate-welcome',
            with: [
                'candidate' => $this->candidate,
                'ournearId' => $this->ournearId,
                'activationUrl' => $this->activationUrl,
            ],
        );
    }
}
