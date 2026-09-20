<?php

namespace App\Enums;

enum RecruitmentActivityType: string
{
    case StatusChanged = 'status_changed';
    case NoteAdded = 'note_added';
    case InterviewScheduled = 'interview_scheduled';
    case InterviewUpdated = 'interview_updated';
}
