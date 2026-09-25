import { Link, router, usePage } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import type { SharedPageProps } from '@/types/page';

type TimelineEvent = {
    key: string;
    label: string;
    at: string | null;
};

type ApplicationDetail = {
    id: number;
    business_id: string | null;
    applied_at: string | null;
    updated_at: string | null;
    last_updated_at: string | null;
    status: string;
    can_withdraw: boolean;
    withdraw_requires_confirmation: boolean;
    job: {
        title: string;
        slug: string;
        company: string | null;
        location: string | null;
        employment_type: string | null;
        experience: string | null;
        salary: string | null;
        is_closed: boolean;
        status_label: string;
        can_view: boolean;
        href: string | null;
    } | null;
    resume: {
        has_resume: boolean;
        filename: string | null;
        extension: string | null;
        size: number | null;
        download_url: string | null;
    };
    timeline: TimelineEvent[];
};

type ShowProps = {
    application: ApplicationDetail;
};

function fileSize(bytes: number | null): string {
    if (!bytes) {
        return '—';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    return `${Math.round(bytes / 1024)} KB`;
}

export default function ApplicationShow({ application }: ShowProps) {
    const flash = usePage<SharedPageProps>().props.flash;

    const withdraw = () => {
        const message = application.withdraw_requires_confirmation
            ? 'You have been selected. Are you sure you want to withdraw this application?'
            : 'Withdraw this application?';

        if (!window.confirm(message)) {
            return;
        }

        router.patch(`/account/applications/${application.id}/withdraw`);
    };

    return (
        <AccountLayout title="Application" description="Your application status and recruitment timeline.">
            {flash.success ? <p className="mb-4 text-sm text-success">{flash.success}</p> : null}

            <div className="space-y-4">
                <section className="rounded-xl border border-line bg-white p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p className="text-xs font-medium uppercase tracking-wide text-muted">
                                {application.business_id ?? `Application #${application.id}`}
                            </p>
                            <h2 className="mt-1 text-xl font-semibold text-ink">Application</h2>
                        </div>
                        <span className="rounded-full bg-surface px-3 py-1 text-sm font-medium text-ink">
                            {application.status}
                        </span>
                    </div>
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted">Applied</dt>
                            <dd className="mt-1 text-ink">{application.applied_at ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Last updated</dt>
                            <dd className="mt-1 text-ink">{application.last_updated_at ?? application.updated_at ?? '—'}</dd>
                        </div>
                    </dl>
                    {application.can_withdraw ? (
                        <button type="button" className="mt-4 text-sm font-medium text-danger" onClick={withdraw}>
                            Withdraw application
                        </button>
                    ) : null}
                </section>

                <section className="rounded-xl border border-line bg-white p-6">
                    <h2 className="text-lg font-semibold text-ink">Job</h2>
                    <p className="mt-2 text-base font-medium text-ink">{application.job?.title ?? 'Role'}</p>
                    <p className="mt-1 text-sm text-muted">
                        {[application.job?.company, application.job?.location, application.job?.employment_type]
                            .filter(Boolean)
                            .join(' · ')}
                    </p>
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted">Experience</dt>
                            <dd className="mt-1 text-ink">{application.job?.experience || '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Salary</dt>
                            <dd className="mt-1 text-ink">{application.job?.salary || '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Job status</dt>
                            <dd className="mt-1 text-ink">{application.job?.is_closed ? 'Closed' : application.job?.status_label || '—'}</dd>
                        </div>
                    </dl>
                    {application.job?.href ? (
                        <Link href={application.job.href} className="mt-4 inline-block text-sm font-medium text-brand">
                            View job
                        </Link>
                    ) : (
                        <p className="mt-4 text-sm text-muted">This job listing is closed or no longer public.</p>
                    )}
                </section>

                <section className="rounded-xl border border-line bg-white p-6">
                    <h2 className="text-lg font-semibold text-ink">Resume used</h2>
                    {application.resume.has_resume ? (
                        <>
                            <p className="mt-2 text-sm text-ink">{application.resume.filename}</p>
                            <p className="mt-1 text-sm text-muted">
                                Snapshot from this application
                                {application.resume.extension ? ` · ${application.resume.extension.toUpperCase()}` : ''}
                                {application.resume.size ? ` · ${fileSize(application.resume.size)}` : ''}
                            </p>
                            {application.resume.download_url ? (
                                <a href={application.resume.download_url} className="mt-4 inline-block text-sm font-medium text-accent">
                                    Download snapshot
                                </a>
                            ) : null}
                        </>
                    ) : (
                        <p className="mt-2 text-sm text-muted">No resume snapshot is available.</p>
                    )}
                </section>

                <section className="rounded-xl border border-line bg-white p-6">
                    <h2 className="text-lg font-semibold text-ink">Timeline</h2>
                    <ol className="mt-4 space-y-3">
                        {application.timeline.map((event) => (
                            <li key={`${event.key}-${event.at ?? 'none'}`} className="border-l-2 border-brand/30 pl-4">
                                <p className="text-sm font-medium text-ink">{event.label}</p>
                                <p className="text-sm text-muted">{event.at ?? '—'}</p>
                            </li>
                        ))}
                    </ol>
                </section>
            </div>

            <p className="mt-6 text-sm">
                <Link href="/account/applications" className="font-medium text-brand hover:underline">
                    Back to applications
                </Link>
            </p>
        </AccountLayout>
    );
}
