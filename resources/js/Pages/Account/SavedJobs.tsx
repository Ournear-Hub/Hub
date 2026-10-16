import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import JobCard from '@/Components/Public/JobCard';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import AccountPageStack from '@/Components/Account/AccountPageStack';
import AccountLayout from '@/Layouts/AccountLayout';
import Pagination from '@/Components/Pagination';
import type { Paginator, PublicJobCard } from '@/types/jobs';
import { publicJobsWorkspaceWithJob } from '@/utils/publicJobsUrl';

type SavedJobCard = PublicJobCard & {
    saved_at: string | null;
    can_view: boolean;
    status_label: string;
};

type SavedJobsProps = {
    savedJobs: Paginator<SavedJobCard>;
    filters: {
        status: 'all' | 'open' | 'closed';
    };
};

const filterOptions = [
    { value: 'all', label: 'All' },
    { value: 'open', label: 'Open' },
    { value: 'closed', label: 'Closed' },
] as const;

const filterClass = (active: boolean) =>
    `inline-flex min-h-10 items-center rounded-md px-3 py-1.5 text-sm font-medium ${
        active ? 'bg-brand text-white' : 'text-muted hover:bg-surface hover:text-ink'
    }`;

function formatSavedAt(iso: string | null): string | null {
    if (!iso) {
        return null;
    }

    const saved = new Date(iso);
    if (Number.isNaN(saved.getTime())) {
        return null;
    }

    return saved.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function SavedJobApplyAction({ job }: { job: SavedJobCard }) {
    const applyHref = job.slug ? `/jobs/${job.slug}/apply` : null;
    const applyClass =
        'inline-flex min-h-10 items-center justify-center rounded-md bg-accent px-3 py-2 text-sm font-semibold text-white disabled:bg-disabled';
    const closedClass =
        'inline-flex min-h-10 items-center justify-center rounded-md border border-line px-3 py-2 text-sm font-semibold text-muted';

    if (job.apply_state === 'hidden' || !applyHref) {
        return null;
    }

    if (job.apply_state === 'closed') {
        return <span className={closedClass}>Closed</span>;
    }

    if (job.apply_state === 'applied') {
        return <span className={closedClass}>Applied</span>;
    }

    if (job.apply_state === 'upload_resume') {
        return (
            <Link href={applyHref} className={applyClass}>
                Continue to apply
            </Link>
        );
    }

    return (
        <Link href={applyHref} className={applyClass}>
            Apply
        </Link>
    );
}

export default function SavedJobs({ savedJobs, filters }: SavedJobsProps) {
    const [removingSlug, setRemovingSlug] = useState<string | null>(null);
    const [removeError, setRemoveError] = useState<string | null>(null);
    const activeStatus = filters.status || 'all';
    const isFiltered = activeStatus !== 'all';

    const unsave = (job: SavedJobCard) => {
        if (!job.slug || removingSlug) {
            return;
        }

        setRemovingSlug(job.slug);
        setRemoveError(null);
        router.delete(`/account/saved-jobs/${job.slug}`, {
            preserveScroll: true,
            onSuccess: () => setRemoveError(null),
            onError: () => {
                setRemoveError('Could not remove this saved job. Please try again.');
            },
            onFinish: () => setRemovingSlug(null),
        });
    };

    return (
        <AccountLayout
            title="Saved Jobs"
            description="Keep track of opportunities you want to explore."
            breadcrumbs={[
                { label: 'Personal Dashboard', href: '/account' },
                { label: 'Saved Jobs' },
            ]}
        >
            <AccountPageStack className="max-w-none">
                {removeError ? (
                    <p className="rounded-md border border-danger/20 bg-danger/5 px-3 py-2 text-sm text-danger" role="alert">
                        {removeError}
                    </p>
                ) : null}
                <nav className="flex flex-wrap gap-2" aria-label="Filter saved jobs">
                    {filterOptions.map((option) => {
                        const href =
                            option.value === 'all'
                                ? '/account/saved-jobs'
                                : `/account/saved-jobs?status=${option.value}`;
                        const active = activeStatus === option.value;

                        return (
                            <Link
                                key={option.value}
                                href={href}
                                className={filterClass(active)}
                                aria-current={active ? 'true' : undefined}
                            >
                                {option.label}
                            </Link>
                        );
                    })}
                </nav>

                {savedJobs.data.length === 0 ? (
                    <Card padding="compact" className="min-w-0">
                        {!isFiltered ? (
                            <>
                                <h2 className="text-base font-semibold text-ink">No saved jobs yet</h2>
                                <p className="mt-2 text-sm text-muted">
                                    When you save roles from job listings, they will appear here so you can review and apply
                                    later.
                                </p>
                                <Button surface="public" href="/jobs" variant="primary" className="mt-4 min-h-11">
                                    Browse jobs
                                </Button>
                            </>
                        ) : (
                            <>
                                <h2 className="text-base font-semibold text-ink">No saved jobs match this filter</h2>
                                <p className="mt-2 text-sm text-muted">Try another status or view all saved jobs.</p>
                                <Link
                                    href="/account/saved-jobs"
                                    className="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                                >
                                    Show all saved jobs
                                </Link>
                            </>
                        )}
                    </Card>
                ) : (
                    <>
                        <ul className="space-y-3">
                            {savedJobs.data.map((job) => {
                                const savedLabel = formatSavedAt(job.saved_at);
                                const jobHref =
                                    job.slug && job.can_view ? publicJobsWorkspaceWithJob(job.slug) : null;
                                const isRemoving = Boolean(job.slug && removingSlug === job.slug);

                                return (
                                    <li key={job.slug ?? job.title} className="min-w-0 space-y-2">
                                        <JobCard job={{ ...job, is_saved: true }} compact hideActions linkable={false} />
                                        <div className="flex flex-wrap items-center gap-x-2 gap-y-1 px-0.5 text-sm">
                                            <span
                                                className={`rounded-md px-2 py-0.5 text-xs font-medium ${
                                                    job.status_label === 'Open'
                                                        ? 'bg-brand/10 text-brand'
                                                        : 'bg-surface text-muted'
                                                }`}
                                            >
                                                {job.status_label}
                                            </span>
                                            {savedLabel ? <span className="text-muted">Saved {savedLabel}</span> : null}
                                            {!job.can_view ? (
                                                <span className="text-xs text-muted">Listing no longer public</span>
                                            ) : null}
                                        </div>
                                        <div className="flex flex-wrap items-center gap-x-4 gap-y-2 px-0.5 text-sm">
                                            {jobHref ? (
                                                <Link
                                                    href={jobHref}
                                                    className="inline-flex min-h-10 items-center font-semibold text-brand hover:underline"
                                                >
                                                    View job
                                                </Link>
                                            ) : job.slug ? (
                                                <span className="text-muted">Job listing unavailable</span>
                                            ) : null}
                                            <SavedJobApplyAction job={job} />
                                            {job.slug ? (
                                                <button
                                                    type="button"
                                                    className="inline-flex min-h-10 items-center font-semibold text-danger disabled:opacity-60"
                                                    onClick={() => unsave(job)}
                                                    disabled={isRemoving}
                                                >
                                                    {isRemoving ? 'Removing…' : 'Remove'}
                                                </button>
                                            ) : null}
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                        <Pagination links={savedJobs.links} />
                    </>
                )}
            </AccountPageStack>
        </AccountLayout>
    );
}
