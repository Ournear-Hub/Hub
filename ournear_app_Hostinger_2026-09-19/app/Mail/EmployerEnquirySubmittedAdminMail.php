<?php

namespace App\Mail;

use App\Models\EmployerEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployerEnquirySubmittedAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public EmployerEnquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New employer enquiry: '.$this->enquiry->company_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.employer-enquiry-submitted-admin',
            with: [
                'enquiry' => $this->enquiry,
                'adminUrl' => url('/admin/employer-enquiries?enquiry='.$this->enquiry->id),
            ],
        );
    }
}
