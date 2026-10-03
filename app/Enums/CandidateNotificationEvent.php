<?php

namespace App\Enums;

enum CandidateNotificationEvent: string
{
    case ApplicationReceived = 'application_received';
    case ApplicationStatusChanged = 'application_status_changed';
    case ApplicationShortlisted = 'application_shortlisted';
    case ApplicationSelected = 'application_selected';
    case ApplicationRejected = 'application_rejected';
    case InterviewScheduled = 'interview_scheduled';
    case InterviewRescheduled = 'interview_rescheduled';
    case InterviewCancelled = 'interview_cancelled';
    case InterviewReminder = 'interview_reminder';
    case ProfileIncomplete = 'profile_incomplete';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationReceived => 'Application received',
            self::ApplicationStatusChanged => 'Application update',
            self::ApplicationShortlisted => 'Shortlisted',
            self::ApplicationSelected => 'Selected',
            self::ApplicationRejected => 'Unsuccessful',
            self::InterviewScheduled => 'Interview scheduled',
            self::InterviewRescheduled => 'Interview rescheduled',
            self::InterviewCancelled => 'Interview cancelled',
            self::InterviewReminder => 'Interview reminder',
            self::ProfileIncomplete => 'Complete your profile',
        };
    }
}
