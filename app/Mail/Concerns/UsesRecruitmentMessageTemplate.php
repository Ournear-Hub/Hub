<?php

namespace App\Mail\Concerns;

use App\Models\Application;
use App\Models\ApplicationInterview;
use App\Services\MessageTemplateService;

trait UsesRecruitmentMessageTemplate
{
    /**
     * @return array{subject: string, intro_line: string}
     */
    protected function recruitmentEmailCopy(
        string $templateKey,
        Application $application,
        string $defaultSubject,
        string $defaultIntro,
        ?ApplicationInterview $interview = null,
    ): array {
        $rendered = app(MessageTemplateService::class)->renderForApplication(
            $templateKey,
            $application,
            $interview,
            $defaultSubject,
            $defaultIntro,
        );

        return [
            'subject' => (string) ($rendered['subject'] ?? $defaultSubject),
            'intro_line' => (string) ($rendered['body'] ?? $defaultIntro),
        ];
    }
}
