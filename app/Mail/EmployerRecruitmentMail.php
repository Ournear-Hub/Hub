<?php

namespace App\Mail;

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

class EmployerRecruitmentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, UsesCompanyMailIdentity, UsesRecruitmentMessageTemplate;

    /**
     * @param  array<string, string>  $variableOverrides
     */
    public function __construct(
        public Application $application,
        public string $templateKey,
        public string $defaultSubject,
        public string $defaultIntro,
        public string $actionUrl,
        public ?ApplicationInterview $interview = null,
        public array $variableOverrides = [],
    ) {}

    public function envelope(): Envelope
    {
        $copy = $this->employerEmailCopy();

        return $this->companyEnvelope($copy['subject'], 'recruitment');
    }

    public function content(): Content
    {
        $copy = $this->employerEmailCopy();
        $this->application->loadMissing(['job.employer.user']);

        return new Content(
            markdown: 'mail.employer-recruitment',
            with: [
                'application' => $this->application,
                'job' => $this->application->job,
                'introLine' => $copy['intro_line'],
                'actionUrl' => $this->actionUrl,
            ],
        );
    }

    /**
     * @return array{subject: string, intro_line: string}
     */
    private function employerEmailCopy(): array
    {
        $rendered = app(\App\Services\MessageTemplateService::class)->renderForApplication(
            $this->templateKey,
            $this->application,
            $this->interview,
            $this->defaultSubject,
            $this->defaultIntro,
            $this->variableOverrides,
        );

        return [
            'subject' => (string) ($rendered['subject'] ?? $this->defaultSubject),
            'intro_line' => (string) ($rendered['body'] ?? $this->defaultIntro),
        ];
    }
}
