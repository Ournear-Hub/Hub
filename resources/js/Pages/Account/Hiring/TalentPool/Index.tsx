import { router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import AccountLayout from '@/Layouts/AccountLayout';
import EmployerTalentPoolPipeline from '@/Components/Account/Recruiter/EmployerTalentPoolPipeline';
import RecruiterCandidateSummary, {
    type RecruiterCandidateSummaryData,
} from '@/Components/Account/Recruiter/RecruiterCandidateSummary';
import Button from '@/Components/atoms/Button';
import Pagination from '@/Components/Pagination';
import type { PipelineStageSummary } from '@/Components/Account/Recruiter/EmployerApplicantPipeline';
import type { Paginator } from '@/types/jobs';

type Props = {
    company_name: string;
    applications: Paginator<RecruiterCandidateSummaryData>;
    pipeline_summary: PipelineStageSummary[];
    filters: {
        q: string;
        stage: string;
    };
};

export default function HiringTalentPoolIndex({ company_name, applications, pipeline_summary, filters }: Props) {
    const [q, setQ] = useState(filters.q);
    const empty = applications.data.length === 0;
    const hasFilters = Boolean(filters.q || filters.stage);
    const noCandidates = empty && applications.total === 0 && !hasFilters;
    const noMatches = empty && !noCandidates;

    const search = (event: FormEvent) => {
        event.preventDefault();
        const params = new URLSearchParams();
        if (q.trim()) {
            params.set('q', q.trim());
        }
        if (filters.stage) {
            params.set('stage', filters.stage);
        }
        router.get(`/account/hiring/candidates?${params.toString()}`, {}, { preserveScroll: true });
    };

    return (
        <AccountLayout title="Talent Pool" hideShellHeader>
            <section className="mb-4 rounded-xl border border-line bg-white p-4 sm:p-5">
                <p className="text-xs font-semibold uppercase tracking-wide text-muted">Talent Pool</p>
                <h1 className="mt-1 text-xl font-semibold text-ink">{company_name}</h1>
                <p className="mt-2 text-sm text-muted">
                    {pipeline_summary.find((row) => row.key === 'all')?.count ?? applications.total} candidate
                    {(pipeline_summary.find((row) => row.key === 'all')?.count ?? applications.total) === 1 ? '' : 's'} from
                    your jobs
                </p>
            </section>

            <div className="mb-4 rounded-xl border border-line bg-white p-4 sm:p-5">
                <h2 className="text-sm font-semibold text-ink">Status</h2>
                <p className="mt-1 text-xs text-muted">
                    Stages use application statuses. &quot;In review&quot; maps to Reviewing (there is no separate Contacted status). Contact
                    details are on each application snapshot, not the public talent directory.
                </p>
                <div className="mt-3">
                    <EmployerTalentPoolPipeline summary={pipeline_summary} activeStage={filters.stage} searchQuery={filters.q} />
                </div>
            </div>

            <form onSubmit={search} className="mb-4 flex flex-col gap-3 rounded-xl border border-line bg-white p-4 sm:flex-row sm:items-end">
                <label className="min-w-0 flex-1 text-sm">
                    <span className="font-medium text-ink">Search</span>
                    <input
                        type="search"
                        value={q}
                        onChange={(event) => setQ(event.target.value)}
                        placeholder="Name"
                        className="mt-1 w-full rounded-md border border-line px-3 py-2.5 text-sm"
                    />
                </label>
                <Button surface="public" type="submit" variant="secondary" className="min-h-11 w-full sm:w-auto">
                    Search
                </Button>
            </form>

            {noCandidates ? (
                <div className="rounded-xl border border-dashed border-line bg-white p-8 text-center">
                    <p className="text-sm font-semibold text-ink">No candidates in your job talent pool yet</p>
                    <p className="mt-2 text-sm text-muted">
                        Applicants from your published jobs appear here. For OurNear-assigned manpower candidates, open{' '}
                        <a href="/account/talent-pool" className="font-semibold text-brand hover:underline">
                            Assigned Candidates
                        </a>{' '}
                        after admin allocation.
                    </p>
                </div>
            ) : null}

            {noMatches ? (
                <div className="rounded-xl border border-dashed border-line bg-white p-8 text-center">
                    <p className="text-sm text-muted">No candidates match these filters.</p>
                    <Button surface="public" href="/account/hiring/candidates" variant="secondary" className="mt-4 min-h-11">
                        Clear filters
                    </Button>
                </div>
            ) : null}

            {!empty ? (
                <div className="space-y-3">
                    {applications.data.map((candidate) => (
                        <RecruiterCandidateSummary key={candidate.id} candidate={candidate} mode="application" />
                    ))}
                    <Pagination links={applications.links} />
                </div>
            ) : null}
        </AccountLayout>
    );
}
