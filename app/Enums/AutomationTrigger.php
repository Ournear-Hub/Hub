<?php

namespace App\Enums;

enum AutomationTrigger: string
{
    case ApplicationSubmitted = 'application_submitted';
    case ApplicationStatusChanged = 'application_status_changed';
    case InterviewScheduled = 'interview_scheduled';
    case InterviewCompleted = 'interview_completed';
    case CandidateSubmittedToEmployer = 'candidate_submitted_to_employer';
    case ClientReviewPending = 'client_review_pending';
    case ClientReviewCompleted = 'client_review_completed';
    case EmployerSelected = 'employer_selected';
    case PlacementCreated = 'placement_created';
    case PlacementJoined = 'placement_joined';
    case ReplacementRequested = 'replacement_requested';
    case InterviewApproaching = 'interview_approaching';
    case JoiningDue = 'joining_due';
    case ReplacementAging = 'replacement_aging';
    case ClientFollowupDue = 'client_followup_due';
    case InvoiceDue = 'invoice_due';
    case ApplicationStale = 'application_stale';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationSubmitted => 'Application submitted',
            self::ApplicationStatusChanged => 'Application status changed',
            self::InterviewScheduled => 'Interview scheduled',
            self::InterviewCompleted => 'Interview completed',
            self::CandidateSubmittedToEmployer => 'Candidate submitted to employer',
            self::ClientReviewPending => 'Client review pending',
            self::ClientReviewCompleted => 'Client review completed',
            self::EmployerSelected => 'Employer selected candidate',
            self::PlacementCreated => 'Placement created',
            self::PlacementJoined => 'Candidate joined',
            self::ReplacementRequested => 'Replacement requested',
            self::InterviewApproaching => 'Interview approaching',
            self::JoiningDue => 'Joining date approaching',
            self::ReplacementAging => 'Replacement ageing',
            self::ClientFollowupDue => 'Client follow-up due',
            self::InvoiceDue => 'Invoice due',
            self::ApplicationStale => 'Application stale in New',
        };
    }

    public function module(): string
    {
        return match ($this) {
            self::ApplicationSubmitted, self::ApplicationStale => 'screening',
            self::ApplicationStatusChanged => 'pipeline',
            self::InterviewScheduled, self::InterviewCompleted, self::InterviewApproaching => 'interview',
            self::CandidateSubmittedToEmployer, self::ClientReviewPending, self::ClientReviewCompleted, self::EmployerSelected, self::ClientFollowupDue => 'submission',
            self::PlacementCreated, self::PlacementJoined, self::JoiningDue, self::InvoiceDue => 'placement',
            self::ReplacementRequested, self::ReplacementAging => 'replacement',
        };
    }

    public function isScheduled(): bool
    {
        return in_array($this, [
            self::InterviewApproaching,
            self::JoiningDue,
            self::ReplacementAging,
            self::ClientFollowupDue,
            self::InvoiceDue,
            self::ApplicationStale,
        ], true);
    }

    public function defaultDelayHours(): int
    {
        return match ($this) {
            self::InterviewApproaching, self::JoiningDue => 24,
            self::ApplicationStale => 48,
            self::ReplacementAging, self::ClientFollowupDue, self::InvoiceDue => 72,
            default => 0,
        };
    }

    /**
     * @return list<array{value: string, label: string, module: string, scheduled: bool}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $trigger) => [
                'value' => $trigger->value,
                'label' => $trigger->label(),
                'module' => $trigger->module(),
                'scheduled' => $trigger->isScheduled(),
            ],
            self::cases(),
        );
    }
}
