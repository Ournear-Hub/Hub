import type { AuthUser } from '@/types/page';
import {
    EMPLOYER_HIRING_WORKSPACE_PATH,
    resolveEmployerDestinationHref,
    resolvePostJobHref,
} from '@/utils/employerHiringEntry';

export { resolvePostJobHref };

/** Employer ATS / hiring workspace entry for CTAs (existing routes only). */
export function resolveHiringWorkspaceHref(user: AuthUser | null | undefined): string {
    return resolveEmployerDestinationHref(user, EMPLOYER_HIRING_WORKSPACE_PATH);
}
