<?php

namespace App\Enums;

enum EmployerNotificationEvent: string
{
    case ApplicationReceived = 'employer_application_received';
    case InterviewCandidateResponded = 'employer_interview_candidate_responded';
    case ApplicationMessageReceived = 'employer_application_message_received';
    case CorrectionRequestSubmitted = 'employer_correction_request_submitted';
    case TalentPoolAssignmentReceived = 'employer_talent_pool_assignment_received';
    case TalentPoolSubmissionReceived = 'employer_talent_pool_submission_received';
    case TalentPoolSubmissionMessageReceived = 'employer_talent_pool_submission_message_received';
    case StaffingAccessApproved = 'employer_staffing_access_approved';
    case StaffingAccessRejected = 'employer_staffing_access_rejected';
    case StaffingCandidateSubmitted = 'employer_staffing_candidate_submitted';
    case StaffingSubmissionMessageReceived = 'employer_staffing_submission_message_received';
    case StaffingJoiningConfirmed = 'employer_staffing_joining_confirmed';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationReceived => 'New application',
            self::InterviewCandidateResponded => 'Interview response',
            self::ApplicationMessageReceived => 'Application message',
            self::CorrectionRequestSubmitted => 'Correction request',
            self::TalentPoolAssignmentReceived => 'Talent Pool assignment',
            self::TalentPoolSubmissionReceived => 'OurNear Talent Pool submission',
            self::TalentPoolSubmissionMessageReceived => 'Talent Pool message',
            self::StaffingAccessApproved => 'Staffing access approved',
            self::StaffingAccessRejected => 'Staffing access rejected',
            self::StaffingCandidateSubmitted => 'Staffing candidate submitted',
            self::StaffingSubmissionMessageReceived => 'Staffing message',
            self::StaffingJoiningConfirmed => 'Staffing joining confirmed',
        };
    }
}
