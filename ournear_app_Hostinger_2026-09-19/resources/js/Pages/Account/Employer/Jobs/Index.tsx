import { Link, router, usePage } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import Pagination from '@/Components/Pagination';
import type { AdminJob, Paginator } from '@/types/jobs';
import type { SharedPageProps } from '@/types/page';

type Eligibility = {
    eligible: boolean;
    reason: string | null;
    next_action: string | null;
};

type Props = {
    jobs: Paginator<AdminJob>;
    eligibility: Eligibility;
    featured: {
        available: boolean;
        message: string;
    };
};

export default function EmployerJobsIndex({ jobs, eligibility, featured }: Props) {
    const flash = usePage<SharedPageProps>().props.flash;

    const remove = (job: AdminJob) => {
        if (window.confirm(`Delete ${job.title}?`)) {
            router.delete(`/account/jobs/${job.slug}`);
        }
    };

    return (
        <AccountLayout
            title="Job management"
            description="Post jobs, manage your own listings, and review applicants."
            action={
                eligibility.eligible ? (
                    <Link href="/account/jobs/create" className="rounded-md bg-accent px-4 py-2 text-sm font-semibold text-white">
                        Post Job
                    </Link>
                ) : null
            }
        >
            {flash.success ? <p className="mb-4 rounded-md bg-success/10 px-3 py-2 text-sm text-success">{flash.success}</p> : null}
            {flash.error ? <p className="mb-4 rounded-md bg-danger/10 px-3 py-2 text-sm text-danger">{flash.error}</p> : null}
            {!eligibility.eligible ? (
                <section className="mb-4 rounded-xl border border-accent/40 bg-accent/10 p-4 text-sm text-ink">
                    <p className="font-semibold">Job posting is currently unavailable</p>
                    <p className="mt-1">{eligibility.reason}</p>
                    {eligibility.next_action ? <p className="mt-1 text-muted">{eligibility.next_action}</p> : null}
                </section>
            ) : null}
            <section className="mb-6 rounded-xl border border-line bg-white p-4">
                <p className="text-sm font-semibold text-ink">Featured jobs</p>
                <p className="mt-1 text-sm text-muted">{featured.message}</p>
            </section>
            {jobs.data.length === 0 ? (
                <div className="rounded-xl border border-dashed border-line bg-white p-8 text-center">
                    <h2 className="text-lg font-semibold text-ink">No jobs posted yet</h2>
                    <p className="mt-2 text-sm text-muted">Your first eligible post is a one-hour trial job.</p>
                </div>
            ) : (
                <div className="space-y-4">
                    {jobs.data.map((job) => (
                        <article key={job.id} className="rounded-xl border border-line bg-white p-5">
                            <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                <div>
                                    <h2 className="text-lg font-semibold text-ink">{job.title}</h2>
                                    <p className="mt-1 text-sm text-muted">
                                        {job.business_id} · {job.location} · {job.publishing_status === 'published' ? 'Published' : 'Draft'}
                                    </p>
                                    <p className="mt-1 text-sm text-muted">
                                        {job.posting_tier === 'trial' ? 'Trial' : 'Basic'} · {job.applications_count} applicant{job.applications_count === 1 ? '' : 's'}
                                    </p>
                                </div>
                                <div className="flex flex-wrap gap-3 text-sm font-medium">
                                    <Link href={`/jobs/${job.slug}`} className="text-brand">View</Link>
                                    <Link href={`/account/jobs/${job.slug}/edit`} className="text-brand">Edit</Link>
                                    <Link href={`/account/jobs/${job.slug}/applicants`} className="text-brand">Applicants</Link>
                                    <button type="button" className="text-danger" onClick={() => remove(job)}>Delete</button>
                                </div>
                            </div>
                        </article>
                    ))}
                    <Pagination links={jobs.links} />
                </div>
            )}
        </AccountLayout>
    );
}
