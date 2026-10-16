import type { AuthUser } from '@/types/page';
import {
    RECRUITMENT_SUPPORT_PATH,
    resolveEmployerDestinationHref,
    resolvePostJobHref,
} from '@/utils/employerHiringEntry';

/** Contact form topic for managed recruitment (matches PublicSiteController contact interestOptions). */
export const TALENT_POOL_CONTACT_INTEREST = 'Recruitment';

export const TALENT_POOL_PROCESS_SECTION_ID = 'talent-pool-process';

/** Managed recruitment requirement form (account recruitment support). */
export function resolveTalentPoolRequirementHref(user: AuthUser | null | undefined): string {
    return resolveEmployerDestinationHref(user, RECRUITMENT_SUPPORT_PATH);
}

/** Self-service job posting from Talent Pool marketing CTAs. */
export function resolveTalentPoolPostJobHref(user: AuthUser | null | undefined): string {
    return resolvePostJobHref(user);
}

export function scrollToTalentPoolProcess(id: string = TALENT_POOL_PROCESS_SECTION_ID): void {
    const target = document.getElementById(id);
    if (!target) {
        return;
    }

    const reduced =
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
}
