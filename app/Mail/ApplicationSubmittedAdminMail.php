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

class ApplicationSubmittedAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailIdentity;

    public function __construct(public Application $application) {}

    public function envelope(): Envelope
    {
        return $this->companyEnvelope(
            'New job application: '.$this->application->job?->title,
            'recruitment',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.application-submitted-admin',
            with: [
                'application' => $this->application,
                'job' => $this->application->job,
                'adminUrl' => url('/admin/applications?application='.$this->application->id),
            ],
        );
    }
}
