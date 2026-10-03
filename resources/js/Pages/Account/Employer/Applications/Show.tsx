import { Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import StatusBadge from '@/Components/Account/StatusBadge';
import Button from '@/Components/Public/Button';
import Card from '@/Components/UI/Card';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';
import type { RecruiterCandidateSummaryData } from '@/Components/Account/Recruiter/RecruiterCandidateSummary';

type TimelineEvent = {
    key: string;
    label: string;
    at: string | null;
};

type ApplicationDetail = RecruiterCandidateSummaryData & {
    timeline: TimelineEvent[];
    application_contact?: {
        available: boolean;
        email: string | null;
        mobile: string | null;
        source_label: string;
    };
};

type Props = {
    job: {
        title: string;
        business_id: string | null;
        slug: string;
        location?: string | null;
        status_label?: string | null;
        company?: string | null;
    };
    application: ApplicationDetail;
    list_filters: {
        q: string;
        status: string;
        stage: string;
    };
};

function listHref(job: Props['job'], filters: Props['list_filters']): string {
    const params = new URLSearchParams();
    if (filters.q) {
        params.set('q', filters.q);
    }
    if (filters.status) {
        params.set('status', filters.status);
    }
    if (filters.stage) {
        params.set('stage', filters.stage);
    }
    const query = params.toString();
    return `/account/jobs/${job.slug}/applicants${query ? `?${query}` : ''}`;
}

function applicationHref(job: Props['job'], application: ApplicationDetail, filters: Props['list_filters']): string {
    const params = new URLSearchParams();
    if (filters.q) {
        params.set('q', filters.q);
    }
    if (filters.status) {
        params.set('status', filters.status);
    }
    if (filters.stage) {
        params.set('stage', filters.stage);
    }
    const query = params.toString();
    const base = `/account/jobs/${job.slug}/applications/${application.id}`;
    return query ? `${base}?${query}` : base;
}

function profileQuery(
    job: Props['job'],
    application: ApplicationDetail,
    listHrefValue: string,
    filters: Props['list_filters'],
): string {
    const params = new URLSearchParams();
    params.set('back', listHrefValue);
    params.set('job_title', job.title);
    params.set('application_status', application.status_label ?? application.status);
    if (application.applied_at) {
        params.set('applied_at', application.applied_at);
    }
    params.set('application_href', applicationHref(job, application, filters));
    return params.toString();
}

export default function EmployerApplicationShow({ job, application, list_filters }: Props) {
    const applicantsHref = listHref(job, list_filters);
    const statusLabel = application.status_label ?? application.status;
    const profileHref = application.public_profile_url
        ? `${application.public_profile_url}?${profileQuery(job, application, applicantsHref, list_filters)}`
        : null;

    return (
        <AccountLayout
            title={`Application · ${application.name}`}
            description={`${job.title} · Direct Hiring / ATS`}
            breadcrumbs={[
                { label: 'Hiring', href: '/account/hiring' },
                { label: 'Jobs', href: '/account/jobs' },
                { label: job.title, href: `/account/jobs/${job.slug}/edit` },
                { label: 'Applicants', href: applicantsHref },
                { label: application.name },
            ]}
        >
            <div className="mx-auto max-w-3xl space-y-4 lg:max-w-none">
                <Card as="section" density="compact">
                    <Link href={applicantsHref} className="text-sm font-semibold text-brand hover:underline">
                        ← Back to applicants
                    </Link>
                    <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-muted">Applicant for</p>
                    <h1 className="mt-1 text-xl font-semibold text-ink">{job.title}</h1>
                    <p className="mt-1 text-sm text-muted">
                        {[job.company, job.location, job.business_id].filter(Boolean).join(' · ')}
                    </p>
                </Card>

                <Card as="section" density="standard">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 className="text-lg font-semibold text-ink">{application.name}</h2>
                            {application.designation ? (
                                <p className="mt-1 text-sm font-medium text-ink">{application.designation}</p>
                            ) : null}
                            <p className="mt-1 text-sm text-muted">
                                {[application.location, application.experience, application.qualification]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                        </div>
                        <StatusBadge status={statusLabel} />
                    </div>
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted">Applied</dt>
                            <dd className="font-medium text-ink">{formatAppliedAt(application.applied_at ?? null)}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Current status</dt>
                            <dd className="font-medium text-ink">{statusLabel}</dd>
                        </div>
                        {application.business_id ? (
                            <div>
                                <dt className="text-muted">Application ID</dt>
                                <dd className="font-medium text-ink">{application.business_id}</dd>
                            </div>
                        ) : null}
                    </dl>
                    {'application_contact' in application && application.application_contact?.available ? (
                        <div className="mt-4 rounded-lg border border-line bg-surface/50 p-4">
                            <h3 className="text-sm font-semibold text-ink">Contact (application snapshot)</h3>
                            <dl className="mt-2 grid gap-2 text-sm sm:grid-cols-2">
                                {application.application_contact.email ? (
                                    <div>
                                        <dt className="text-muted">Email</dt>
                                        <dd className="font-medium text-ink">
                                            <a href={`mailto:${application.application_contact.email}`} className="text-brand hover:underline">
                                                {application.application_contact.email}
                                            </a>
                                        </dd>
                                    </div>
                                ) : null}
                                {application.application_contact.mobile ? (
                                    <div>
                                        <dt className="text-muted">Mobile</dt>
                                        <dd className="font-medium text-ink">{application.application_contact.mobile}</dd>
                                    </div>
                                ) : null}
                            </dl>
                            <p className="mt-2 text-xs text-muted">{application.application_contact.source_label}</p>
                        </div>
                    ) : (
                        <p className="mt-4 text-sm text-muted">No contact details were captured on this application snapshot.</p>
                    )}
                    <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        {profileHref ? (
                            <Button href={profileHref} variant="secondary" className="min-h-11 w-full sm:w-auto">
                                View candidate profile
                            </Button>
                        ) : (
                            <p className="text-sm text-muted">Public profile not shared by this candidate.</p>
                        )}
                    </div>
                </Card>

                <Card as="section" density="compact">
                    <h2 className="text-base font-semibold text-ink">Resume</h2>
                    {application.has_resume && application.resume_url ? (
                        <div className="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            <a
                                href={`${application.resume_url}?disposition=inline`}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                            >
                                View
                            </a>
                            <a
                                href={application.resume_url}
                                className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                            >
                                Download
                            </a>
                        </div>
                    ) : (
                        <p className="mt-2 text-sm text-muted">Resume not available for this application snapshot.</p>
                    )}
                    <p className="mt-2 text-xs text-muted">
                        Application resume snapshots are historical files from when the candidate applied.
                    </p>
                </Card>

                {application.timeline.length > 0 ? (
                    <Card as="section" density="compact">
                        <h2 className="text-base font-semibold text-ink">Application timeline</h2>
                        <ol className="mt-3 space-y-3 border-l-2 border-line pl-4">
                            {application.timeline.map((event) => (
                                <li key={`${event.key}-${event.at ?? 'na'}`} className="text-sm">
                                    <p className="font-medium text-ink">{event.label}</p>
                                    {event.at ? <p className="text-muted">{event.at}</p> : null}
                                </li>
                            ))}
                        </ol>
                    </Card>
                ) : null}

                <Card as="section" density="compact" className="bg-surface/40">
                    <h2 className="text-base font-semibold text-ink">Application actions</h2>
                    <p className="mt-2 text-sm text-muted">
                        Status changes for employer-owned jobs are handled through OurNear recruitment operations. Review
                        the candidate profile and resume snapshot here, then continue in your existing hiring workflow.
                    </p>
                </Card>
            </div>
        </AccountLayout>
    );
}
