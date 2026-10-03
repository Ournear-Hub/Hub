import { Link } from '@inertiajs/react';
import type { PipelineStageSummary } from '@/Components/Account/Recruiter/EmployerApplicantPipeline';

type Props = {
    summary: PipelineStageSummary[];
    activeStage: string;
    searchQuery: string;
};

function queryForStage(stage: string, q: string): string {
    const params = new URLSearchParams();
    if (q.trim()) {
        params.set('q', q.trim());
    }
    if (stage && stage !== 'all') {
        params.set('stage', stage);
    }
    const query = params.toString();
    return `/account/hiring/candidates${query ? `?${query}` : ''}`;
}

export default function EmployerTalentPoolPipeline({ summary, activeStage, searchQuery }: Props) {
    const current = activeStage || 'all';

    return (
        <nav className="overflow-x-auto" aria-label="Talent pool stages">
            <ul className="flex min-w-0 flex-wrap gap-2 pb-1 lg:flex-nowrap">
                {summary.map((stage) => {
                    const active = (stage.key === 'all' && current === '') || stage.key === current;
                    return (
                        <li key={stage.key} className="shrink-0">
                            <Link
                                href={queryForStage(stage.key, searchQuery)}
                                className={`inline-flex min-h-11 items-center gap-2 rounded-full border px-3 py-2 text-sm font-semibold ${
                                    active
                                        ? 'border-brand bg-brand/10 text-brand'
                                        : 'border-line bg-white text-ink hover:border-brand/40'
                                }`}
                                aria-current={active ? 'page' : undefined}
                            >
                                <span>{stage.label}</span>
                                <span className="rounded-full bg-surface px-2 py-0.5 text-xs font-semibold text-ink">{stage.count}</span>
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
