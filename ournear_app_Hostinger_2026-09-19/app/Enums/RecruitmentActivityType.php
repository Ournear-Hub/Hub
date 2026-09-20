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
}
