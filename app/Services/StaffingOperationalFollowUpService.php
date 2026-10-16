<?php

namespace App\Services;

use App\Enums\RecruiterOperationalFollowUpType;
use App\Enums\RecruitmentActivityType;
use App\Enums\RecruitmentOperationalVisibility;
use App\Models\RecruitmentActivity;
use App\Models\StaffingCandidateSubmission;
use App\Models\User;
use App\Support\RecruiterOperationalAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffingOperationalFollowUpService
{
    public function __construct(
        private RecruitmentActivityService $activities,
    ) {}

    /**
     * @param  array{
     *     follow_up_type: RecruiterOperationalFollowUpType,
     *     occurred_at?: Carbon|null,
     *     summary: string,
     *     outcome?: string|null,
     *     issue?: string|null,
     *     next_action?: string|null,
     *     next_action_due_at?: Carbon|null,
     *     visibility?: RecruitmentOperationalVisibility,
     * } $payload
     */
    public function record(User $actor, StaffingCandidateSubmission $submission, array $payload): RecruitmentActivity
    {
        if (! RecruiterOperationalAccess::canManageStaffingSubmissionOperational($actor, $submission)) {
            throw ValidationException::withMessages([
                'summary' => 'You are not authorized to record follow-ups for this staffing submission.',
            ]);
        }

        $visibility = $payload['visibility'] ?? RecruitmentOperationalVisibility::Internal;
        $summary = trim($payload['summary']);
        if ($summary === '') {
            throw ValidationException::withMessages(['summary' => 'Enter follow-up details.']);
        }

        $submission->loadMissing(['employerEnquiry.user.employerProfile', 'assignment']);

        $meta = [
            'follow_up_type' => $payload['follow_up_type']->value,
            'follow_up_type_label' => $payload['follow_up_type']->label(),
            'occurred_at' => ($payload['occurred_at'] ?? now())->toDateTimeString(),
            'outcome' => $payload['outcome'] ?? null,
            'issue' => $payload['issue'] ?? null,
            'next_action' => $payload['next_action'] ?? null,
            'next_action_due_at' => isset($payload['next_action_due_at'])
                ? $payload['next_action_due_at']->toDateTimeString()
                : null,
        ];

        return DB::transaction(function () use ($actor, $submission, $summary, $visibility, $meta) {
            return $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::StaffingFollowUpRecorded,
                enquiry: $submission->employerEnquiry,
                body: $summary,
                employer: $submission->employerEnquiry?->user?->employerProfile,
                staffingSubmission: $submission,
                talentPoolAssignment: $submission->assignment,
                visibility: $visibility,
                operationalMeta: $meta,
            );
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function timelineForViewer(User $viewer, StaffingCandidateSubmission $submission): array
    {
        if (! RecruiterOperationalAccess::canViewStaffingSubmissionOperational($viewer, $submission)) {
            return [];
        }

        $query = RecruitmentActivity::query()
            ->with('actor:id,name')
            ->where('staffing_candidate_submission_id', $submission->id)
            ->where('type', RecruitmentActivityType::StaffingFollowUpRecorded->value)
            ->latest('id');

        if (! RecruiterOperationalAccess::canViewInternalOperational($viewer)) {
            $query->where('visibility', RecruitmentOperationalVisibility::EmployerShared->value);
        }

        return $query->get()->map(fn (RecruitmentActivity $row) => $row->toOperationalArray())->values()->all();
    }
}
