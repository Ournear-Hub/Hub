<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Server-side recruiter workspace path detection (keep aligned with resources/js/lib/recruiterAccountPaths.ts).
 */
class RecruiterAccountPaths
{
    public static function isRecruiterWorkspacePath(Request $request): bool
    {
        return $request->is('account/hiring')
            || $request->is('account/hiring/*')
            || $request->is('account/company')
            || $request->is('account/jobs')
            || $request->is('account/jobs/*')
            || $request->is('account/submissions')
            || $request->is('account/submissions/*')
            || $request->is('account/enquiries')
            || $request->is('account/enquiries/*')
            || $request->is('account/company-documents')
            || $request->is('account/company-documents/*')
            || $request->is('account/employee-reviews')
            || $request->is('account/employee-reviews/*')
            || $request->is('account/contact')
            || self::isRecruiterVerificationSettingsPath($request);
    }

    public static function isRecruiterVerificationSettingsPath(Request $request): bool
    {
        return $request->is('account/settings/verification')
            || $request->is('account/settings/verification/*');
    }

    /** Personal candidate settings — not recruiter workspace chrome. */
    public static function isPersonalCandidateSettingsPath(Request $request): bool
    {
        return $request->is('account/settings')
            || $request->is('account/settings/account')
            || $request->is('account/settings/account/*')
            || $request->is('account/settings/security')
            || $request->is('account/settings/security/*')
            || $request->is('account/settings/password')
            || $request->is('account/settings/password/*')
            || $request->is('account/settings/communication')
            || $request->is('account/settings/communication/*');
    }
}
