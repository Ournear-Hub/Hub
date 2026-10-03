import { router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import AccountLayout from '@/Layouts/AccountLayout';
import RecruiterCandidateSummary, {
    type RecruiterCandidateSummaryData,
} from '@/Components/Account/Recruiter/RecruiterCandidateSummary';
import Button from '@/Components/Public/Button';
import Pagination from '@/Components/Pagination';
import type { Paginator } from '@/types/jobs';

type Props = {
    company_name: string;
    applications: Paginator<RecruiterCandidateSummaryData>;
    filters: {
        q: string;
        status: string;
    };
    status_options: Array<{ value: string; label: string }>;
};

export default function HiringApplicationsIndex({ company_name, applications, filters, status_options }: Props) {
    const [q, setQ] = useState(filters.q);
    const [status, setStatus] = useState(filters.status);
    const empty = applications.data.length === 0;
    const hasFilters = Boolean(filters.q || filters.status);
    const noApplications = empty && applications.total === 0 && !hasFilters;

    const search = (event: FormEvent) => {
        event.preventDefault();
        const params = new URLSearchParams();
        if (q.trim()) {
            params.set('q', q.trim());
        }
        if (status) {
            params.set('status', status);
        }
        router.get(`/account/hiring/applications?${params.toString()}`, {}, { preserveScroll: true });
    };

    return (
        <AccountLayout
            title="Applications"
            description="All applications across your jobs."
            breadcrumbs={[
                { label: 'Hiring', href: '/account/hiring' },
                { label: 'Applications' },
            ]}
        >
            <section className="mb-4 rounded-xl border border-line bg-white p-4 sm:p-5">
                <p className="text-xs font-semibold uppercase tracking-wide text-muted">Direct Hiring / ATS</p>
                <h1 className="mt-1 text-xl font-semibold text-ink">Applications</h1>
                <p className="mt-1 text-sm text-muted">{company_name}</p>
            </section>

            <form onSubmit={search} className="mb-4 grid gap-3 rounded-xl border border-line bg-white p-4 sm:grid-cols-[1fr_auto_auto] sm:items-end">
                <label className="min-w-0 text-sm">
                    <span className="font-medium text-ink">Search</span>
                    <input
                        type="search"
                        value={q}
                        onChange={(event) => setQ(event.target.value)}
                        placeholder="Candidate name"
                        className="mt-1 w-full rounded-md border border-line px-3 py-2.5 text-sm"
                    />
                </label>
                <label className="text-sm">
                    <span className="font-medium text-ink">Status</span>
                    <select
                        value={status}
                        onChange={(event) => setStatus(event.target.value)}
                        className="mt-1 w-full min-w-[10rem] rounded-md border border-line px-3 py-2.5 text-sm"
                    >
                        <option value="">All statuses</option>
                        {status_options.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </label>
                <Button type="submit" variant="secondary" className="min-h-11 w-full sm:w-auto">
                    Filter
                </Button>
            </form>

            {noApplications ? (
                <div className="rounded-xl border border-dashed border-line bg-white p-8 text-center">
                    <p className="text-sm font-semibold text-ink">No applications yet</p>
                    <p className="mt-2 text-sm text-muted">Applications will appear here when candidates apply to your jobs.</p>
                    <Button href="/account/jobs" variant="secondary" className="mt-4 min-h-11">
                        View jobs
                    </Button>
                </div>
            ) : null}

            {!empty ? (
                <div className="space-y-3">
                    <p className="text-sm text-muted">
                        {applications.total} application{applications.total === 1 ? '' : 's'}
                    </p>
                    {applications.data.map((candidate) => (
                        <RecruiterCandidateSummary key={candidate.id} candidate={candidate} mode="application" />
                    ))}
                    <Pagination links={applications.links} />
                </div>
            ) : null}
        </AccountLayout>
    );
}
