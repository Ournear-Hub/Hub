<?php

namespace App\Services;

use App\Enums\AdminNotificationEvent;
use App\Enums\EmployerNotificationEvent;
use App\Enums\RecruiterTalentPoolAssignmentStatus;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\User;
use App\Support\OurNearInternalRecruiters;
use App\Notifications\EmployerLifecycleNotification;
use App\Notifications\LifecycleNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class RecruiterTalentPoolAssignmentNotificationService
{
    public function assignmentCreated(RecruiterTalentPoolAssignment $assignment): void
    {
        $assignment->loadMissing(['candidateUser', 'recruiterUser', 'job']);

        $recruiter = $assignment->recruiterUser;
        if ($recruiter instanceof User) {
            if (OurNearInternalRecruiters::canActAsInternalRecruiter($recruiter)) {
                $this->notifyInternalRecruiter($recruiter, $assignment);
            } else {
                $this->notifyRecruiter(
                    $recruiter,
                    EmployerNotificationEvent::TalentPoolAssignmentReceived,
                    'New Talent Pool assignment',
                    trim(($assignment->candidateUser?->name ?? 'A candidate').' was assigned to your Talent Pool.'),
                    route('account.talent-pool.show', $assignment, false),
                    [
                        'assignment_id' => $assignment->id,
                        'event' => 'created',
                    ],
                );
            }
        }
    }

    public function assignmentStatusChanged(
        RecruiterTalentPoolAssignment $assignment,
        RecruiterTalentPoolAssignmentStatus $from,
        User $actor,
    ): void {
        $assignment->loadMissing(['candidateUser', 'recruiterUser']);

        if ($actor->isEmployer()) {
            app(AdminAlertService::class)->talentPoolAssignmentProgress(
                'Talent Pool assignment updated',
                trim(
                    ($assignment->recruiterUser?->name ?? 'Recruiter')
                    .' updated '
                    .($assignment->candidateUser?->name ?? 'candidate')
                    .' to '.$assignment->status->label().'.'
                ),
                route('admin.talent-pool-assignments.index', ['assignment' => $assignment->id], false),
                [
                    'assignment_id' => $assignment->id,
                    'from_status' => $from->value,
                    'to_status' => $assignment->status->value,
                ],
            );
        }
    }

    private function notifyInternalRecruiter(User $recipient, RecruiterTalentPoolAssignment $assignment): void
    {
        $fingerprint = AdminNotificationEvent::TalentPoolAssignmentProgress->value.':assignment_id='.$assignment->id.':created';

        DB::afterCommit(function () use ($recipient, $assignment, $fingerprint) {
            try {
                $fresh = $recipient->fresh();
                if (! $fresh instanceof User || ! OurNearInternalRecruiters::canActAsInternalRecruiter($fresh)) {
                    return;
                }

                Notification::send($fresh, new LifecycleNotification(
                    event: AdminNotificationEvent::TalentPoolAssignmentProgress,
                    title: 'New Talent Pool assignment',
                    body: trim(($assignment->candidateUser?->name ?? 'A candidate').' was assigned to you for Talent Pool coordination.'),
                    url: route('admin.talent-pool-assignments.index', ['assignment' => $assignment->id], false),
                    fingerprint: $fingerprint,
                    context: [
                        'assignment_id' => $assignment->id,
                        'event' => 'created',
                    ],
                ));
            } catch (\Throwable $throwable) {
                Log::warning('Internal recruiter talent pool notification failed', [
                    'user_id' => $recipient->id,
                    'message' => $throwable->getMessage(),
                ]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function notifyRecruiter(
        User $recipient,
        EmployerNotificationEvent $event,
        string $title,
        string $body,
        string $url,
        array $context,
    ): void {
        $fingerprint = $event->value.':'.collect($context)->sortKeys()->map(fn ($value, $key) => $key.'='.$value)->implode('|');

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
                Log::warning('Recruiter talent pool notification failed', [
                    'event' => $event->value,
                    'user_id' => $recipient->id,
                    'message' => $throwable->getMessage(),
                ]);
            }
        });
    }
}
