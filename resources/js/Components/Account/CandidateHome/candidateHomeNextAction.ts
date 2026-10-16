import { isHireResumeReady } from '@/Components/Resume/resumeReadiness';
import type { AccountCandidateDashboard, AccountProfile } from '@/types/account';

export type CandidateNextAction = {
    title: string;
    description: string;
    href: string;
    cta: string;
};

export function candidateFirstName(fullName: string): string {
    const parts = fullName.trim().split(/\s+/).filter(Boolean);
    return parts[0] ?? fullName;
}

export function resolveCandidateNextAction(
    profile: AccountProfile,
    dashboard: AccountCandidateDashboard,
): CandidateNextAction {
    if (dashboard.next_action) {
        return dashboard.next_action;
    }

    const completion = profile.profile_completion ?? 0;
    const hasMyResume = Boolean(dashboard.resume.primary);

    if (completion < 100) {
        return {
            title: 'Complete your profile',
            description: 'Finish your professional details so employers can review your applications.',
            href: '/account/profile',
            cta: 'Continue profile',
        };
    }

    if (!hasMyResume) {
        return {
            title: 'Upload your resume',
            description: 'Add your CV for roles that accept a file upload alongside your profile.',
            href: '/account/resumes',
            cta: 'Manage resumes',
        };
    }

    if (!isHireResumeReady(profile.completion_items)) {
        return {
            title: 'Complete profile for OurNear Resume',
            description: 'OurNear Resume pulls from your profile—fill any missing sections to mark it ready.',
            href: '/account/profile',
            cta: 'Complete profile',
        };
    }

    return {
        title: 'Find your next opportunity',
        description: 'Browse open roles and apply with your profile and resume.',
        href: '/jobs',
        cta: 'Browse jobs',
    };
}

export function formatAppliedAt(iso: string | null): string {
    if (!iso) {
        return 'Applied recently';
    }

    const applied = new Date(iso);
    if (Number.isNaN(applied.getTime())) {
        return 'Applied recently';
    }

    const startOfToday = new Date();
    startOfToday.setHours(0, 0, 0, 0);
    const startOfApplied = new Date(applied);
    startOfApplied.setHours(0, 0, 0, 0);
    const dayMs = 1000 * 60 * 60 * 24;
    const days = Math.floor((startOfToday.getTime() - startOfApplied.getTime()) / dayMs);

    if (days <= 0) {
        return 'Applied today';
    }
    if (days === 1) {
        return 'Applied 1 day ago';
    }

    return `Applied ${days} days ago`;
}
