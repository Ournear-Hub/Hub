import type { AuthUser } from '@/types/page';

export const EMPLOYER_CREATE_JOB_PATH = '/account/jobs/create';
export const EMPLOYER_HIRING_WORKSPACE_PATH = '/account/hiring';
export const EMPLOYER_APPLICATIONS_PATH = '/account/hiring/applications';
export const RECRUITMENT_SUPPORT_PATH = '/account/recruitment-support';
export const STAFFING_WORKSPACE_PATH = '/account/enquiries';

export function loginWithIntended(path: string): string {
    return `/login?intended=${encodeURIComponent(path)}`;
}

export function hiringActivationUrl(destination: string): string {
    return `/account/hiring?destination=${encodeURIComponent(destination)}`;
}

export function hasCompanyProfile(user: AuthUser | null | undefined): boolean {
    if (!user) {
        return false;
    }

    if (user.portal_navigation?.company?.has_company_profile) {
        return true;
    }

    return user.role === 'employer' && user.employer_identity != null;
}

/** Resolve employer workspace paths with login, activation, or direct access. */
export function resolveEmployerDestinationHref(
    user: AuthUser | null | undefined,
    destination: string,
): string {
    if (!user) {
        return loginWithIntended(destination);
    }

    if (!hasCompanyProfile(user)) {
        return hiringActivationUrl(destination);
    }

    return destination;
}

/** “Post a Job” / employer onboarding entry (existing routes only). */
export function resolvePostJobHref(user: AuthUser | null | undefined): string {
    return resolveEmployerDestinationHref(user, EMPLOYER_CREATE_JOB_PATH);
}

/** Signed-in hiring workspace shortcuts for landing-page demos (login + intended for guests). */
export function resolveHiringWorkspaceHref(
    user: AuthUser | null | undefined,
    path: `/account${string}` = EMPLOYER_HIRING_WORKSPACE_PATH,
): string {
    return resolveEmployerDestinationHref(user, path);
}

export function resolveStaffingWorkspaceHref(user: AuthUser | null | undefined): string {
    return resolveEmployerDestinationHref(user, STAFFING_WORKSPACE_PATH);
}

export function scrollToHiringSection(id: string): void {
    const target = document.getElementById(id);
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/** CMS-configured paths: public routes pass through; account paths use activation/login routing. */
export function resolvePublicCmsHref(
    user: AuthUser | null | undefined,
    href: string | null | undefined,
): string | null {
    if (href == null || href.trim() === '') {
        return null;
    }

    const path = href.trim();
    if (!path.startsWith('/account')) {
        return path;
    }

    if (path === EMPLOYER_CREATE_JOB_PATH || path.startsWith(`${EMPLOYER_CREATE_JOB_PATH}/`)) {
        return resolvePostJobHref(user);
    }

    if (path.startsWith(RECRUITMENT_SUPPORT_PATH)) {
        return resolveEmployerDestinationHref(user, RECRUITMENT_SUPPORT_PATH);
    }

    if (path.startsWith(STAFFING_WORKSPACE_PATH)) {
        return resolveStaffingWorkspaceHref(user);
    }

    if (path.startsWith('/account/hiring') || path.startsWith('/account/jobs') || path.startsWith('/account/company')) {
        return resolveEmployerDestinationHref(user, path);
    }

    return resolveEmployerDestinationHref(user, path);
}
