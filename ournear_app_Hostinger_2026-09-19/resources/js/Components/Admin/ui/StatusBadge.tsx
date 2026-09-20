type StatusBadgeProps = {
    status: string;
    className?: string;
};

const tones: Record<string, string> = {
    New: 'bg-surface text-ink',
    Reviewing: 'bg-brand/10 text-brand',
    Shortlisted: 'bg-success/10 text-success',
    Interview: 'bg-warning/15 text-ink',
    Selected: 'bg-success/15 text-success',
    Placed: 'bg-success/20 text-success',
    Rejected: 'bg-danger/10 text-danger',
    Withdrawn: 'bg-surface text-muted',
    Contacted: 'bg-brand/10 text-brand',
    'In Progress': 'bg-warning/15 text-ink',
    Completed: 'bg-success/10 text-success',
    Closed: 'bg-surface text-muted',
    Active: 'bg-success/10 text-success',
    Inactive: 'bg-surface text-muted',
    Published: 'bg-success/10 text-success',
    Draft: 'bg-warning/15 text-ink',
    Featured: 'bg-brand/10 text-brand',
    Pending: 'bg-surface text-muted',
    Suitable: 'bg-brand/10 text-brand',
    'Not Suitable': 'bg-danger/10 text-danger',
    Submitted: 'bg-brand/10 text-brand',
    'Under Review': 'bg-warning/15 text-ink',
    'On Hold': 'bg-surface text-muted',
    Open: 'bg-surface text-ink',
    Offered: 'bg-brand/10 text-brand',
    Accepted: 'bg-success/10 text-success',
    Declined: 'bg-danger/10 text-danger',
    'Joining Confirmed': 'bg-warning/15 text-ink',
    Joined: 'bg-success/20 text-success',
};

export default function StatusBadge({ status, className = '' }: StatusBadgeProps) {
    const tone = tones[status] ?? 'bg-surface text-muted';

    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${tone} ${className}`}>
            {status}
        </span>
    );
}
