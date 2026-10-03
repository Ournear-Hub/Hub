<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailIdentity;
use App\Models\EmployerEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployerEnquirySubmittedEmployerMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailIdentity;

    public function __construct(public EmployerEnquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return $this->companyEnvelope('We received your workforce enquiry', 'recruitment');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.employer-enquiry-submitted-employer',
            with: [
                'enquiry' => $this->enquiry,
            ],
        );
    }
}
