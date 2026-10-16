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

class ApplicationRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailIdentity, UsesRecruitmentMessageTemplate;

    public function __construct(public Application $application) {}

    public function envelope(): Envelope
    {
        $copy = $this->recruitmentEmailCopy(
            SystemMessageTemplateKey::ApplicationRejectedEmail->value,
            $this->application,
            'Update on your application',
            'Thank you for applying for this role. After careful review, we will not be progressing your application on this occasion.',
        );

        return $this->companyEnvelope($copy['subject'], 'recruitment');
    }

    public function content(): Content
    {
        $this->application->loadMissing('job');
        $copy = $this->recruitmentEmailCopy(
            SystemMessageTemplateKey::ApplicationRejectedEmail->value,
            $this->application,
            'Update on your application',
            'Thank you for applying for this role. After careful review, we will not be progressing your application on this occasion.',
        );

        return new Content(
            markdown: 'mail.application-rejected',
            with: [
                'application' => $this->application,
                'job' => $this->application->job,
                'introLine' => $copy['intro_line'],
            ],
        );
    }
}
