<?php

namespace App\Enums;

enum RecruitmentActivityType: string
{
    case StatusChanged = 'status_changed';
    case NoteAdded = 'note_added';
    case InterviewScheduled = 'interview_scheduled';
    case InterviewUpdated = 'interview_updated';
    case MarkedSuitable = 'marked_suitable';
    case MarkedNotSuitable = 'marked_not_suitable';
    case Shortlisted = 'shortlisted';
    case EmployerAssigned = 'employer_assigned';
    case CandidateSubmitted = 'candidate_submitted';
    case SubmissionStatusChanged = 'submission_status_changed';
    case EmployerSelected = 'employer_selected';
    case EmployerRejected = 'employer_rejected';
    case PlacementCreated = 'placement_created';
    case OfferCreated = 'offer_created';
    case OfferAccepted = 'offer_accepted';
    case OfferDeclined = 'offer_declined';
    case OfferWithdrawn = 'offer_withdrawn';
    case JoiningConfirmed = 'joining_confirmed';
    case CandidateJoined = 'candidate_joined';
    case ReplacementRequested = 'replacement_requested';
    case ReplacementCandidateIdentified = 'replacement_candidate_identified';
    case ReplacementPlacementLinked = 'replacement_placement_linked';
    case ReplacementCompleted = 'replacement_completed';
    case ReplacementCancelled = 'replacement_cancelled';
}
