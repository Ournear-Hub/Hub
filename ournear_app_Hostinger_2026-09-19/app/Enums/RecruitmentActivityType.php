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
}
