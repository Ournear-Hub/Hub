<?php

namespace App\Enums;

enum RecruiterTalentPoolAssignmentProgressStage: string
{
    case Assigned = 'assigned';
    case ContactPending = 'contact_pending';
    case Contacted = 'contacted';
    case ScreeningInProgress = 'screening_in_progress';
    case Shortlisted = 'shortlisted';
    case SubmittedToEmployer = 'submitted_to_employer';
    case InterviewScheduled = 'interview_scheduled';
    case SelectedOffer = 'selected_offer';
    case JoinedPlaced = 'joined_placed';
    case Rejected = 'rejected';
    case OnHold = 'on_hold';
    case UnableToContact = 'unable_to_contact';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::ContactPending => 'Contact Pending',
            self::Contacted => 'Contacted',
            self::ScreeningInProgress => 'Screening in Progress',
            self::Shortlisted => 'Shortlisted',
            self::SubmittedToEmployer => 'Submitted to Employer',
            self::InterviewScheduled => 'Interview Scheduled',
            self::SelectedOffer => 'Selected / Offer',
            self::JoinedPlaced => 'Joined / Placed',
            self::Rejected => 'Rejected',
            self::OnHold => 'On Hold',
            self::UnableToContact => 'Unable to Contact',
        };
    }

    public function syncCoarseStatus(): RecruiterTalentPoolAssignmentStatus
    {
        return match ($this) {
            self::Assigned => RecruiterTalentPoolAssignmentStatus::PendingReview,
            self::JoinedPlaced, self::SelectedOffer => RecruiterTalentPoolAssignmentStatus::Completed,
            self::Rejected, self::UnableToContact => RecruiterTalentPoolAssignmentStatus::Declined,
            default => RecruiterTalentPoolAssignmentStatus::InProgress,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::JoinedPlaced,
            self::SelectedOffer,
            self::Rejected,
            self::UnableToContact,
        ], true);
    }

    /**
     * @return list<self>
     */
    public static function recruiterOptions(): array
    {
        return self::cases();
    }

    /**
     * @return list<self>
     */
    public function allowedRecruiterTransitions(): array
    {
        if ($this->isTerminal()) {
            return [];
        }

        return array_values(array_filter(
            self::cases(),
            fn (self $stage) => ! $stage->isTerminal() || $stage === $this,
        ));
    }
}
