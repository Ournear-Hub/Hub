<?php

namespace App\Support;

use App\Enums\SystemMessageTemplateKey;
use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Services\WebsiteSettingService;

class MessageTemplateVariableCatalog
{
    /**
     * @return list<string>
     */
    public static function globalVariables(): array
    {
        return [
            'candidate_name',
            'job_title',
            'company_name',
            'application_reference',
            'application_status',
            'interview_when',
            'platform_name',
            'support_email',
            'interview_response_label',
            'interview_number',
            'message_preview',
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedForKey(string $templateKey): array
    {
        $system = SystemMessageTemplateKey::tryFrom($templateKey);
        if ($system instanceof SystemMessageTemplateKey) {
            return $system->defaults()['variables'];
        }

        return self::globalVariables();
    }

    /**
     * @return array<string, string>
     */
    public static function sampleValues(): array
    {
        return [
            'candidate_name' => 'Sample Candidate',
            'job_title' => 'Site Engineer',
            'company_name' => 'Sample Employer Ltd',
            'application_reference' => 'APP-2026-0001',
            'application_status' => 'Under review',
            'interview_when' => 'Mon 12 Oct 2026, 10:00 IST',
            'platform_name' => (string) config('app.name', 'OurNear Hire'),
            'support_email' => 'support@example.com',
            'interview_response_label' => 'accepted',
            'interview_number' => '1',
            'message_preview' => 'Sample message preview text.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function forApplication(Application $application, ?ApplicationInterview $interview = null): array
    {
        $application->loadMissing(['job', 'job.employer']);

        $settings = app(WebsiteSettingService::class);
        $supportEmail = $settings->supportEmail() ?? $settings->primaryEmail() ?? '';

        $employerName = $application->job?->employer?->company_name ?? 'the hiring company';

        $values = [
            'candidate_name' => (string) $application->full_name,
            'job_title' => (string) ($application->job?->title ?? 'this role'),
            'company_name' => (string) $employerName,
            'application_reference' => (string) ($application->business_id ?? (string) $application->id),
            'application_status' => $application->status->toCandidateStatusLabel(),
            'platform_name' => (string) config('app.name', 'OurNear Hire'),
            'support_email' => $supportEmail !== '' ? $supportEmail : 'support@example.com',
            'interview_when' => '',
        ];

        if ($interview instanceof ApplicationInterview) {
            $values['interview_when'] = $interview->scheduled_at?->timezone(config('app.timezone'))->format('D j M Y, H:i T')
                ?? 'Details will follow separately.';
        }

        return $values;
    }
}
