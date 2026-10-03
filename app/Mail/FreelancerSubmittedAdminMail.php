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

class FreelancerSubmittedAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailIdentity;

    public function __construct(public Freelancer $freelancer) {}

    public function envelope(): Envelope
    {
        return $this->companyEnvelope('New freelancer enquiry: '.$this->freelancer->name, 'recruitment');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.freelancer-submitted-admin',
            with: [
                'freelancer' => $this->freelancer,
                'adminUrl' => url('/admin/freelancers/'.$this->freelancer->id),
            ],
        );
    }
}
