<?php

namespace App\Mail;

use App\Models\Freelancer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FreelancerSubmittedApplicantMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Freelancer $freelancer) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your freelancer application',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.freelancer-submitted-applicant',
            with: [
                'freelancer' => $this->freelancer,
            ],
        );
    }
}
