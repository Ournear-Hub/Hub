<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Mail\ApplicationRejectedMail;
use App\Mail\ApplicationSelectedMail;
use App\Mail\InterviewScheduledMail;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RecruitmentNotificationService
{
    public function __construct(
        private CandidateNotificationService $candidates,
        private EmployerNotificationService $employers,
        private ?CommunicationPreferenceService $preferences = null,
    ) {}

    public function notifyApplicationSubmitted(Application $application): void
    {
        $this->candidates->notifyApplicationReceived($application);
        $this->employers->notifyApplicationReceived($application);
    }

    public function notifyStatusChanged(Application $application, ApplicationStatus $from, ApplicationStatus $to): void
    {
        if ($from === $to) {
            return;
        }

        $this->candidates->notifyStatusChanged($application, $from, $to);

        $mailable = match ($to) {
            ApplicationStatus::Rejected => new ApplicationRejectedMail($application),
            ApplicationStatus::Selected => new ApplicationSelectedMail($application),
            default => null,
        };

        if ($mailable === null) {
            return;
        }

        $this->queueToApplicationEmail($application, $mailable);
    }

    public function notifyInterviewScheduled(Application $application, ApplicationInterview $interview): void
    {
        $this->candidates->notifyInterviewScheduled($application, $interview);
        $this->queueToApplicationEmail($application, new InterviewScheduledMail($application, $interview));
    }

    public function notifyInterviewRescheduled(Application $application, ApplicationInterview $interview): void
    {
        $this->candidates->notifyInterviewRescheduled($application, $interview);
        $this->queueToApplicationEmail($application, new InterviewScheduledMail($application, $interview));
    }

    public function notifyInterviewCancelled(Application $application, ApplicationInterview $interview): void
    {
        $this->candidates->notifyInterviewCancelled($application, $interview);
    }

    public function notifyInterviewReminder(Application $application, ApplicationInterview $interview): void
    {
        $this->candidates->notifyInterviewReminder($application, $interview);
        $this->queueToApplicationEmail($application, new InterviewScheduledMail($application, $interview));
    }

    private function queueToApplicationEmail(Application $application, Mailable $mailable): void
    {
        if (! $this->candidateAllowsOperationalEmail($application)) {
            return;
        }

        $email = strtolower(trim((string) $application->email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($email)->queue($mailable);
        } catch (\Throwable $exception) {
            Log::warning('Operational email could not be queued.', [
                'mailable' => $mailable::class,
                'application_id' => $application->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function candidateAllowsOperationalEmail(Application $application): bool
    {
        if ($application->user_id === null) {
            return true;
        }

        $user = User::query()->find($application->user_id);
        if (! $user instanceof User) {
            return true;
        }

        return ($this->preferences ?? app(CommunicationPreferenceService::class))
            ->canSendRecruitmentEmail($user);
    }
}
