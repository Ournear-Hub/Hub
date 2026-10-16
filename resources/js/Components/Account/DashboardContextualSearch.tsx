import { FormEvent, useId, useState } from 'react';
import SearchField from '@/Components/molecules/SearchField';

type ResultItem = { label: string; meta?: string | null; url: string };

type CandidateResults = {
    scope: 'candidate';
    applications: ResultItem[];
    saved_jobs: ResultItem[];
};

type EmployerResults = {
    scope: 'employer';
    jobs: ResultItem[];
    applicants: ResultItem[];
    staffing_requests?: ResultItem[];
    recruitment_requests?: ResultItem[];
};

type Props = {
    scope: 'candidate' | 'employer' | 'auto';
    label?: string;
    /** Compact single-row layout for recruiter workspace header. */
    variant?: 'card' | 'inline';
};

export default function DashboardContextualSearch({
    scope,
    label = 'Search this workspace',
    variant = 'card',
}: Props) {
    const fieldId = useId();
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<CandidateResults | EmployerResults | null>(null);
    const [loading, setLoading] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        const q = query.trim();
        if (q.length < 2) {
            setResults(null);
            return;
        }
        setLoading(true);
        try {
            const params = new URLSearchParams({ q, scope });
            const response = await fetch(`/account/contextual-search?${params.toString()}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                setResults(null);
                return;
            }
            const json = (await response.json()) as CandidateResults | EmployerResults;
            setResults(json);
        } finally {
            setLoading(false);
        }
    };

    const sections =
        results?.scope === 'employer'
            ? [
                  { title: 'Your jobs', items: results.jobs },
                  { title: 'Applicants', items: results.applicants },
                  { title: 'Staffing requests', items: results.staffing_requests ?? [] },
                  { title: 'Recruitment requirements', items: results.recruitment_requests ?? [] },
              ].filter((section) => section.items.length > 0)
            : results
              ? [
                    { title: 'Applications', items: results.applications },
                    { title: 'Saved jobs', items: results.saved_jobs },
                ]
              : [];

    const resultsPanel =
        sections.length > 0 ? (
            <div className={`space-y-4 ${variant === 'inline' ? 'absolute left-0 right-0 top-full z-40 mt-2 rounded-xl border border-line bg-white p-4 shadow-lg' : 'mt-4'}`}>
                {sections.map((section) => (
                    <div key={section.title}>
                        <p className="text-xs font-semibold uppercase tracking-wide text-muted">{section.title}</p>
                        {section.items.length === 0 ? (
                            <p className="mt-1 text-sm text-muted">No matches.</p>
                        ) : (
                            <ul className="mt-2 space-y-1">
                                {section.items.map((item) => (
                                    <li key={`${section.title}-${item.url}`}>
                                        <a href={item.url} className="block rounded-md px-2 py-1.5 text-sm hover:bg-surface">
                                            <span className="font-medium text-ink">{item.label}</span>
                                            {item.meta ? (
                                                <span className="mt-0.5 block text-xs text-muted">{item.meta}</span>
                                            ) : null}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                ))}
            </div>
        ) : null;

    if (variant === 'inline') {
        return (
            <div className="relative min-w-0">
                <form onSubmit={submit} className="flex min-w-0 items-center gap-2">
                    <div className="min-w-0 flex-1">
                        <label htmlFor={fieldId} className="sr-only">
                            {label}
                        </label>
                        <SearchField
                            id={fieldId}
                            aria-label={label}
                            placeholder="Search jobs and applicants…"
                            value={query}
                            onChange={(event) => setQuery(event.currentTarget.value)}
                            className="w-full"
                        />
                    </div>
                    <button
                        type="submit"
                        disabled={loading}
                        className="min-h-10 shrink-0 rounded-md bg-brand px-3 text-sm font-semibold text-white disabled:opacity-60 sm:px-4"
                    >
                        {loading ? '…' : 'Search'}
                    </button>
                </form>
                {resultsPanel}
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-line bg-white p-4">
            <form onSubmit={submit} className="flex flex-col gap-2 sm:flex-row sm:items-end">
                <div className="min-w-0 flex-1">
                    <label htmlFor={fieldId} className="mb-1 block text-sm font-medium text-ink">
                        {label}
                    </label>
                    <SearchField
                        id={fieldId}
                        aria-label={label}
                        placeholder="Applications, jobs, saved roles…"
                        value={query}
                        onChange={(event) => setQuery(event.currentTarget.value)}
                    />
                </div>
                <button
                    type="submit"
                    disabled={loading}
                    className="min-h-11 shrink-0 rounded-md bg-brand px-4 text-sm font-semibold text-white disabled:opacity-60"
                >
                    {loading ? 'Searching…' : 'Search'}
                </button>
            </form>
            {resultsPanel}
        </div>
    );
}
