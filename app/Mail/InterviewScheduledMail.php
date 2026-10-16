<?php

namespace App\Mail;

use App\Enums\SystemMessageTemplateKey;
use App\Mail\Concerns\UsesCompanyMailIdentity;
use App\Mail\Concerns\UsesRecruitmentMessageTemplate;
use App\Models\Application;
use App\Models\ApplicationInterview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewScheduledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailIdentity, UsesRecruitmentMessageTemplate;

    public function __construct(
        public Application $application,
        public ApplicationInterview $interview,
    ) {}

    public function envelope(): Envelope
    {
        $defaultSubject = 'Interview scheduled: '.($this->application->job?->title ?? 'your application');
        $copy = $this->recruitmentEmailCopy(
            SystemMessageTemplateKey::InterviewScheduledEmail->value,
            $this->application,
            $defaultSubject,
            'An interview has been scheduled for this role.',
            $this->interview,
        );

        return $this->companyEnvelope($copy['subject'], 'recruitment');
    }

    public function content(): Content
    {
        $this->application->loadMissing('job');
        $copy = $this->recruitmentEmailCopy(
            SystemMessageTemplateKey::InterviewScheduledEmail->value,
            $this->application,
            'Interview scheduled',
            'An interview has been scheduled for this role.',
            $this->interview,
        );

        return new Content(
            markdown: 'mail.interview-scheduled',
            with: [
                'application' => $this->application,
                'job' => $this->application->job,
                'interview' => $this->interview,
                'introLine' => $copy['intro_line'],
            ],
        );
    }
}
