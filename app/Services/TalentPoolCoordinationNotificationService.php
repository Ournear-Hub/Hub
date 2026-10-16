<?php

namespace App\Services;

use App\Enums\AdminNotificationEvent;
use App\Enums\EmployerNotificationEvent;
use App\Enums\EmployerSubmissionStatus;
use App\Models\CompanyRelationshipRecruiterAssignment;
use App\Models\TalentPoolCompanySubmission;
use App\Models\User;
use App\Notifications\EmployerLifecycleNotification;
use App\Notifications\LifecycleNotification;
use App\Support\OurNearInternalRecruiters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class TalentPoolCoordinationNotificationService
{
    public function submissionCreated(TalentPoolCompanySubmission $submission, User $actor): void
    {
        $submission->loadMissing(['candidateUser', 'employerProfile.user', 'responsibleRecruiter']);

        $this->notifyResponsibleRecruiter(
            $submission,
            AdminNotificationEvent::TalentPoolSubmissionCreated,
            'Talent Pool submission created',
            trim(
                ($submission->candidateUser?->name ?? 'A candidate')
                .' was submitted to '
                .($submission->employerProfile?->displayCompany() ?? 'a company')
                .'.'
            ),
            route('admin.talent-pool-company-submissions.show', $submission, false),
            'created:'.$submission->id,
        );

        $employerUser = $submission->employerProfile?->user;
        if ($employerUser instanceof User && $employerUser->canAccessHiringWorkspace()) {
            $this->notifyEmployer(
                $employerUser,
                EmployerNotificationEvent::TalentPoolSubmissionReceived,
                'New OurNear Talent Pool candidate',
                trim(
                    'OurNear submitted '
                    .($submission->candidateUser?->name ?? 'a candidate')
                    .' for your review (not a job application).'
                ),
                route('account.coordinated-talent-pool.show', $submission, false),
                ['submission_id' => $submission->id],
            );
        }
    }

    public function companyRecruiterAssigned(CompanyRelationshipRecruiterAssignment $assignment): void
    {
        $assignment->loadMissing(['employerProfile', 'recruiterUser']);
        $recruiter = $assignment->recruiterUser;
        if (! $recruiter instanceof User || ! OurNearInternalRecruiters::canActAsInternalRecruiter($recruiter)) {
            return;
        }

        $fingerprint = AdminNotificationEvent::CompanyRelationshipRecruiterAssigned->value.':assignment_id='.$assignment->id;

        DB::afterCommit(function () use ($recruiter, $assignment, $fingerprint) {
            try {
                Notification::send($recruiter, new LifecycleNotification(
                    event: AdminNotificationEvent::CompanyRelationshipRecruiterAssigned,
                    title: 'Company assigned to you',
                    body: trim(($assignment->employerProfile?->displayCompany() ?? 'A company').' is now under your Talent Pool relationship.'),
                    url: route('admin.talent-pool-workspace.index', [], false),
                    fingerprint: $fingerprint,
                    context: [
                        'assignment_id' => $assignment->id,
                        'employer_profile_id' => $assignment->employer_profile_id,
                    ],
                ));
            } catch (\Throwable $throwable) {
                Log::warning('Company recruiter assignment notification failed', [
                    'message' => $throwable->getMessage(),
                ]);
            }
        });
    }

    public function employerStatusChanged(TalentPoolCompanySubmission $submission, User $employerActor, EmployerSubmissionStatus $toStatus): void
    {
        $submission->loadMissing(['candidateUser', 'employerProfile', 'responsibleRecruiter']);
        $title = match ($toStatus) {
            EmployerSubmissionStatus::Rejected => 'Employer rejected Talent Pool candidate',
            EmployerSubmissionStatus::Selected => 'Employer selected Talent Pool candidate',
            default => 'Employer updated Talent Pool submission',
        };

        $this->notifyResponsibleRecruiter(
            $submission,
            AdminNotificationEvent::TalentPoolEmployerResponse,
            $title,
            trim(
                ($submission->employerProfile?->displayCompany() ?? 'Employer')
                .' updated '
                .($submission->candidateUser?->name ?? 'candidate')
                .' to '.$toStatus->value
                .' (by '.$employerActor->name.').'
            ),
            route('admin.talent-pool-company-submissions.show', $submission, false),
            'employer_status:'.$submission->id.':'.$toStatus->value.':'.$employerActor->id,
        );
    }

    public function employerReplied(TalentPoolCompanySubmission $submission, User $employerActor, string $bodyPreview): void
    {
        $this->notifyResponsibleRecruiter(
            $submission,
            AdminNotificationEvent::TalentPoolEmployerResponse,
            'Employer replied on Talent Pool submission',
            trim(($submission->employerProfile?->displayCompany() ?? 'Employer').': '.\Illuminate\Support\Str::limit($bodyPreview, 120)),
            route('admin.talent-pool-company-submissions.show', $submission, false),
            'employer_message:'.$submission->id.':'.$employerActor->id.':'.md5($bodyPreview),
        );
    }

    private function notifyResponsibleRecruiter(
        TalentPoolCompanySubmission $submission,
        AdminNotificationEvent $event,
        string $title,
        string $body,
        string $url,
        string $fingerprintSuffix,
    ): void {
        $recruiter = $submission->responsibleRecruiter;
        if (! $recruiter instanceof User || ! OurNearInternalRecruiters::canActAsInternalRecruiter($recruiter)) {
            return;
        }

        $fingerprint = $event->value.':'.$fingerprintSuffix;

        DB::afterCommit(function () use ($recruiter, $event, $title, $body, $url, $fingerprint, $submission) {
            try {
                Notification::send($recruiter, new LifecycleNotification(
                    event: $event,
                    title: $title,
                    body: $body,
                    url: $url,
                    fingerprint: $fingerprint,
                    context: ['submission_id' => $submission->id],
                ));
            } catch (\Throwable $throwable) {
                Log::warning('Talent pool coordination notification failed', [
                    'event' => $event->value,
                    'message' => $throwable->getMessage(),
                ]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function notifyEmployer(
        User $recipient,
        EmployerNotificationEvent $event,
        string $title,
        string $body,
        string $url,
        array $context,
    ): void {
        $fingerprint = $event->value.':'.collect($context)->sortKeys()->map(fn ($v, $k) => $k.'='.$v)->implode('|');

        DB::afterCommit(function () use ($recipient, $event, $title, $body, $url, $context, $fingerprint) {
            try {
                $fresh = $recipient->fresh();
                if (! $fresh instanceof User || ! $fresh->canAccessHiringWorkspace()) {
                    return;
                }

                Notification::send($fresh, new EmployerLifecycleNotification(
                    event: $event,
                    title: $title,
                    body: $body,
                    url: $url,
                    fingerprint: $fingerprint,
                    context: $context,
                ));
            } catch (\Throwable $throwable) {
                Log::warning('Employer talent pool notification failed', [
                    'event' => $event->value,
                    'message' => $throwable->getMessage(),
                ]);
            }
        });
    }
}
