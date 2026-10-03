<?php

namespace App\Mail;

use App\Mail\Concerns\UsesCompanyMailIdentity;
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
    use Queueable, SerializesModels, UsesCompanyMailIdentity;

    public function __construct(
        public Application $application,
        public ApplicationInterview $interview,
    ) {}

    public function envelope(): Envelope
    {
        return $this->companyEnvelope(
            'Interview scheduled: '.($this->application->job?->title ?? 'your application'),
            'recruitment',
        );
    }

    public function content(): Content
    {
        $this->application->loadMissing('job');

        return new Content(
            markdown: 'mail.interview-scheduled',
            with: [
                'application' => $this->application,
                'job' => $this->application->job,
                'interview' => $this->interview,
            ],
        );
    }
}
