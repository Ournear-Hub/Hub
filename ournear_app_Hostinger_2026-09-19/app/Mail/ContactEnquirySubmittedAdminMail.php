<?php

namespace App\Mail;

use App\Models\ContactEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactEnquirySubmittedAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContactEnquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New contact enquiry: '.$this->enquiry->full_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact-enquiry-submitted-admin',
            with: [
                'enquiry' => $this->enquiry,
                'adminUrl' => url('/admin/website-content?page=contact&enquiry='.$this->enquiry->id),
            ],
        );
    }
}
