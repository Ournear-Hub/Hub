import type { AccountEmploymentHistory } from '@/types/account';

type Props = {
    histories: AccountEmploymentHistory[];
    onEdit?: (history: AccountEmploymentHistory) => void;
    onRemove?: (id: number) => void;
    compact?: boolean;
};

function formatPeriod(history: AccountEmploymentHistory): string {
    const start = history.started_on ?? '';
    const end = history.is_current ? 'Present' : history.ended_on ?? '';
    const parts = [start, end].filter(Boolean);
    return parts.length > 0 ? parts.join(' – ') : 'Dates not specified';
}

export default function EmploymentTimeline({ histories, onEdit, onRemove, compact = false }: Props) {
    if (histories.length === 0) {
        return <p className="text-sm text-muted">No employment history added yet.</p>;
    }

    return (
        <ol className="relative space-y-0 border-l-2 border-brand/25 pl-5">
            {histories.map((history, index) => (
                <li key={history.id} className={`relative ${index < histories.length - 1 ? 'pb-8' : 'pb-1'}`}>
                    <span
                        className="absolute -left-[1.625rem] top-1.5 flex h-3 w-3 rounded-full border-2 border-white bg-brand ring-2 ring-brand/20"
                        aria-hidden
                    />
                    <div className={compact ? 'space-y-1' : 'space-y-2'}>
                        <div className="flex flex-wrap items-start justify-between gap-2">
                            <div className="min-w-0">
                                <p className="text-sm font-semibold text-ink">
                                    {history.designation || 'Role not specified'}
                                    {history.company ? (
                                        <span className="font-normal text-muted"> · {history.company}</span>
                                    ) : null}
                                </p>
                                <p className="text-sm text-muted">
                                    {formatPeriod(history)}
                                    {history.industry ? ` · ${history.industry}` : ''}
                                </p>
                            </div>
                            <div className="flex shrink-0 flex-wrap gap-2">
                                {history.is_current ? (
                                    <span className="rounded-full bg-brand/10 px-2 py-0.5 text-xs font-semibold text-brand">
                                        Current
                                    </span>
                                ) : null}
                                {history.is_internship ? (
                                    <span className="rounded-full bg-surface px-2 py-0.5 text-xs font-medium text-ink">
                                        Internship
                                    </span>
                                ) : null}
                            </div>
                        </div>
                        {history.description ? (
                            <p className="whitespace-pre-wrap text-sm text-ink">{history.description}</p>
                        ) : null}
                        {onEdit || onRemove ? (
                            <div className="flex gap-3 pt-1">
                                {onEdit ? (
                                    <button
                                        type="button"
                                        className="text-sm font-semibold text-brand hover:underline"
                                        onClick={() => onEdit(history)}
                                    >
                                        Edit
                                    </button>
                                ) : null}
                                {onRemove ? (
                                    <button
                                        type="button"
                                        className="text-sm font-semibold text-danger hover:underline"
                                        onClick={() => onRemove(history.id)}
                                    >
                                        Remove
                                    </button>
                                ) : null}
                            </div>
                        ) : null}
                    </div>
                </li>
            ))}
        </ol>
    );
}
