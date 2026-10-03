import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import Button from '@/Components/Public/Button';

type Props = {
    filters: {
        q: string;
        status: string;
    };
    statusOptions: Array<{ value: string; label: string }>;
    resultCount: number;
};

function activeFilterCount(filters: Props['filters']): number {
    let count = 0;
    if (filters.q.trim()) {
        count += 1;
    }
    if (filters.status) {
        count += 1;
    }
    return count;
}

export default function EmployerStaffingSearch({ filters, statusOptions, resultCount }: Props) {
    const [q, setQ] = useState(filters.q);
    const [status, setStatus] = useState(filters.status);
    const active = activeFilterCount(filters);

    const submit = (event?: FormEvent) => {
        event?.preventDefault();
        router.get(
            '/account/enquiries',
            {
                q: q.trim() || undefined,
                status: status || undefined,
            },
            { preserveScroll: true, preserveState: true },
        );
    };

    const clearAll = () => {
        setQ('');
        setStatus('');
        router.get('/account/enquiries', {}, { preserveScroll: true });
    };

    const filterFields = (
        <>
            <label className="block text-sm font-medium text-ink" htmlFor="staffing-q">
                Keyword
            </label>
            <input
                id="staffing-q"
                className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                placeholder="Location, workforce type, skills…"
                value={q}
                onChange={(event) => setQ(event.target.value)}
            />
            <label className="mt-4 block text-sm font-medium text-ink md:mt-0" htmlFor="staffing-status">
                Request status
            </label>
            <select
                id="staffing-status"
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
        <section className="rounded-xl border border-line bg-white p-4 sm:p-5" aria-labelledby="staffing-search-heading">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="staffing-search-heading" className="text-base font-semibold text-ink">
                        Search staffing requests
                    </h2>
                    <p className="mt-1 text-sm text-muted">
                        {resultCount} request{resultCount === 1 ? '' : 's'} on this page
                        {active > 0 ? ` · ${active} filter${active === 1 ? '' : 's'} active` : ''}
                    </p>
                </div>
                {active > 0 ? (
                    <button type="button" onClick={clearAll} className="min-h-11 text-sm font-semibold text-brand hover:underline">
                        Clear filters
                    </button>
                ) : null}
            </div>
            <form onSubmit={submit} className="mt-4 hidden gap-3 md:grid md:grid-cols-[1fr_12rem_auto] md:items-end">
                {filterFields}
                <Button type="submit" variant="secondary" className="min-h-11">
                    Apply filters
                </Button>
            </form>
            <div className="mt-4 space-y-3 md:hidden">
                <input
                    className="min-h-11 w-full rounded-md border border-line px-3 py-2 text-sm"
                    placeholder="Location, workforce type, skills…"
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
                        Filters{active > 0 ? ` · ${active} active` : ''}
                    </summary>
                    <div className="mt-3 pb-2">{filterFields}</div>
                </details>
                <Button type="button" variant="secondary" className="min-h-11 w-full" onClick={() => submit()}>
                    Apply filters
                </Button>
            </div>
        </section>
    );
}
