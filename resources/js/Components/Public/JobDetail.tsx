import { Link, usePage } from '@inertiajs/react';
import ApplicationConfirmation from '@/Components/Public/ApplicationConfirmation';
import JobActions from '@/Components/Public/JobActions';
import Alert from '@/Components/UI/Alert';
import type { PublicJobDetail } from '@/types/jobs';
import type { SharedPageProps } from '@/types/page';

function Block({ title, body }: { title: string; body: string | null | undefined }) {
    if (!body) {
        return null;
    }

    return (
        <section className="mt-6">
            <h2 className="text-base font-semibold text-ink">{title}</h2>
            <p className="mt-2 whitespace-pre-line text-sm leading-6 text-muted">{body}</p>
        </section>
    );
}

type JobDetailProps = {
    job: PublicJobDetail;
    backHref?: string;
    layout?: 'standalone' | 'pane' | 'page';
};

export default function JobDetail({ job, backHref, layout = 'standalone' }: JobDetailProps) {
    const { flash, errors } = usePage<SharedPageProps>().props;
    const confirmation = flash.application_confirmation;
    const success = flash.success;
    const isPane = layout === 'pane';
    const isPage = layout === 'page' || Boolean(backHref);
    const companyInitials =
        job.company_name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0])
            .join('')
            .toUpperCase() || 'CO';

    const header = (
        <>
            <div className="flex flex-wrap gap-2">
                {job.featured ? <p className="text-xs font-medium uppercase tracking-wide text-accent">Featured</p> : null}
                {job.urgent ? <p className="text-xs font-medium uppercase tracking-wide text-accent">Urgent</p> : null}
            </div>
            <div className="mt-2 flex min-w-0 items-start gap-3">
                {job.company_logo_url ? (
                    job.company_url ? (
                        <Link href={job.company_url} className="shrink-0">
                            <img
                                src={job.company_logo_url}
                                alt={`${job.company_name} logo`}
                                className="h-14 w-14 rounded-lg border border-line object-cover"
                            />
                        </Link>
                    ) : (
                        <img
                            src={job.company_logo_url}
                            alt={`${job.company_name} logo`}
                            className="h-14 w-14 shrink-0 rounded-lg border border-line object-cover"
                        />
                    )
                ) : (
                    <div
                        aria-label={`${job.company_name} logo placeholder`}
                        className="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-base font-semibold text-brand"
                    >
                        {companyInitials}
                    </div>
                )}
                <div className="min-w-0">
                    <h1 className="text-xl font-semibold text-ink sm:text-2xl">{job.title}</h1>
                    {job.company_url ? (
                        <Link href={job.company_url} className="mt-1 inline-block text-sm font-medium text-brand hover:underline">
                            {job.company_name}
                        </Link>
                    ) : (
                        <p className="mt-1 text-sm font-medium text-brand">{job.company_name}</p>
                    )}
                    <p className="mt-0.5 text-sm text-muted">{job.location}</p>
                </div>
            </div>
            <dl className="mt-4 grid gap-2 rounded-lg bg-surface p-3 text-sm sm:grid-cols-2">
                {job.salary ? (
                    <div>
                        <dt className="font-medium text-ink">Salary / CTC</dt>
                        <dd className="mt-0.5 text-muted">{job.salary}</dd>
                    </div>
                ) : null}
                {job.experience ? (
                    <div>
                        <dt className="font-medium text-ink">Experience</dt>
                        <dd className="mt-0.5 text-muted">{job.experience}</dd>
                    </div>
                ) : null}
                {job.employment_type ? (
                    <div>
                        <dt className="font-medium text-ink">Employment type</dt>
                        <dd className="mt-0.5 text-muted">{job.employment_type}</dd>
                    </div>
                ) : null}
                {job.industry ? (
                    <div>
                        <dt className="font-medium text-ink">Industry</dt>
                        <dd className="mt-0.5 text-muted">{job.industry}</dd>
                    </div>
                ) : null}
                {job.category ? (
                    <div>
                        <dt className="font-medium text-ink">Category</dt>
                        <dd className="mt-0.5 text-muted">{job.category}</dd>
                    </div>
                ) : null}
                <div>
                    <dt className="font-medium text-ink">Vacancies</dt>
                    <dd className="mt-0.5 text-muted">{job.vacancy}</dd>
                </div>
                <div>
                    <dt className="font-medium text-ink">Posted</dt>
                    <dd className="mt-0.5 text-muted">{job.posted_label ?? '—'}</dd>
                </div>
                <div>
                    <dt className="font-medium text-ink">Closing</dt>
                    <dd className="mt-0.5 text-muted">{job.closing_label ?? (job.is_closed ? 'Closed' : 'Open')}</dd>
                </div>
                {job.joining_date ? (
                    <div>
                        <dt className="font-medium text-ink">Joining date</dt>
                        <dd className="mt-0.5 text-muted">{job.joining_date}</dd>
                    </div>
                ) : null}
                {job.site ? (
                    <div>
                        <dt className="font-medium text-ink">Site</dt>
                        <dd className="mt-0.5 text-muted">{job.site}</dd>
                    </div>
                ) : null}
            </dl>
            {errors.job ? (
                <Alert tone="error" title="Application" className="mt-3">
                    {Array.isArray(errors.job) ? errors.job[0] : errors.job}
                </Alert>
            ) : null}
            {success && !confirmation ? (
                <Alert tone="success" className="mt-3">
                    {success}
                </Alert>
            ) : null}
            {confirmation ? <ApplicationConfirmation confirmation={confirmation} className="mt-3" /> : null}
        </>
    );

    const body = (
        <>
            {header}
            {!isPane ? (
                <div className="mt-4 hidden sm:block">
                    <JobActions job={job} variant="detail" />
                </div>
            ) : null}
            <section className="mt-6">
                <h2 className="text-base font-semibold text-ink">About this role</h2>
                <p className="mt-2 whitespace-pre-line text-sm leading-6 text-muted">{job.description}</p>
            </section>
            <Block title="Responsibilities" body={job.responsibilities} />
            <Block title="Requirements" body={job.requirements} />
            <Block title="Benefits / What we offer" body={job.what_we_offer} />
            <Block title="Accommodation" body={job.accommodation} />
            {backHref ? (
                <Link href={backHref} className="mt-6 inline-block text-sm font-medium text-brand">
                    Back to jobs
                </Link>
            ) : isPage ? null : (
                <Link href={`/jobs/${job.slug}`} className="mt-6 inline-block text-sm font-medium text-brand lg:hidden">
                    Open full job page
                </Link>
            )}
        </>
    );

    if (isPane) {
        return (
            <div className="flex h-full min-h-0 flex-col bg-white">
                <div className="flex-1 overflow-y-auto overscroll-contain px-5 py-5">{body}</div>
                <div className="shrink-0 border-t border-line bg-white px-5 py-3">
                    <JobActions job={job} variant="detail" />
                </div>
            </div>
        );
    }

    return (
        <div
            className={`rounded-xl border border-line bg-white ${
                isPage ? 'p-4 pb-24 sm:p-6 sm:pb-6' : 'max-h-[calc(100vh-8rem)] overflow-y-auto p-6'
            }`}
        >
            {body}
            <div className="sticky bottom-0 z-10 -mx-4 mt-6 border-t border-line bg-white px-4 py-3 shadow-[0_-8px_16px_rgba(33,68,73,0.08)] pb-[calc(0.75rem+env(safe-area-inset-bottom,0px))] sm:hidden">
                <JobActions job={job} variant="detail" />
            </div>
        </div>
    );
}
