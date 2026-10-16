<?php

namespace App\Services;

use App\Enums\RecruiterOperationalFollowUpType;
use App\Enums\RecruitmentActivityType;
use App\Enums\RecruitmentOperationalVisibility;
use App\Models\EmployerProfile;
use App\Models\RecruiterTalentPoolAssignment;
use App\Models\RecruitmentActivity;
use App\Models\TalentPoolCompanySubmission;
use App\Models\User;
use App\Support\RecruiterOperationalAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruiterOperationalFollowUpService
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
     *     employer_profile_id?: int|null,
     *     talent_pool_company_submission_id?: int|null,
     *     recruiter_talent_pool_assignment_id?: int|null,
     * } $payload
     */
    public function record(User $actor, array $payload): RecruitmentActivity
    {
        $visibility = $payload['visibility'] ?? RecruitmentOperationalVisibility::Internal;
        $summary = trim($payload['summary']);
        if ($summary === '') {
            throw ValidationException::withMessages(['summary' => 'Enter follow-up details.']);
        }

        $submission = isset($payload['talent_pool_company_submission_id'])
            ? TalentPoolCompanySubmission::query()->find($payload['talent_pool_company_submission_id'])
            : null;
        $employer = isset($payload['employer_profile_id'])
            ? EmployerProfile::query()->find($payload['employer_profile_id'])
            : null;
        $assignment = isset($payload['recruiter_talent_pool_assignment_id'])
            ? RecruiterTalentPoolAssignment::query()->find($payload['recruiter_talent_pool_assignment_id'])
            : null;

        if ($submission instanceof TalentPoolCompanySubmission) {
            if (! RecruiterOperationalAccess::canManageSubmissionOperational($actor, $submission)) {
                throw ValidationException::withMessages(['submission' => 'You are not authorized to record follow-ups for this submission.']);
            }
            $employer = $submission->employerProfile;
        } elseif ($employer instanceof EmployerProfile) {
            if (! RecruiterOperationalAccess::canManageEmployerOperational($actor, $employer)) {
                throw ValidationException::withMessages(['employer' => 'You are not authorized to record follow-ups for this company.']);
            }
        } elseif ($assignment instanceof RecruiterTalentPoolAssignment) {
            if (! RecruiterOperationalAccess::canManageAssignmentOperational($actor, $assignment)) {
                throw ValidationException::withMessages(['assignment' => 'You are not authorized to record follow-ups for this assignment.']);
            }
        } else {
            throw ValidationException::withMessages(['context' => 'Link the follow-up to a company, submission, or assignment.']);
        }

        if ($visibility === RecruitmentOperationalVisibility::EmployerShared
            && ! $submission instanceof TalentPoolCompanySubmission) {
            throw ValidationException::withMessages([
                'visibility' => 'Employer-shared follow-ups must be linked to a Talent Pool company submission.',
            ]);
        }

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

        return DB::transaction(function () use ($actor, $summary, $visibility, $meta, $employer, $submission, $assignment) {
            return $this->activities->record(
                actor: $actor,
                type: RecruitmentActivityType::TalentPoolFollowUpRecorded,
                body: $summary,
                employer: $employer,
                talentPoolSubmission: $submission,
                talentPoolAssignment: $assignment,
                visibility: $visibility,
                operationalMeta: $meta,
            );
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function timelineForViewer(User $viewer, ?TalentPoolCompanySubmission $submission = null, ?EmployerProfile $employer = null): array
    {
        $query = RecruitmentActivity::query()
            ->with('actor:id,name')
            ->where('type', RecruitmentActivityType::TalentPoolFollowUpRecorded->value)
            ->latest('id');

        if ($submission instanceof TalentPoolCompanySubmission) {
            if (! RecruiterOperationalAccess::canViewSubmissionOperational($viewer, $submission)) {
                return [];
            }
            $query->where('talent_pool_company_submission_id', $submission->id);
        } elseif ($employer instanceof EmployerProfile) {
            if (! RecruiterOperationalAccess::canManageEmployerOperational($viewer, $employer)
                && ! $viewer->hasPermission(\App\Enums\PermissionName::EmployersView->value)) {
                return [];
            }
            $query->where('employer_profile_id', $employer->id)
                ->whereNull('talent_pool_company_submission_id');
        } else {
            return [];
        }

        if (! RecruiterOperationalAccess::canViewInternalOperational($viewer)) {
            $query->where('visibility', RecruitmentOperationalVisibility::EmployerShared->value);
        } elseif ($viewer->canAccessHiringWorkspace() && ! $viewer->isAdmin()) {
            $query->where('visibility', RecruitmentOperationalVisibility::EmployerShared->value);
        }

        return $query->get()->map(fn (RecruitmentActivity $row) => $row->toOperationalArray())->values()->all();
    }
}
