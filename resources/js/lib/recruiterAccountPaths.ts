function normalizeAccountPath(path: string): string {
    return path.split('?')[0]?.replace(/\/$/, '') ?? path;
}

/** Paths that share recruiter workspace navigation and chrome (see HandleInertiaRequests employerNav). */
export function isRecruiterAccountPath(path: string): boolean {
    const normalized = normalizeAccountPath(path);

    return (
        normalized === '/account/hiring' ||
        normalized.startsWith('/account/hiring/') ||
        normalized === '/account/company' ||
        normalized === '/account/jobs' ||
        normalized.startsWith('/account/jobs/') ||
        normalized === '/account/submissions' ||
        normalized.startsWith('/account/submissions/') ||
        normalized === '/account/enquiries' ||
        normalized.startsWith('/account/enquiries/') ||
        normalized === '/account/recruitment-support' ||
        normalized === '/account/talent-pool' ||
        normalized.startsWith('/account/talent-pool/') ||
        normalized === '/account/company/staffing-access' ||
        normalized === '/account/company-documents' ||
        normalized.startsWith('/account/company-documents/') ||
        normalized === '/account/employee-reviews' ||
        normalized.startsWith('/account/employee-reviews/') ||
        normalized === '/account/contact' ||
        normalized === '/account/settings/verification' ||
        normalized.startsWith('/account/settings/verification/')
    );
}

/** Personal candidate settings — not recruiter workspace chrome. */
export function isPersonalAccountSettingsPath(path: string): boolean {
    const normalized = normalizeAccountPath(path);

    if (normalized === '/account/settings') {
        return true;
    }

    if (
        normalized === '/account/settings/verification' ||
        normalized.startsWith('/account/settings/verification/')
    ) {
        return false;
    }

    return normalized.startsWith('/account/settings/');
}

/** Recruiter/company chrome (sidebar identity, nav) — not used on the candidate dashboard or personal profile. */
export function usesRecruiterAccountChrome(
    path: string,
    role: string | undefined,
    hasEmployerIdentity: boolean,
): boolean {
    if (isPersonalAccountSettingsPath(path)) {
        return false;
    }

    if (role === 'employer') {
        return true;
    }

    return role === 'candidate' && hasEmployerIdentity && isRecruiterAccountPath(path);
}

export const RECRUITER_WORKSPACE_HREF = '/account/hiring';
