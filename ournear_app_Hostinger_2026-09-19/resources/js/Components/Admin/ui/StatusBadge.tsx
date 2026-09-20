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
};

export default function StatusBadge({ status, className = '' }: StatusBadgeProps) {
    const tone = tones[status] ?? 'bg-surface text-muted';

    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${tone} ${className}`}>
            {status}
        </span>
    );
}
