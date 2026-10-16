<?php

namespace App\Support;

use Illuminate\Http\Request;

/** Account URL prefixes that use company/recruiter workspace chrome and employer sidebar nav. */
final class AccountRecruiterWorkspacePaths
{
    /**
     * @return list<string>
     */
    public static function patterns(): array
    {
        return [
            'account/hiring',
            'account/hiring/*',
            'account/company',
            'account/jobs',
            'account/jobs/*',
            'account/submissions',
            'account/submissions/*',
            'account/enquiries',
            'account/enquiries/*',
            'account/recruitment-support',
            'account/talent-pool',
            'account/talent-pool/*',
            'account/company-documents',
            'account/company-documents/*',
            'account/employee-reviews',
            'account/employee-reviews/*',
            'account/contact',
            'account/settings/verification',
            'account/settings/verification/*',
        ];
    }

    public static function matchesRequest(Request $request): bool
    {
        foreach (self::patterns() as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
