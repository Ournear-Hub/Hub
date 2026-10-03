<?php

namespace App\Services;

use App\Enums\JobPublishingStatus;
use App\Enums\PermissionName;
use App\Enums\SecurityActivityType;
use App\Models\Job;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class JobLifecycleService
{
    public function __construct(
        private SecurityAuditService $audit,
        private EmployerPostingEligibilityService $eligibility,
    ) {}

    public function publish(Job $job, User $actor): Job
    {
        if ($actor->canAccessHiringWorkspace() && ! $actor->hasPermission(PermissionName::JobsManage->value) && ! $this->eligibility->allows($actor)) {
            throw ValidationException::withMessages([
                'posting' => $this->eligibility->for($actor)['reason'] ?? 'Job posting is not available.',
            ]);
        }

        return $this->transition($job, JobPublishingStatus::Published, $actor);
    }

    public function unpublish(Job $job, User $actor): Job
    {
        return $this->transition($job, JobPublishingStatus::Draft, $actor);
    }

    public function pause(Job $job, User $actor): Job
    {
        return $this->transition($job, JobPublishingStatus::Paused, $actor);
    }

    public function close(Job $job, User $actor): Job
    {
        return $this->transition($job, JobPublishingStatus::Closed, $actor);
    }

    public function archive(Job $job, User $actor): Job
    {
        return $this->transition($job, JobPublishingStatus::Archived, $actor);
    }

    public function applyAction(Job $job, string $action, User $actor): Job
    {
        return match ($action) {
            'publish' => $this->publish($job, $actor),
            'unpublish' => $this->unpublish($job, $actor),
            'pause' => $this->pause($job, $actor),
            'close' => $this->close($job, $actor),
            'archive' => $this->archive($job, $actor),
            default => throw ValidationException::withMessages([
                'action' => 'Unsupported job lifecycle action.',
            ]),
        };
    }

    public function togglePublish(Job $job, User $actor): Job
    {
        return $job->publishing_status === JobPublishingStatus::Published
            ? $this->unpublish($job, $actor)
            : $this->publish($job, $actor);
    }

    public function toggleActive(Job $job, User $actor): Job
    {
        $wantActive = ! $job->active;

        if (in_array($job->publishing_status, [
            JobPublishingStatus::Paused,
            JobPublishingStatus::Closed,
            JobPublishingStatus::Archived,
        ], true)) {
            if ($wantActive) {
                throw ValidationException::withMessages([
                    'active' => 'Publish this job to make it active.',
                ]);
            }

            return $job->active ? $this->transition($job, $job->publishing_status, $actor) : $job;
        }

        if ($job->publishing_status === JobPublishingStatus::Published) {
            return $wantActive ? $this->publish($job, $actor) : $this->unpublish($job, $actor);
        }

        $job->forceFill(['active' => $wantActive])->save();

        return $job->fresh() ?? $job;
    }

    public function transition(Job $job, JobPublishingStatus $target, User $actor): Job
    {
        $from = $job->publishing_status;
        $fromActive = (bool) $job->active;
        $active = $target === JobPublishingStatus::Published;

        if ($from === $target && $fromActive === $active) {
            return $job;
        }

        $job->forceFill([
            'publishing_status' => $target,
            'active' => $active,
        ])->save();

        $this->audit->record(
            action: $this->activityType($target),
            description: sprintf(
                'Job %s changed from %s to %s.',
                $job->business_id ?: $job->title,
                $from->label(),
                $target->label(),
            ),
            actor: $actor,
            subject: $job,
            metadata: [
                'from' => $from->value,
                'to' => $target->value,
                'active' => $active,
            ],
        );

        return $job->fresh() ?? $job;
    }

    /**
     * Keep admin create/update drafts able to stay independently active.
     * Operator statuses other than draft/published always deactivate.
     */
    public function syncActiveForStatus(JobPublishingStatus $status, bool $requestedActive): bool
    {
        if (in_array($status, [
            JobPublishingStatus::Paused,
            JobPublishingStatus::Closed,
            JobPublishingStatus::Archived,
        ], true)) {
            return false;
        }

        if ($status === JobPublishingStatus::Published) {
            return true;
        }

        return $requestedActive;
    }

    private function activityType(JobPublishingStatus $status): SecurityActivityType
    {
        return match ($status) {
            JobPublishingStatus::Published => SecurityActivityType::JobPublished,
            JobPublishingStatus::Draft => SecurityActivityType::JobUnpublished,
            JobPublishingStatus::Paused => SecurityActivityType::JobPaused,
            JobPublishingStatus::Closed => SecurityActivityType::JobClosed,
            JobPublishingStatus::Archived => SecurityActivityType::JobArchived,
        };
    }
}
