import { Link } from '@inertiajs/react';

export type PipelineStageSummary = {
    key: string;
    label: string;
    count: number;
};

type Props = {
    jobSlug: string;
    summary: PipelineStageSummary[];
    activeStage: string;
    searchQuery: string;
    statusFilter: string;
};

function queryForStage(jobSlug: string, stage: string, q: string, status: string): string {
    const params = new URLSearchParams();
    if (q.trim()) {
        params.set('q', q.trim());
    }
    if (status) {
        params.set('status', status);
    }
    if (stage && stage !== 'all') {
        params.set('stage', stage);
    }
    const query = params.toString();
    return `/account/jobs/${jobSlug}/applicants${query ? `?${query}` : ''}`;
}

export default function EmployerApplicantPipeline({ jobSlug, summary, activeStage, searchQuery, statusFilter }: Props) {
    const current = activeStage || 'all';
    const lifecycleStages = summary.filter((stage) => stage.key !== 'all');

    return (
        <div className="space-y-3">
            <p className="text-xs text-muted">
                Stages follow the shared ATS lifecycle (Applied through Closed). Counts use the same rules as admin
                screening and placement data.
            </p>
            {lifecycleStages.length > 0 ? (
                <div
                    className="hidden gap-0.5 sm:grid sm:grid-cols-4 lg:grid-cols-8"
                    aria-hidden="true"
                >
                    {lifecycleStages.map((stage) => {
                        const active = stage.key === current;
                        return (
                            <div
                                key={`bar-${stage.key}`}
                                className={`rounded-md border px-2 py-2 text-center text-[10px] font-semibold uppercase tracking-wide ${
                                    active ? 'border-brand bg-brand/5 text-brand' : 'border-line bg-surface text-muted'
                                }`}
                            >
                                {stage.label}
                            </div>
                        );
                    })}
                </div>
            ) : null}
            <nav className="overflow-x-auto" aria-label="Application pipeline stages">
                <ul className="flex min-w-0 gap-2 pb-1">
                    {summary.map((stage) => {
                        const active = (stage.key === 'all' && current === '') || stage.key === current;
                        return (
                            <li key={stage.key} className="shrink-0">
                                <Link
                                    href={queryForStage(jobSlug, stage.key, searchQuery, statusFilter)}
                                    className={`inline-flex min-h-11 items-center gap-2 rounded-full border px-3 py-2 text-sm font-semibold ${
                                        active
                                            ? 'border-brand bg-brand/10 text-brand'
                                            : 'border-line bg-white text-ink hover:border-brand/40'
                                    }`}
                                    aria-current={active ? 'page' : undefined}
                                    aria-label={`${stage.label}, ${stage.count} applicants`}
                                >
                                    <span>{stage.label}</span>
                                    <span className="rounded-full bg-surface px-2 py-0.5 text-xs font-semibold text-ink">
                                        {stage.count}
                                    </span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>
        </div>
    );
}
