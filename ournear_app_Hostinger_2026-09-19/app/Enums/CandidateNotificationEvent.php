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

    public function label(): string
    {
        return match ($this) {
            self::ApplicationReceived => 'Application received',
            self::ApplicationStatusChanged => 'Application update',
            self::ApplicationShortlisted => 'Shortlisted',
            self::ApplicationSelected => 'Selected',
            self::ApplicationRejected => 'Unsuccessful',
            self::InterviewScheduled => 'Interview scheduled',
        };
    }
}
