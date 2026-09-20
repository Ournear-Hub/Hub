<?php

namespace App\Enums;

enum AdminNotificationEvent: string
{
    case ApplicationReceived = 'application_received';
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
}
