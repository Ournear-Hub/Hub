<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailIdentity;
use App\Models\ContactEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactEnquirySubmittedSubmitterMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailIdentity;

    public function __construct(public ContactEnquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return $this->companyEnvelope('We received your message', 'support');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact-enquiry-submitted-submitter',
            with: [
                'enquiry' => $this->enquiry,
            ],
        );
    }
}
