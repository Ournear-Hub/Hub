import type { AccountCompletionItem } from '@/types/account';

/** Keys that most affect Hire Resume content (existing profile completion items only). */
const RESUME_RELEVANT_KEYS = new Set([
    'name',
    'bio',
    'location',
    'mobile',
    'current_role',
    'experience',
    'industry_or_function',
    'skills',
    'employment_history',
]);

export function resumeRelevantItems(items: AccountCompletionItem[]): AccountCompletionItem[] {
    return items.filter((item) => RESUME_RELEVANT_KEYS.has(item.key));
}

export function incompleteResumeItems(items: AccountCompletionItem[]): AccountCompletionItem[] {
    return resumeRelevantItems(items).filter((item) => !item.complete);
}

export function isHireResumeReady(items: AccountCompletionItem[]): boolean {
    const relevant = resumeRelevantItems(items);
    if (relevant.length === 0) {
        return true;
    }
    return relevant.every((item) => item.complete);
}

export function profileHrefForCompletionKey(key: string): string {
    const map: Record<string, string> = {
        name: '/account/profile#basic',
        bio: '/account/profile#about',
        location: '/account/profile#basic',
        mobile: '/account/profile#basic',
        current_role: '/account/profile#professional',
        experience: '/account/profile#professional',
        industry_or_function: '/account/profile#professional',
        skills: '/account/profile#skills',
        employment_history: '/account/profile#experience',
    };

    return map[key] ?? '/account/profile';
}
