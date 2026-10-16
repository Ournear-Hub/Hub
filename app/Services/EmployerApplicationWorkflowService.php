<?php

namespace App\Services;

use App\Enums\InterviewMode;
use App\Enums\InterviewOutcome;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Models\Placement;
use App\Models\User;
use App\Enums\ApplicationStatus;
use App\Support\EmployerApplicationAccess;
use Illuminate\Validation\ValidationException;

class EmployerApplicationWorkflowService
{
    public function __construct(
        private ApplicationPipelineService $pipeline,
        private PlacementService $placements,
        private RecruitmentApplicationMessagingService $messaging,
    ) {}

    public function assertCanManage(User $employer, Application $application): void
    {
        if (! EmployerApplicationAccess::managesApplication($employer, $application)) {
            throw ValidationException::withMessages([
                'application' => 'You cannot manage this application.',
            ]);
        }
    }

    public function shortlist(Application $application, User $employer, ?string $notes = null): Application
    {
        $this->assertCanManage($employer, $application);

        return $this->pipeline->shortlist($application, $employer, $notes);
    }

    public function reject(
        Application $application,
        User $employer,
        int $rejectionReasonId,
        ?string $notes = null,
    ): Application {
        $this->assertCanManage($employer, $application);

        return $this->pipeline->markNotSuitable($application, $employer, $rejectionReasonId, $notes);
    }

    /**
     * @param  array{
     *     scheduled_at: string,
     *     mode: InterviewMode|string,
     *     location_or_link?: string|null,
     *     interviewer_name?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function scheduleInterview(Application $application, User $employer, array $data): ApplicationInterview
    {
        $this->assertCanManage($employer, $application);

        $employer->loadMissing('employerProfile');
        $interviewerName = trim((string) ($data['interviewer_name'] ?? $employer->name ?? 'Employer'));
        if ($interviewerName === '') {
            $interviewerName = 'Employer';
        }

        return $this->pipeline->saveInterview($application, [
            'scheduled_at' => $data['scheduled_at'],
            'mode' => $data['mode'],
            'location_or_link' => $data['location_or_link'] ?? null,
            'interviewer_name' => $interviewerName,
            'interviewer_user_id' => $employer->id,
            'outcome' => InterviewOutcome::Pending,
            'notes' => $data['notes'] ?? null,
        ], $employer);
    }

    public function sendMessage(Application $application, User $employer, string $body): void
    {
        $this->assertCanManage($employer, $application);
        $this->messaging->send($employer, $application, $body);
    }

    public function select(Application $application, User $employer, ?string $notes = null): Application
    {
        $this->assertCanManage($employer, $application);

        return $this->pipeline->selectForEmployer($application, $employer, $notes);
    }

    public function openPlacement(Application $application, User $employer, ?string $notes = null): Placement
    {
        $this->assertCanManage($employer, $application);

        return $this->placements->createForEmployerApplication($application, $employer, $notes);
    }

    public function offer(
        Application $application,
        User $employer,
        string $offeredAt,
        ?string $salary = null,
        ?string $notes = null,
    ): Placement {
        $placement = $this->requireOpenPlacement($application, $employer);

        return $this->placements->offer($placement, $employer, $offeredAt, $salary, $notes);
    }

    public function acceptOffer(Application $application, User $employer, ?string $notes = null): Placement
    {
        $placement = $this->requireOpenPlacement($application, $employer);

        return $this->placements->accept($placement, $employer, $notes);
    }

    public function confirmJoining(
        Application $application,
        User $employer,
        string $expectedJoiningDate,
        ?string $notes = null,
    ): Placement {
        $placement = $this->requireOpenPlacement($application, $employer);

        return $this->placements->confirmJoining($placement, $employer, $expectedJoiningDate, $notes);
    }

    public function recordJoining(
        Application $application,
        User $employer,
        string $actualJoiningDate,
        ?string $notes = null,
    ): Placement {
        $placement = $this->requireOpenPlacement($application, $employer);
        $joined = $this->placements->join($placement, $employer, $actualJoiningDate, $notes);
        if ($application->fresh()?->status !== ApplicationStatus::Placed) {
            $this->pipeline->changeStatus($application->fresh() ?? $application, ApplicationStatus::Placed, $employer);
        }

        return $joined;
    }

    private function requireOpenPlacement(Application $application, User $employer): Placement
    {
        $this->assertCanManage($employer, $application);
        $application->loadMissing('placements');
        $placement = $application->placements->sortByDesc('id')->first();
        if (! $placement instanceof Placement) {
            throw ValidationException::withMessages([
                'placement' => 'Open a placement before recording offer or joining steps.',
            ]);
        }

        return $placement;
    }
}
