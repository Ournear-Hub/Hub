<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailIdentity;
use App\Models\Freelancer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FreelancerSubmittedApplicantMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailIdentity;

    public function __construct(public Freelancer $freelancer) {}

    public function envelope(): Envelope
    {
        return $this->companyEnvelope('We received your freelancer application', 'recruitment');
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
