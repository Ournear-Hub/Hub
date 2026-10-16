<?php

namespace App\Enums;

enum SystemMessageTemplateKey: string
{
    case ApplicationReceivedInApp = 'recruitment.application_received.in_app';
    case ApplicationReviewingInApp = 'recruitment.application_reviewing.in_app';
    case ApplicationShortlistedInApp = 'recruitment.application_shortlisted.in_app';
    case ApplicationSelectedInApp = 'recruitment.application_selected.in_app';
    case ApplicationSelectedEmail = 'recruitment.application_selected.email';
    case ApplicationRejectedInApp = 'recruitment.application_rejected.in_app';
    case ApplicationRejectedEmail = 'recruitment.application_rejected.email';
    case InterviewScheduledInApp = 'recruitment.interview_scheduled.in_app';
    case InterviewScheduledEmail = 'recruitment.interview_scheduled.email';
    case InterviewRescheduledInApp = 'recruitment.interview_rescheduled.in_app';
    case InterviewCancelledInApp = 'recruitment.interview_cancelled.in_app';
    case InterviewReminderInApp = 'recruitment.interview_reminder.in_app';
    case EmployerApplicationReceivedInApp = 'recruitment.employer.application_received.in_app';
    case EmployerInterviewRespondedInApp = 'recruitment.employer.interview_candidate_responded.in_app';
    case EmployerApplicationMessageInApp = 'recruitment.employer.application_message_received.in_app';
    case EmployerCorrectionRequestInApp = 'recruitment.employer.correction_request_submitted.in_app';
    case EmployerApplicationReceivedEmail = 'recruitment.employer.application_received.email';
    case EmployerInterviewRespondedEmail = 'recruitment.employer.interview_candidate_responded.email';
    case EmployerApplicationMessageEmail = 'recruitment.employer.application_message_received.email';
    case EmployerCorrectionRequestEmail = 'recruitment.employer.correction_request_submitted.email';

    public function channel(): MessageTemplateChannel
    {
        return str_ends_with($this->value, '.email')
            ? MessageTemplateChannel::Email
            : MessageTemplateChannel::InApp;
    }

    public function audience(): MessageTemplateAudience
    {
        return str_contains($this->value, '.employer.')
            ? MessageTemplateAudience::Employer
            : MessageTemplateAudience::Candidate;
    }

    public function isRequired(): bool
    {
        return true;
    }

    /**
     * @return array{
     *     name: string,
     *     description: string,
     *     subject: string|null,
     *     body: string,
     *     variables: list<string>
     * }
     */
    public function defaults(): array
    {
        return match ($this) {
            self::ApplicationReceivedInApp => [
                'name' => 'Application received (in-app)',
                'description' => 'Sent to candidates when an application is submitted.',
                'subject' => null,
                'body' => 'We received your application for {{job_title}}.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'platform_name'],
            ],
            self::ApplicationReviewingInApp => [
                'name' => 'Application under review (in-app)',
                'description' => 'Sent when application status moves to reviewing.',
                'subject' => null,
                'body' => 'Your application for {{job_title}} is now being reviewed.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'application_status', 'platform_name'],
            ],
            self::ApplicationShortlistedInApp => [
                'name' => 'Application shortlisted (in-app)',
                'description' => 'Sent when a candidate is shortlisted.',
                'subject' => null,
                'body' => 'You have been shortlisted for {{job_title}}.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'platform_name'],
            ],
            self::ApplicationSelectedInApp => [
                'name' => 'Application selected (in-app)',
                'description' => 'Sent when a candidate is selected.',
                'subject' => null,
                'body' => 'You have been selected for {{job_title}}.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'company_name', 'platform_name'],
            ],
            self::ApplicationSelectedEmail => [
                'name' => 'Application selected (email)',
                'description' => 'Operational email when a candidate is selected.',
                'subject' => 'You have been selected',
                'body' => 'We are pleased to confirm that you have been selected for {{job_title}}. Our team will contact you with next steps.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'company_name', 'platform_name', 'support_email'],
            ],
            self::ApplicationRejectedInApp => [
                'name' => 'Application rejected (in-app)',
                'description' => 'Sent when an application is rejected.',
                'subject' => null,
                'body' => 'Your application for {{job_title}} was unsuccessful.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'platform_name'],
            ],
            self::ApplicationRejectedEmail => [
                'name' => 'Application rejected (email)',
                'description' => 'Operational email when an application is rejected.',
                'subject' => 'Update on your application',
                'body' => 'Thank you for applying for {{job_title}}. After careful review, we will not be progressing your application on this occasion.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'platform_name', 'support_email'],
            ],
            self::InterviewScheduledInApp => [
                'name' => 'Interview scheduled (in-app)',
                'description' => 'Sent when an interview is scheduled.',
                'subject' => null,
                'body' => 'An interview has been scheduled for {{job_title}}.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'interview_when', 'platform_name'],
            ],
            self::InterviewScheduledEmail => [
                'name' => 'Interview scheduled (email)',
                'description' => 'Operational email for scheduled interviews.',
                'subject' => 'Interview scheduled',
                'body' => 'An interview has been scheduled for {{job_title}}. {{interview_when}}',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'interview_when', 'platform_name', 'support_email'],
            ],
            self::InterviewRescheduledInApp => [
                'name' => 'Interview rescheduled (in-app)',
                'description' => 'Sent when an interview is rescheduled.',
                'subject' => null,
                'body' => 'Your interview for {{job_title}} has been rescheduled.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'interview_when', 'platform_name'],
            ],
            self::InterviewCancelledInApp => [
                'name' => 'Interview cancelled (in-app)',
                'description' => 'Sent when an interview is cancelled.',
                'subject' => null,
                'body' => 'Your interview for {{job_title}} was cancelled.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'platform_name'],
            ],
            self::InterviewReminderInApp => [
                'name' => 'Interview reminder (in-app)',
                'description' => 'Reminder before an upcoming interview.',
                'subject' => null,
                'body' => 'Reminder: you have an upcoming interview for {{job_title}}.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'interview_when', 'platform_name'],
            ],
            self::EmployerApplicationReceivedInApp => [
                'name' => 'Employer: new application (in-app)',
                'description' => 'Notifies the job employer when someone applies.',
                'subject' => null,
                'body' => '{{candidate_name}} applied for {{job_title}}.',
                'variables' => ['candidate_name', 'job_title', 'company_name', 'application_reference', 'platform_name'],
            ],
            self::EmployerInterviewRespondedInApp => [
                'name' => 'Employer: interview response (in-app)',
                'description' => 'Notifies the employer when a candidate responds to an interview invitation.',
                'subject' => null,
                'body' => '{{candidate_name}} {{interview_response_label}} interview #{{interview_number}}.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'interview_response_label', 'interview_number', 'platform_name'],
            ],
            self::EmployerApplicationMessageInApp => [
                'name' => 'Employer: application message (in-app)',
                'description' => 'Notifies the employer when a candidate sends an application message.',
                'subject' => null,
                'body' => '{{candidate_name}}: {{message_preview}}',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'message_preview', 'platform_name'],
            ],
            self::EmployerCorrectionRequestInApp => [
                'name' => 'Employer: correction request (in-app)',
                'description' => 'Notifies the employer when a candidate submits a correction request.',
                'subject' => null,
                'body' => '{{candidate_name}} requested a correction or clarification for {{job_title}}.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'platform_name'],
            ],
            self::EmployerApplicationReceivedEmail => [
                'name' => 'Employer: new application (email)',
                'description' => 'Email to the job employer when someone applies.',
                'subject' => 'New application received',
                'body' => '{{candidate_name}} applied for {{job_title}}. Sign in to review the application on {{platform_name}}.',
                'variables' => ['candidate_name', 'job_title', 'company_name', 'application_reference', 'platform_name', 'support_email'],
            ],
            self::EmployerInterviewRespondedEmail => [
                'name' => 'Employer: interview response (email)',
                'description' => 'Email when a candidate responds to an interview invitation.',
                'subject' => 'Interview response received',
                'body' => '{{candidate_name}} {{interview_response_label}} interview #{{interview_number}} for {{job_title}}.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'interview_response_label', 'interview_number', 'platform_name', 'support_email'],
            ],
            self::EmployerApplicationMessageEmail => [
                'name' => 'Employer: application message (email)',
                'description' => 'Email when a candidate sends an application message.',
                'subject' => 'New application message',
                'body' => '{{candidate_name}} sent a message about {{job_title}}: {{message_preview}}',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'message_preview', 'platform_name', 'support_email'],
            ],
            self::EmployerCorrectionRequestEmail => [
                'name' => 'Employer: correction request (email)',
                'description' => 'Email when a candidate submits a correction request.',
                'subject' => 'Correction request submitted',
                'body' => '{{candidate_name}} requested a correction or clarification for {{job_title}}.',
                'variables' => ['candidate_name', 'job_title', 'application_reference', 'platform_name', 'support_email'],
            ],
        };
    }
}
