import type { AuthUser } from '@/types/page';
import { loginWithIntended, resolveStaffingWorkspaceHref } from '@/utils/employerHiringEntry';

export const STAFFING_REQUEST_SECTION_ID = 'request-manpower';

const DEFAULT_ACCOUNT_PATH = '/account';

/** Personal dashboard entry; guests go to login with intended URL. */
export function resolveAccountDashboardHref(user: AuthUser | null | undefined): string {
    if (!user) {
        return loginWithIntended(DEFAULT_ACCOUNT_PATH);
    }

    return DEFAULT_ACCOUNT_PATH;
}

export function staffingWorkspaceHref(user: AuthUser | null | undefined): string {
    return resolveStaffingWorkspaceHref(user);
}

export function scrollToStaffingSection(id: string = STAFFING_REQUEST_SECTION_ID): void {
    const target = document.getElementById(id);
    if (!target) {
        return;
    }

    const reduced =
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
}
