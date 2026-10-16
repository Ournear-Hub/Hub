/**
 * Account-surface status labels → presentation (distinct from admin map where labels overlap).
 * See docs/OURNEAR_BADGE_PR3A.md for known cross-surface differences.
 */
export const accountStatusPresentation: Record<string, string> = {
    Submitted: 'bg-brand/10 text-brand',
    'Under Review': 'bg-accent/10 text-accent',
    'Under review': 'bg-accent/10 text-accent',
    Selected: 'bg-success/10 text-success',
    Rejected: 'bg-danger/10 text-danger',
    'On Hold': 'bg-surface text-ink',
    New: 'bg-brand/10 text-brand',
    Reviewing: 'bg-accent/10 text-accent',
    Shortlisted: 'bg-brand/10 text-brand',
    Interview: 'bg-accent/10 text-accent',
    Placed: 'bg-success/10 text-success',
    Withdrawn: 'bg-surface text-muted',
    Received: 'bg-brand/10 text-brand',
    'Being handled': 'bg-accent/10 text-accent',
    Completed: 'bg-success/10 text-success',
    Closed: 'bg-surface text-muted',
    Verified: 'bg-success/10 text-success',
    Unread: 'bg-brand/10 text-brand',
    'Action required': 'bg-accent/10 text-accent',
};

export function accountStatusToneClass(status: string): string {
    return accountStatusPresentation[status] ?? 'bg-surface text-ink';
}
