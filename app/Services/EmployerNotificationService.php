<?php

namespace App\Services;

use App\Enums\CandidateInterviewResponse;
use App\Enums\EmployerNotificationEvent;
use App\Enums\SystemMessageTemplateKey;
use App\Mail\EmployerRecruitmentMail;
use App\Models\Application;
use App\Models\ApplicationCorrectionRequest;
use App\Models\ApplicationInterview;
use App\Models\Job;
use App\Models\StaffingCandidateSubmission;
use App\Models\TalentPoolCompanySubmission;
use App\Models\User;
use App\Notifications\EmployerLifecycleNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class EmployerNotificationService
{
    public function __construct(private ?MessageTemplateService $messageTemplates = null) {}

    public function notifyApplicationReceived(Application $application): void
    {
        $application->loadMissing(['job.employer.user']);
        $recipient = $this->employerRecipient($application);
        if (! $recipient instanceof User) {
            return;
        }

        $job = $application->job;
        if (! $job instanceof Job) {
            return;
        }

        $candidateName = $this->candidateDisplayName($application);
        $jobTitle = trim((string) $job->title);
        $title = 'New application received';
        $body = $this->inAppBody(
            SystemMessageTemplateKey::EmployerApplicationReceivedInApp->value,
            $application,
            $candidateName.' applied for '.$jobTitle.'.',
        );

        $url = route('account.employer.jobs.application', [$job, $application], false);

        $this->send(
            $recipient,
            EmployerNotificationEvent::ApplicationReceived,
            $title,
            $body,
            $url,
            [
                'application_id' => $application->id,
                'job_id' => $job->id,
            ],
            SystemMessageTemplateKey::EmployerApplicationReceivedEmail->value,
            'New application received',
            $candidateName.' applied for '.$jobTitle.'.',
        );
    }

    public function notifyInterviewCandidateResponded(
        Application $application,
        ApplicationInterview $interview,
        CandidateInterviewResponse $response,
    ): void {
        $recipient = $this->employerRecipient($application);
        if (! $recipient instanceof User) {
            return;
        }

        $job = $application->job;
        if (! $job instanceof Job) {
            return;
        }

        $candidateName = $this->candidateDisplayName($application);
        $title = 'Interview response received';
        $body = $this->inAppBody(
            SystemMessageTemplateKey::EmployerInterviewRespondedInApp->value,
            $application,
            $candidateName.' '.$response->label().' interview #'.$interview->sort_order.'.',
            $interview,
            [
                'interview_response_label' => $response->label(),
                'interview_number' => (string) $interview->sort_order,
            ],
        );
        $url = route('account.employer.jobs.application', [$job, $application], false);

        $this->send(
            $recipient,
            EmployerNotificationEvent::InterviewCandidateResponded,
            $title,
            $body,
            $url,
            [
                'application_id' => $application->id,
                'interview_id' => $interview->id,
                'response' => $response->value,
            ],
            SystemMessageTemplateKey::EmployerInterviewRespondedEmail->value,
            'Interview response received',
            $candidateName.' '.$response->label().' interview #'.$interview->sort_order.'.',
            $interview,
            [
                'interview_response_label' => $response->label(),
                'interview_number' => (string) $interview->sort_order,
            ],
        );
    }

    public function notifyApplicationMessageReceived(Application $application, string $preview): void
    {
        $recipient = $this->employerRecipient($application);
        if (! $recipient instanceof User) {
            return;
        }

        $job = $application->job;
        if (! $job instanceof Job) {
            return;
        }

        $candidateName = $this->candidateDisplayName($application);
        $previewLine = str($preview)->limit(120)->toString();
        $title = 'New application message';
        $body = $this->inAppBody(
            SystemMessageTemplateKey::EmployerApplicationMessageInApp->value,
            $application,
            $candidateName.': '.$previewLine,
            null,
            ['message_preview' => $previewLine],
        );
        $url = route('account.employer.jobs.application', [$job, $application], false);

        $this->send(
            $recipient,
            EmployerNotificationEvent::ApplicationMessageReceived,
            $title,
            $body,
            $url,
            [
                'application_id' => $application->id,
            ],
            SystemMessageTemplateKey::EmployerApplicationMessageEmail->value,
            'New application message',
            $candidateName.': '.$previewLine,
            null,
            ['message_preview' => $previewLine],
        );
    }

    public function notifyStaffingCandidateSubmitted(StaffingCandidateSubmission $submission): void
    {
        $submission->loadMissing(['employerEnquiry.user', 'candidateUser']);
        $recipient = $submission->employerEnquiry?->user;
        if (! $recipient instanceof User || ! $recipient->canAccessHiringWorkspace()) {
            return;
        }

        $candidateName = trim((string) ($submission->candidateUser?->name ?? 'Candidate'));
        $title = 'Staffing candidate submitted for review';
        $body = 'OurNear submitted '.$candidateName.' for your staffing requirement.';
        $url = route('account.staffing-submissions.show', $submission, false);

        $this->send(
            $recipient,
            EmployerNotificationEvent::StaffingCandidateSubmitted,
            $title,
            $body,
            $url,
            ['staffing_submission_id' => $submission->id],
            SystemMessageTemplateKey::EmployerApplicationMessageEmail->value,
            $title,
            $body,
        );
    }

    public function notifyStaffingJoiningConfirmed(StaffingCandidateSubmission $submission): void
    {
        $submission->loadMissing(['employerEnquiry.user', 'candidateUser']);
        $recipient = $submission->employerEnquiry?->user;
        if (! $recipient instanceof User) {
            return;
        }

        $candidateName = trim((string) ($submission->candidateUser?->name ?? 'Candidate'));
        $title = 'Staffing joining confirmed';
        $body = $candidateName.' joining has been recorded for your staffing requirement.';
        $url = route('account.enquiries.show', $submission->employer_enquiry_id, false);

        $this->send(
            $recipient,
            EmployerNotificationEvent::StaffingJoiningConfirmed,
            $title,
            $body,
            $url,
            ['staffing_submission_id' => $submission->id],
            SystemMessageTemplateKey::EmployerApplicationMessageEmail->value,
            $title,
            $body,
        );
    }

    public function notifyStaffingSubmissionMessage(StaffingCandidateSubmission $submission, string $preview): void
    {
        $submission->loadMissing(['employerEnquiry.user', 'candidateUser']);
        $recipient = $submission->employerEnquiry?->user;
        if (! $recipient instanceof User || ! $recipient->canAccessHiringWorkspace()) {
            return;
        }

        $previewLine = str($preview)->limit(120)->toString();
        $candidateName = trim((string) ($submission->candidateUser?->name ?? 'Candidate'));
        $title = 'Staffing message from OurNear';
        $body = 'OurNear: '.$previewLine;
        $url = route('account.staffing-submissions.show', $submission, false);

        $this->send(
            $recipient,
            EmployerNotificationEvent::StaffingSubmissionMessageReceived,
            $title,
            $body,
            $url,
            ['staffing_submission_id' => $submission->id],
            SystemMessageTemplateKey::EmployerApplicationMessageEmail->value,
            $title,
            $candidateName.' — '.$previewLine,
        );
    }

    public function notifyTalentPoolSubmissionMessage(TalentPoolCompanySubmission $submission, string $preview): void
    {
        $submission->loadMissing(['employerProfile.user', 'candidateUser']);
        $recipient = $submission->employerProfile?->user;
        if (! $recipient instanceof User || ! $recipient->canAccessHiringWorkspace()) {
            return;
        }

        $previewLine = str($preview)->limit(120)->toString();
        $candidateName = trim((string) ($submission->candidateUser?->name ?? 'Candidate'));
        $title = 'Talent Pool message from OurNear';
        $body = 'OurNear: '.$previewLine;
        $url = route('account.coordinated-talent-pool.show', $submission, false);

        $this->send(
            $recipient,
            EmployerNotificationEvent::TalentPoolSubmissionMessageReceived,
            $title,
            $body,
            $url,
            ['submission_id' => $submission->id],
            SystemMessageTemplateKey::EmployerApplicationMessageEmail->value,
            $title,
            $candidateName.' — '.$previewLine,
        );
    }

    public function notifyCorrectionRequestSubmitted(
        Application $application,
        ApplicationCorrectionRequest $request,
    ): void {
        $recipient = $this->employerRecipient($application);
        if (! $recipient instanceof User) {
            return;
        }

        $job = $application->job;
        if (! $job instanceof Job) {
            return;
        }

        $candidateName = $this->candidateDisplayName($application);
        $title = 'Correction request submitted';
        $body = $this->inAppBody(
            SystemMessageTemplateKey::EmployerCorrectionRequestInApp->value,
            $application,
            $candidateName.' requested a correction or clarification.',
        );
        $url = route('account.employer.jobs.application', [$job, $application], false);

        $this->send(
            $recipient,
            EmployerNotificationEvent::CorrectionRequestSubmitted,
            $title,
            $body,
            $url,
            [
                'application_id' => $application->id,
                'correction_request_id' => $request->id,
            ],
            SystemMessageTemplateKey::EmployerCorrectionRequestEmail->value,
            'Correction request submitted',
            $candidateName.' requested a correction or clarification.',
        );
    }

    /**
     * @param  array<string, string>  $variableOverrides
     */
    private function inAppBody(
        string $templateKey,
        Application $application,
        string $fallback,
        ?ApplicationInterview $interview = null,
        array $variableOverrides = [],
    ): string {
        $rendered = $this->templates()->renderForApplication(
            $templateKey,
            $application,
            $interview,
            null,
            $fallback,
            $variableOverrides,
        );

        return (string) ($rendered['body'] ?? $fallback);
    }

    private function templates(): MessageTemplateService
    {
        return $this->messageTemplates ?? app(MessageTemplateService::class);
    }

    private function candidateDisplayName(Application $application): string
    {
        return trim((string) ($application->full_name ?: $application->user?->name ?: 'A candidate'));
    }

    /**
     * @param  array<string, mixed>  $context
     */
    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, string>  $emailVariableOverrides
     */
    private function send(
        User $recipient,
        EmployerNotificationEvent $event,
        string $title,
        string $body,
        string $url,
        array $context,
        string $emailTemplateKey,
        string $emailDefaultSubject,
        string $emailDefaultIntro,
        ?ApplicationInterview $interview = null,
        array $emailVariableOverrides = [],
    ): void {
        $fingerprint = $event->value.':'.collect($context)->sortKeys()->map(fn ($value, $key) => $key.'='.$value)->implode('|');
        $application = $this->applicationFromContext($context);

        DB::afterCommit(function () use (
            $recipient,
            $event,
            $title,
            $body,
            $url,
            $context,
            $fingerprint,
            $emailTemplateKey,
            $emailDefaultSubject,
            $emailDefaultIntro,
            $interview,
            $emailVariableOverrides,
            $application,
        ) {
            try {
                $fresh = $recipient->fresh();
                if (! $fresh instanceof User || ! $fresh->canAccessHiringWorkspace()) {
                    return;
                }

                $preferences = app(CommunicationPreferenceService::class);
                $isNewEvent = ! $fresh->notifications()->where('data->fingerprint', $fingerprint)->exists();

                if ($preferences->canSendRecruitmentInApp($fresh) && $isNewEvent) {
                    Notification::send($fresh, new EmployerLifecycleNotification(
                        $event,
                        $title,
                        $body,
                        $url,
                        $context,
                        $fingerprint,
                    ));
                }

                if ($application instanceof Application && $isNewEvent) {
                    $this->queueEmployerRecruitmentEmail(
                        $fresh,
                        $application,
                        $emailTemplateKey,
                        $emailDefaultSubject,
                        $emailDefaultIntro,
                        $url,
                        $interview,
                        $emailVariableOverrides,
                    );
                }
            } catch (\Throwable $exception) {
                Log::warning('In-app employer notification could not be stored.', [
                    'event' => $event->value,
                    'message' => $exception->getMessage(),
                ]);
            }
        });
    }

    /**
     * @param  array<string, string>  $variableOverrides
     */
    private function queueEmployerRecruitmentEmail(
        User $recipient,
        Application $application,
        string $templateKey,
        string $defaultSubject,
        string $defaultIntro,
        string $relativeUrl,
        ?ApplicationInterview $interview = null,
        array $variableOverrides = [],
    ): void {
        if (! app(CommunicationPreferenceService::class)->canSendRecruitmentEmail($recipient)) {
            return;
        }

        $email = strtolower(trim((string) $recipient->email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $actionUrl = str_starts_with($relativeUrl, 'http')
            ? $relativeUrl
            : url($relativeUrl);

        try {
            \Illuminate\Support\Facades\Mail::to($email)->queue(
                new EmployerRecruitmentMail(
                    $application,
                    $templateKey,
                    $defaultSubject,
                    $defaultIntro,
                    $actionUrl,
                    $interview,
                    $variableOverrides,
                ),
            );
        } catch (\Throwable $exception) {
            Log::warning('Employer recruitment email could not be queued.', [
                'template_key' => $templateKey,
                'application_id' => $application->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function applicationFromContext(array $context): ?Application
    {
        $applicationId = $context['application_id'] ?? null;
        if (! is_numeric($applicationId)) {
            return null;
        }

        return Application::query()->with(['job.employer.user'])->find((int) $applicationId);
    }

    private function employerRecipient(Application $application): ?User
    {
        $employer = $application->job?->employer;
        if ($employer === null) {
            return null;
        }

        $employer->loadMissing('user');
        $user = $employer->user;

        return $user instanceof User ? $user : null;
    }
}
