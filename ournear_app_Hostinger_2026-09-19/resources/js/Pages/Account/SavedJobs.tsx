import { Head, Link, router } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import PageContainer from '@/Components/Public/PageContainer';
import Pagination from '@/Components/Pagination';
import type { Paginator } from '@/types/jobs';

type SavedJobItem = {
    title: string;
    slug: string | null;
    project: string | null;
    location: string | null;
    employment_type: string | null;
    status: 'open' | 'closed';
    status_label: string;
    saved_at: string | null;
    can_view: boolean;
};

type SavedJobsProps = {
    savedJobs: Paginator<SavedJobItem>;
    filters: {
        status: 'all' | 'open' | 'closed';
    };
};

const filterClass = (active: boolean) =>
    `rounded-md px-3 py-1.5 text-sm font-medium ${active ? 'bg-brand text-white' : 'text-muted hover:bg-surface hover:text-ink'}`;

export default function SavedJobs({ savedJobs, filters }: SavedJobsProps) {
    const unsave = (job: SavedJobItem) => {
        if (!job.slug) {
            return;
        }

        router.delete(`/account/saved-jobs/${job.slug}`, { preserveScroll: true });
    };

    return (
        <AccountLayout title="Saved Jobs">
            
                    <div className="mb-6 flex flex-wrap items-center gap-2">
                        <Link href="/account/saved-jobs" className={filterClass(filters.status === 'all')}>
                            All
                        </Link>
                        <Link href="/account/saved-jobs?status=open" className={filterClass(filters.status === 'open')}>
                            Open
                        </Link>
                        <Link href="/account/saved-jobs?status=closed" className={filterClass(filters.status === 'closed')}>
                            Closed
                        </Link>
                    </div>
                    {savedJobs.data.length === 0 ? (
                        <p className="text-sm text-muted">No saved jobs match this view.</p>
                    ) : (
                        <>
                            <ul className="space-y-4">
                                {savedJobs.data.map((job) => (
                                    <li key={job.slug ?? job.title} className="rounded-xl border border-line bg-white p-6">
                                        <h2 className="text-lg font-semibold text-ink">{job.title}</h2>
                                        <p className="mt-1 text-sm font-medium text-brand">{job.project}</p>
                                        <p className="mt-1 text-sm text-muted">
                                            {job.location} · {job.employment_type} · {job.status_label}
                                        </p>
                                        {job.saved_at ? <p className="mt-1 text-sm text-muted">Saved {job.saved_at}</p> : null}
                                        {!job.can_view ? (
                                            <p className="mt-2 text-sm text-danger">This role is no longer publicly available.</p>
                                        ) : null}
                                        <div className="mt-4 flex flex-wrap items-center gap-4 text-sm">
                                            {job.can_view && job.slug ? (
                                                <Link href={`/jobs/${job.slug}`} className="font-medium text-brand">
                                                    View job
                                                </Link>
                                            ) : null}
                                            {job.slug ? (
                                                <button
                                                    type="button"
                                                    className="font-medium text-danger"
                                                    onClick={() => unsave(job)}
                                                >
                                                    Unsave
                                                </button>
                                            ) : null}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                            <Pagination links={savedJobs.links} />
                        </>
                    )}
                    <p className="mt-6 text-sm">
                        <Link href="/account" className="font-medium text-brand hover:underline">
                            Back to account
                        </Link>
                    </p>
                </AccountLayout>
    );
}
