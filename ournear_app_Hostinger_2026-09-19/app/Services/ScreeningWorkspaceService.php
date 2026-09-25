<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Enums\EmployerSubmissionStatus;
use App\Enums\RecruitmentActivityType;
use App\Enums\ScreeningDecision;
use App\Enums\ScreeningStage;
use App\Models\Application;
use App\Models\Employee;
use App\Models\Job;
use App\Models\RecruitmentActivity;

class ScreeningWorkspaceService
{
    public const NOT_RECORDED = 'Not recorded yet';

    public const NOT_AVAILABLE = 'Not available in V1.1';

    /**
     * @var list<array{key: string, label: string}>
     */
    private const DOCUMENT_STAGES = [
        ['key' => 'payslip_verified', 'label' => 'Payslip Verified'],
        ['key' => 'employment_verified', 'label' => 'Employment / Experience Verified'],
        ['key' => 'qualification_verified', 'label' => 'Qualification Verified'],
        ['key' => 'certificate_verified', 'label' => 'Certificate Verified'],
        ['key' => 'licence_verified', 'label' => 'Licence Verified'],
        ['key' => 'other_documents_verified', 'label' => 'Other Documents Verified'],
    ];

    public function __construct(
        private ScreeningMatchService $match,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function jobSummary(?int $jobId): ?array
    {
        if ($jobId === null || $jobId <= 0) {
            return null;
        }

        $job = Job::query()
            ->with(['employer.user', 'classifiedLocation'])
            ->find($jobId);

        if ($job === null) {
            return null;
        }

        $applications = Application::query()
            ->where('job_id', $job->id)
            ->with(['screening', 'submissions'])
            ->get();

        $pending = $applications->filter(fn (Application $application) => $this->isPendingScreening($application))->count();
        $matched = $applications->filter(fn (Application $application) => $this->isMatched($application))->count();
        $shortlisted = $applications->filter(fn (Application $application) => $this->isShortlisted($application))->count();
        $rejected = $applications->filter(fn (Application $application) => $this->isRejected($application))->count();
        $onHold = $applications->filter(fn (Application $application) => $this->isOnHold($application))->count();
        $submitted = $applications->filter(fn (Application $application) => $application->submissions->isNotEmpty())->count();
        $selected = $applications->filter(fn (Application $application) => $application->status === ApplicationStatus::Selected)->count();
        $placements = $job->placements()->count();
        $joined = Employee::query()->where('job_id', $job->id)->count();

        $jobQuery = ['job_id' => (string) $job->id];

        return [
            'id' => $job->id,
            'business_id' => $job->business_id,
            'title' => $job->title,
            'slug' => $job->slug,
            'employer' => $job->employer?->toAdminSummary(),
            'location' => $job->displayLocation(),
            'vacancy' => $job->vacancy,
            'applications_count' => $applications->count(),
            'pending_screening_count' => $pending,
            'matched_count' => $matched,
            'shortlisted_count' => $shortlisted,
            'rejected_count' => $rejected,
            'on_hold_count' => $onHold,
            'pipeline' => [
                $this->pipelineStage('applications', 'Applications', $applications->count(), '/admin/applications?'.http_build_query($jobQuery)),
                $this->pipelineStage('screening_pending', 'Screening Pending', $pending, '/admin/screening?'.http_build_query($jobQuery + ['screening_decision' => ScreeningDecision::Pending->value])),
                $this->pipelineStage('matched', 'Matched', $matched, '/admin/screening?'.http_build_query($jobQuery + ['screening_decision' => ScreeningDecision::Suitable->value])),
                $this->pipelineStage('shortlisted', 'Shortlisted', $shortlisted, '/admin/shortlisted?'.http_build_query($jobQuery)),
                $this->pipelineStage('employer_submission', 'Employer Submission', $submitted, '/admin/submissions?'.http_build_query($jobQuery)),
                $this->pipelineStage('selected', 'Selected', $selected, '/admin/applications?'.http_build_query($jobQuery + ['status' => ApplicationStatus::Selected->value])),
                $this->pipelineStage('placement', 'Placement', $placements, '/admin/placements?'.http_build_query($jobQuery)),
                $this->pipelineStage('joined', 'Joined', $joined, '/admin/employees?'.http_build_query($jobQuery)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function enrichListItem(Application $application): array
    {
        $payload = $application->toAdminListArray();
        $match = $this->match->evaluate($application);
        $submission = $this->submissionState($application);
        $verification = $this->verificationState($application);
        $stage = $application->screeningStage();

        $application->loadMissing([
            'user.candidateProfile.skills',
            'user.candidateProfile.industry',
        ]);
        $profile = $application->user?->candidateProfile;
        $notice = $profile?->notice_period;

        return array_merge($payload, [
            'match' => $match,
            'match_percent' => $match['percent'],
            'match_label' => $match['label'],
            'screening_stage' => $stage->value,
            'screening_stage_label' => $stage->label(),
            'last_screening_activity' => $this->lastActivitySummary($application),
            'skills' => $profile?->skillsDisplay(),
            'current_company' => $profile?->current_company,
            'designation' => $profile?->current_designation,
            'current_salary' => $profile?->formatSalary($profile->current_salary),
            'expected_salary' => $profile?->formatSalary($profile->expected_salary),
            'notice_period' => $notice?->label(),
            'industry_experience' => $profile?->industry?->name,
            'industry' => $application->job?->displayIndustry(),
            'screening_notes' => $application->screening?->notes,
            'verification_status' => $verification['status'],
            'verification_label' => $verification['label'],
            'employer_submission_state' => $submission['state'],
            'employer_submission_label' => $submission['label'],
            'can_start_screening' => $application->canStartScreening(),
            'can_contact' => $application->canRecordScreeningProgress(),
            'can_mark_called' => $application->canRecordScreeningProgress(),
            'can_mark_interviewed' => $application->canRecordScreeningProgress(),
            'can_hold' => $application->canHoldScreening(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function enrichSelected(Application $application, array $payload): array
    {
        $enriched = array_merge($this->enrichListItem($application), $payload);

        $enriched['screening_stages'] = $this->stageBoard($application);
        $enriched['screening_timeline'] = $this->timeline($application);
        $enriched['document_verifications'] = $this->documentVerifications();
        $enriched['match'] = $this->match->evaluate($application);
        $enriched['employer_submission_state'] = $enriched['employer_submission_state'] ?? $this->submissionState($application)['state'];
        $enriched['employer_submission_label'] = $enriched['employer_submission_label'] ?? $this->submissionState($application)['label'];

        if (is_array($enriched['screening'] ?? null)) {
            $screening = $application->screening;
            $enriched['screening']['stage'] = $application->screeningStage()->value;
            $enriched['screening']['stage_label'] = $application->screeningStage()->label();
            $enriched['screening']['held_by'] = $screening?->holder?->name;
            $enriched['screening']['held_at'] = $screening?->held_at?->toDateTimeString();
        }

        return $enriched;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function stageBoard(Application $application): array
    {
        $application->loadMissing(['screening.screener', 'screening.shortlister', 'screening.holder', 'interview', 'submissions']);

        $screening = $application->screening;
        $stage = $application->screeningStage();
        $rows = [];

        $rows[] = $this->operationalStage(
            'matched',
            'Matched',
            $this->hasReached($stage, [ScreeningStage::Matched, ScreeningStage::Contacted, ScreeningStage::Called, ScreeningStage::Interviewed, ScreeningStage::Shortlisted, ScreeningStage::Submitted])
                || $application->screeningDecision() === ScreeningDecision::Suitable
                || $application->screeningDecision() === ScreeningDecision::Shortlisted,
            $screening?->screened_at?->toDateTimeString(),
            $screening?->screener?->name,
            $screening?->notes,
            null,
        );

        $rows[] = $this->operationalStage(
            'shortlisted',
            'Shortlisted',
            $this->isShortlisted($application),
            $screening?->shortlisted_at?->toDateTimeString(),
            $screening?->shortlister?->name,
            $screening?->notes,
            null,
        );

        $contacted = $this->latestActivity($application, RecruitmentActivityType::CandidateContacted);
        $rows[] = $this->operationalStage(
            'contacted',
            'Contacted',
            $contacted !== null || $this->hasReached($stage, [ScreeningStage::Contacted, ScreeningStage::Called, ScreeningStage::Interviewed]),
            $contacted?->created_at?->toDateTimeString(),
            $contacted?->actor?->name,
            $contacted?->body,
            null,
        );

        $called = $this->latestActivity($application, RecruitmentActivityType::CandidateCalled);
        $rows[] = $this->operationalStage(
            'called',
            'Called',
            $called !== null || $this->hasReached($stage, [ScreeningStage::Called, ScreeningStage::Interviewed]),
            $called?->created_at?->toDateTimeString(),
            $called?->actor?->name,
            $called?->body,
            null,
        );

        $interviewed = $this->latestActivity($application, RecruitmentActivityType::CandidateInterviewed);
        $interview = $application->interview;
        $rows[] = $this->operationalStage(
            'interviewed',
            'Interviewed',
            $interviewed !== null || $stage === ScreeningStage::Interviewed || $interview !== null,
            $interviewed?->created_at?->toDateTimeString() ?? $interview?->scheduled_at?->toDateTimeString(),
            $interviewed?->actor?->name ?? $interview?->interviewer_name,
            $interviewed?->body ?? $interview?->notes,
            $interview ? 'Interview record' : null,
        );

        foreach (self::DOCUMENT_STAGES as $document) {
            $rows[] = [
                'key' => $document['key'],
                'label' => $document['label'],
                'available' => false,
                'completed' => false,
                'status' => self::NOT_AVAILABLE,
                'occurred_at' => null,
                'performed_by' => null,
                'remarks' => null,
                'evidence' => self::NOT_AVAILABLE,
            ];
        }

        $submission = $application->submissions->sortByDesc('id')->first();
        $rows[] = $this->operationalStage(
            'employer_submission',
            'Employer Submission',
            $submission !== null,
            $submission?->submitted_at?->toDateTimeString(),
            null,
            $submission?->notes,
            $submission?->business_id,
        );

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function timeline(Application $application): array
    {
        $application->loadMissing(['activities.actor', 'screening.screener', 'submissions']);

        $items = [[
            'id' => 'received-'.$application->id,
            'occurred_at' => $application->created_at?->toDateTimeString(),
            'action' => 'Application received',
            'status' => ApplicationStatus::New->value,
            'actor_name' => null,
            'remarks' => null,
        ]];

        foreach ($application->activities as $activity) {
            $items[] = [
                'id' => 'activity-'.$activity->id,
                'occurred_at' => $activity->created_at?->toDateTimeString(),
                'action' => $this->activityLabel($activity),
                'status' => $activity->to_status ?: $application->screeningStage()->label(),
                'actor_name' => $activity->actor?->name,
                'remarks' => $activity->body,
            ];
        }

        usort($items, function (array $left, array $right): int {
            return strcmp((string) $left['occurred_at'], (string) $right['occurred_at']);
        });

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function documentVerifications(): array
    {
        return array_map(fn (array $document) => [
            'key' => $document['key'],
            'label' => $document['label'],
            'status' => self::NOT_AVAILABLE,
        ], self::DOCUMENT_STAGES);
    }

    public function isPendingScreening(Application $application): bool
    {
        if ($this->isOnHold($application) || $this->isRejected($application) || $this->isShortlisted($application)) {
            return false;
        }

        return $application->screeningDecision() === ScreeningDecision::Pending
            && in_array($application->screeningStage(), [ScreeningStage::Pending], true);
    }

    public function isMatched(Application $application): bool
    {
        if ($this->isShortlisted($application) || $this->isRejected($application)) {
            return false;
        }

        return $application->screeningDecision() === ScreeningDecision::Suitable;
    }

    public function isShortlisted(Application $application): bool
    {
        return $application->status === ApplicationStatus::Shortlisted
            || $application->screeningDecision() === ScreeningDecision::Shortlisted
            || $application->screeningStage() === ScreeningStage::Shortlisted
            || $application->screeningStage() === ScreeningStage::Submitted;
    }

    public function isRejected(Application $application): bool
    {
        return $application->status === ApplicationStatus::Rejected
            || $application->screeningDecision() === ScreeningDecision::NotSuitable
            || $application->screeningStage() === ScreeningStage::Rejected;
    }

    public function isOnHold(Application $application): bool
    {
        return $application->screeningStage() === ScreeningStage::Held;
    }

    /**
     * @return array{state: string, label: string}
     */
    private function submissionState(Application $application): array
    {
        $application->loadMissing('submissions');

        $latest = $application->submissions->sortByDesc('id')->first();

        if ($latest !== null) {
            if (in_array($latest->status, [
                EmployerSubmissionStatus::Selected,
                EmployerSubmissionStatus::Rejected,
                EmployerSubmissionStatus::OnHold,
            ], true)) {
                return [
                    'state' => 'employer_response',
                    'label' => 'Has employer response',
                ];
            }

            return [
                'state' => 'submitted',
                'label' => 'Already submitted',
            ];
        }

        if ($application->submitBlockedReason() === null) {
            return [
                'state' => 'ready_for_submission',
                'label' => 'Ready for employer submission',
            ];
        }

        if ($this->isShortlisted($application)) {
            return [
                'state' => 'shortlisted',
                'label' => 'Shortlisted',
            ];
        }

        return [
            'state' => 'not_shortlisted',
            'label' => 'Has not been shortlisted',
        ];
    }

    /**
     * @return array{status: string, label: string}
     */
    private function verificationState(Application $application): array
    {
        $application->loadMissing('user');

        if ($application->user?->email_verified_at) {
            return [
                'status' => 'verified',
                'label' => 'Verified',
            ];
        }

        if ($application->user) {
            return [
                'status' => 'unverified',
                'label' => 'Unverified',
            ];
        }

        return [
            'status' => 'not_recorded',
            'label' => self::NOT_RECORDED,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lastActivitySummary(Application $application): ?array
    {
        $activity = $this->latestLoadedActivity($application);

        if ($activity === null) {
            $screening = $application->screening;
            if ($screening?->held_at) {
                return [
                    'at' => $screening->held_at->toDateTimeString(),
                    'action' => 'Put on hold',
                    'actor_name' => $screening->holder?->name,
                ];
            }

            if ($screening?->shortlisted_at) {
                return [
                    'at' => $screening->shortlisted_at->toDateTimeString(),
                    'action' => 'Shortlisted',
                    'actor_name' => $screening->shortlister?->name,
                ];
            }

            if ($screening?->screened_at) {
                return [
                    'at' => $screening->screened_at->toDateTimeString(),
                    'action' => 'Screening updated',
                    'actor_name' => $screening->screener?->name,
                ];
            }

            return null;
        }

        return [
            'at' => $activity->created_at?->toDateTimeString(),
            'action' => $this->activityLabel($activity),
            'actor_name' => $activity->actor?->name,
        ];
    }

    private function latestLoadedActivity(Application $application): ?RecruitmentActivity
    {
        if ($application->relationLoaded('activities')) {
            return $application->activities->sortByDesc('id')->first();
        }

        return $application->activities()->with('actor:id,name')->latest('id')->first();
    }

    private function latestActivity(Application $application, RecruitmentActivityType $type): ?RecruitmentActivity
    {
        if ($application->relationLoaded('activities')) {
            return $application->activities
                ->filter(fn (RecruitmentActivity $activity) => $activity->type === $type)
                ->sortByDesc('id')
                ->first();
        }

        return $application->activities()
            ->with('actor:id,name')
            ->where('type', $type)
            ->latest('id')
            ->first();
    }

    /**
     * @param  list<ScreeningStage>  $stages
     */
    private function hasReached(ScreeningStage $current, array $stages): bool
    {
        return in_array($current, $stages, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function operationalStage(
        string $key,
        string $label,
        bool $completed,
        ?string $occurredAt,
        ?string $performedBy,
        ?string $remarks,
        ?string $evidence,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'available' => true,
            'completed' => $completed,
            'status' => $completed ? 'Completed' : 'Not started',
            'occurred_at' => $occurredAt,
            'performed_by' => $performedBy,
            'remarks' => $remarks,
            'evidence' => $evidence,
        ];
    }

    /**
     * @return array{key: string, label: string, value: int, href: string}
     */
    private function pipelineStage(string $key, string $label, int $value, string $href): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'href' => $href,
        ];
    }

    private function activityLabel(RecruitmentActivity $activity): string
    {
        return match ($activity->type) {
            RecruitmentActivityType::ScreeningStarted => 'Screening started',
            RecruitmentActivityType::MarkedSuitable => 'Matched',
            RecruitmentActivityType::CandidateContacted => 'Candidate contacted',
            RecruitmentActivityType::CandidateCalled => 'Call completed',
            RecruitmentActivityType::CandidateInterviewed => 'Interview completed',
            RecruitmentActivityType::ScreeningHeld => 'Put on hold',
            RecruitmentActivityType::Shortlisted => 'Shortlisted',
            RecruitmentActivityType::CandidateSubmitted => 'Employer submitted',
            RecruitmentActivityType::MarkedNotSuitable => 'Rejected',
            RecruitmentActivityType::StatusChanged => 'Status: '.($activity->from_status ?? '—').' → '.($activity->to_status ?? '—'),
            default => $activity->body ?: str_replace('_', ' ', $activity->type->value),
        };
    }
}
