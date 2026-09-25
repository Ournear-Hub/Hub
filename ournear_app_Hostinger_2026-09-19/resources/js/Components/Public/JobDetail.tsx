import { Link, usePage } from '@inertiajs/react';
import JobActions from '@/Components/Public/JobActions';
import type { PublicJobDetail } from '@/types/jobs';
import type { SharedPageProps } from '@/types/page';

function Block({ title, body }: { title: string; body: string | null | undefined }) {
    if (!body) {
        return null;
    }

    return (
        <section className="mt-8">
            <h2 className="text-lg font-semibold text-ink">{title}</h2>
            <p className="mt-2 whitespace-pre-line text-sm leading-6 text-muted">{body}</p>
        </section>
    );
}

export default function JobDetail({ job, backHref }: { job: PublicJobDetail; backHref?: string }) {
    const confirmation = usePage<SharedPageProps>().props.flash.application_confirmation;
    const success = usePage<SharedPageProps>().props.flash.success;
    const companyInitials =
        job.company_name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0])
            .join('')
            .toUpperCase() || 'CO';

    return (
        <div
            className={`rounded-xl border border-line bg-white p-6 ${
                backHref ? '' : 'max-h-[calc(100vh-8rem)] overflow-y-auto'
            }`}
        >
            {job.featured ? <p className="text-xs font-medium uppercase tracking-wide text-accent">Featured</p> : null}
            <div className="mt-2 flex min-w-0 items-start gap-4">
                {job.company_logo_url ? (
                    <img
                        src={job.company_logo_url}
                        alt={`${job.company_name} logo`}
                        className="h-16 w-16 shrink-0 rounded-xl border border-line object-cover"
                    />
                ) : (
                    <div
                        aria-label={`${job.company_name} logo placeholder`}
                        className="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-lg font-semibold text-brand"
                    >
                        {companyInitials}
                    </div>
                )}
                <div className="min-w-0">
                    <h1 className="text-2xl font-semibold text-ink sm:text-3xl">{job.title}</h1>
                    {job.company_url ? (
                        <Link href={job.company_url} className="mt-2 inline-block text-sm font-medium text-brand hover:underline">
                            {job.company_name}
                        </Link>
                    ) : (
                        <p className="mt-2 text-sm font-medium text-brand">{job.company_name}</p>
                    )}
                    <p className="mt-1 text-sm text-muted">{job.location}</p>
                </div>
            </div>
            <dl className="mt-6 grid gap-3 rounded-lg bg-surface p-4 text-sm sm:grid-cols-2 xl:grid-cols-3">
                {job.salary ? <div><dt className="font-medium text-ink">Salary / CTC</dt><dd className="mt-1 text-muted">{job.salary}</dd></div> : null}
                {job.experience ? <div><dt className="font-medium text-ink">Experience</dt><dd className="mt-1 text-muted">{job.experience}</dd></div> : null}
                {job.employment_type ? <div><dt className="font-medium text-ink">Employment type</dt><dd className="mt-1 text-muted">{job.employment_type}</dd></div> : null}
                {job.industry ? <div><dt className="font-medium text-ink">Industry</dt><dd className="mt-1 text-muted">{job.industry}</dd></div> : null}
                {job.category ? <div><dt className="font-medium text-ink">Category</dt><dd className="mt-1 text-muted">{job.category}</dd></div> : null}
                <div><dt className="font-medium text-ink">Vacancies</dt><dd className="mt-1 text-muted">{job.vacancy}</dd></div>
                <div><dt className="font-medium text-ink">Posted</dt><dd className="mt-1 text-muted">{job.posted_label ?? '—'}</dd></div>
                <div><dt className="font-medium text-ink">Closing</dt><dd className="mt-1 text-muted">{job.closing_label ?? (job.is_closed ? 'Closed' : 'Open')}</dd></div>
            </dl>
            {success ? (
                <p className="mt-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                    {success}
                </p>
            ) : null}
            {confirmation ? (
                <div className="mt-4 rounded-md border border-line bg-surface px-3 py-3 text-sm">
                    <p className="font-medium text-ink">Application {confirmation.business_id}</p>
                    <p className="mt-1 text-muted">
                        {confirmation.job_title}
                        {confirmation.company ? ` · ${confirmation.company}` : ''}
                        {confirmation.location ? ` · ${confirmation.location}` : ''}
                    </p>
                    {confirmation.href ? (
                        <Link href={confirmation.href} className="mt-2 inline-block font-medium text-brand">
                            View application
                        </Link>
                    ) : null}
                </div>
            ) : null}
            <div className="mt-6">
                <JobActions job={job} variant="detail" />
            </div>
            <section className="mt-8">
                <h2 className="text-lg font-semibold text-ink">About this role</h2>
                <p className="mt-2 whitespace-pre-line text-sm leading-6 text-muted">{job.description}</p>
            </section>
            <Block title="Responsibilities" body={job.responsibilities} />
            <Block title="Requirements" body={job.requirements} />
            <Block title="Benefits / What we offer" body={job.what_we_offer} />
            {backHref ? (
                <Link href={backHref} className="mt-6 inline-block text-sm font-medium text-brand">
                    Back to jobs
                </Link>
            ) : null}
        </div>
    );
}
