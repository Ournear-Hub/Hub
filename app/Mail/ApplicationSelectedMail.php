<?php

namespace App\Mail;

use App\Enums\SystemMessageTemplateKey;
use App\Mail\Concerns\UsesCompanyMailIdentity;
use App\Mail\Concerns\UsesRecruitmentMessageTemplate;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationSelectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailIdentity, UsesRecruitmentMessageTemplate;

    public function __construct(public Application $application) {}

    public function envelope(): Envelope
    {
        $copy = $this->recruitmentEmailCopy(
            SystemMessageTemplateKey::ApplicationSelectedEmail->value,
            $this->application,
            'You have been selected',
            'We are pleased to confirm that you have been selected for this role. Our team will contact you with next steps.',
        );

        return $this->companyEnvelope($copy['subject'], 'recruitment');
    }

    public function content(): Content
    {
        $this->application->loadMissing('job');
        $copy = $this->recruitmentEmailCopy(
            SystemMessageTemplateKey::ApplicationSelectedEmail->value,
            $this->application,
            'You have been selected',
            'We are pleased to confirm that you have been selected for this role. Our team will contact you with next steps.',
        );

        return new Content(
            markdown: 'mail.application-selected',
            with: [
                'application' => $this->application,
                'job' => $this->application->job,
                'introLine' => $copy['intro_line'],
            ],
        );
    }
}
