import { router } from '@inertiajs/react';
import { useMemo, useState, type FormEvent } from 'react';
import Button from '@/Components/atoms/Button';

type StatusOption = {
    value: string;
    label: string;
};

type Props = {
    jobSlug: string;
    filters: {
        q: string;
        status: string;
        stage?: string;
    };
    statusOptions: StatusOption[];
    resultCount: number;
};

function countActiveFilters(filters: Props['filters']): number {
    let count = 0;
    if (filters.q.trim()) {
        count += 1;
    }
    if (filters.status) {
        count += 1;
    }
    if (filters.stage) {
        count += 1;
    }
    return count;
}

export default function EmployerApplicantSearch({ jobSlug, filters, statusOptions, resultCount }: Props) {
    const [q, setQ] = useState(filters.q);
    const [status, setStatus] = useState(filters.status);
    const activeCount = useMemo(() => countActiveFilters(filters), [filters]);

    const submit = (event?: FormEvent) => {
        event?.preventDefault();
        router.get(
            `/account/jobs/${jobSlug}/applicants`,
            {
                q: q.trim() || undefined,
                status: status || undefined,
                stage: filters.stage || undefined,
            },
            { preserveScroll: true, preserveState: true },
        );
    };

    const clearAll = () => {
        setQ('');
        setStatus('');
        router.get(`/account/jobs/${jobSlug}/applicants`, {}, { preserveScroll: true });
    };

    const filterFields = (
        <>
            <label className="block text-sm font-medium text-ink" htmlFor="applicant-q">
                Keyword
            </label>
            <input
                id="applicant-q"
                className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                placeholder="Search by name"
                value={q}
                onChange={(event) => setQ(event.target.value)}
            />
            <label className="mt-4 block text-sm font-medium text-ink md:mt-0" htmlFor="applicant-status">
                Application status
            </label>
            <select
                id="applicant-status"
                className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                value={status}
                onChange={(event) => setStatus(event.target.value)}
            >
                <option value="">All statuses</option>
                {statusOptions.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </>
    );

    return (
        <section className="rounded-xl border border-line bg-white p-4 sm:p-5" aria-labelledby="applicant-search-heading">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="applicant-search-heading" className="text-base font-semibold text-ink">
                        Search candidates
                    </h2>
                    <p className="mt-1 text-sm text-muted">
                        {resultCount} applicant{resultCount === 1 ? '' : 's'} on this page
                        {activeCount > 0 ? ` · ${activeCount} filter${activeCount === 1 ? '' : 's'} active` : ''}
                    </p>
                </div>
                {activeCount > 0 ? (
                    <button type="button" onClick={clearAll} className="min-h-11 text-sm font-semibold text-brand hover:underline">
                        Clear all
                    </button>
                ) : null}
            </div>
            <form onSubmit={submit} className="mt-4 hidden gap-3 md:grid md:grid-cols-[1fr_12rem_auto] md:items-end">
                {filterFields}
                <Button surface="public" type="submit" variant="secondary" className="min-h-11">
                    Search
                </Button>
            </form>
            <div className="mt-4 space-y-3 md:hidden">
                <input
                    className="min-h-11 w-full rounded-md border border-line px-3 py-2 text-sm"
                    placeholder="Search by name"
                    value={q}
                    onChange={(event) => setQ(event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                            submit();
                        }
                    }}
                />
                <details className="rounded-md border border-line bg-surface/50 px-3 py-2">
                    <summary className="cursor-pointer text-sm font-semibold text-ink">
                        Filters{activeCount > 0 ? ` · ${activeCount} active` : ''}
                    </summary>
                    <div className="mt-3 pb-2">{filterFields}</div>
                </details>
                <Button surface="public" type="button" variant="secondary" className="min-h-11 w-full" onClick={() => submit()}>
                    Search
                </Button>
            </div>
        </section>
    );
}
