const tones: Record<string, string> = {
    Submitted: 'bg-brand/10 text-brand',
    'Under Review': 'bg-accent/10 text-accent',
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
};

export default function StatusBadge({ status }: { status: string }) {
    return (
        <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${tones[status] ?? 'bg-surface text-ink'}`}>
            {status}
        </span>
    );
}
