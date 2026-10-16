<?php

namespace App\Enums;

enum StaffingCandidateSubmissionStatus: string
{
    case SubmittedToClient = 'submitted_to_client';
    case UnderClientReview = 'under_client_review';
    case InterviewInProgress = 'interview_in_progress';
    case ClientSelected = 'client_selected';
    case OfferInProgress = 'offer_in_progress';
    case JoinedConfirmed = 'joined_confirmed';
    case ClientRejected = 'client_rejected';
    case DidNotJoin = 'did_not_join';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::SubmittedToClient => 'Submitted to client',
            self::UnderClientReview => 'Under client review',
            self::InterviewInProgress => 'Interview in progress',
            self::ClientSelected => 'Client selected',
            self::OfferInProgress => 'Offer in progress',
            self::JoinedConfirmed => 'Joined (confirmed)',
            self::ClientRejected => 'Rejected by client',
            self::DidNotJoin => 'Did not join',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::JoinedConfirmed,
            self::ClientRejected,
            self::DidNotJoin,
            self::Withdrawn,
        ], true);
    }

    public function countsTowardFulfilment(): bool
    {
        return $this === self::JoinedConfirmed;
    }

    /**
     * @return list<self>
     */
    public function allowedClientTransitions(): array
    {
        if ($this->isTerminal()) {
            return [];
        }

        return match ($this) {
            self::SubmittedToClient => [
                self::UnderClientReview,
                self::InterviewInProgress,
                self::ClientRejected,
            ],
            self::UnderClientReview => [
                self::InterviewInProgress,
                self::ClientSelected,
                self::ClientRejected,
            ],
            self::InterviewInProgress => [
                self::ClientSelected,
                self::ClientRejected,
            ],
            self::ClientSelected => [
                self::OfferInProgress,
                self::ClientRejected,
                self::DidNotJoin,
            ],
            self::OfferInProgress => [
                self::JoinedConfirmed,
                self::DidNotJoin,
                self::ClientRejected,
            ],
            default => [],
        };
    }
}
