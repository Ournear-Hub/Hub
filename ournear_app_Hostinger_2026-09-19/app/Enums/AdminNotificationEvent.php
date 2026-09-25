<?php

namespace App\Enums;

enum AdminNotificationEvent: string
{
    case ApplicationReceived = 'application_received';
    case FreelancerReceived = 'freelancer_received';
    case CandidateShortlisted = 'candidate_shortlisted';
    case SubmissionCreated = 'submission_created';
    case EmployerSelected = 'employer_selected';
    case EmployerRejected = 'employer_rejected';
    case PlacementCreated = 'placement_created';
    case OfferCreated = 'offer_created';
    case OfferAccepted = 'offer_accepted';
    case OfferDeclined = 'offer_declined';
    case JoiningConfirmed = 'joining_confirmed';
    case EmployeeJoined = 'employee_joined';
    case ReplacementRequested = 'replacement_requested';
    case ReplacementCandidateIdentified = 'replacement_candidate_identified';
    case ReplacementCompleted = 'replacement_completed';
    case ReplacementCancelled = 'replacement_cancelled';
    case InvoiceIssued = 'invoice_issued';
    case PaymentReceived = 'payment_received';
    case InvoiceCancelled = 'invoice_cancelled';

    public function category(): string
    {
        return match ($this) {
            self::ApplicationReceived => 'Application',
            self::FreelancerReceived => 'Freelancer',
            self::CandidateShortlisted => 'Shortlisted',
            self::SubmissionCreated => 'Employer Submission',
            self::EmployerSelected, self::EmployerRejected => 'Selection',
            self::PlacementCreated => 'Placement',
            self::OfferCreated, self::OfferAccepted, self::OfferDeclined => 'Offer',
            self::JoiningConfirmed, self::EmployeeJoined => 'Joining',
            self::ReplacementRequested, self::ReplacementCandidateIdentified, self::ReplacementCompleted, self::ReplacementCancelled => 'Replacement',
            self::InvoiceIssued, self::PaymentReceived, self::InvoiceCancelled => 'Payment',
        };
    }

    public function label(): string
    {
        return str_replace('_', ' ', $this->value);
    }

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return array_values(array_unique(array_map(
            fn (self $event) => $event->category(),
            self::cases(),
        )));
    }

    /**
     * @return list<string>
     */
    public static function valuesForCategory(string $category): array
    {
        return array_values(array_map(
            fn (self $event) => $event->value,
            array_filter(self::cases(), fn (self $event) => $event->category() === $category),
        ));
    }
}
