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

type SubmissionDetail = RecruiterCandidateSummaryData & {
    submission_id?: number;
    submitted_at: string | null;
    timeline: TimelineEvent[];
    requirement_href?: string | null;
};

type Props = {
    company: {
        name: string;
        business_id: string | null;
    };
    submission: SubmissionDetail;
    list_filters: {
        q: string;
        status: string;
        job: string;
    };
    support_href: string;
};

function listHref(filters: Props['list_filters']): string {
    if (filters.job) {
        const params = new URLSearchParams();
        if (filters.q) {
            params.set('q', filters.q);
        }
        if (filters.status) {
            params.set('status', filters.status);
        }
        const query = params.toString();
        return `/account/submissions/requirements/${filters.job}${query ? `?${query}` : ''}`;
    }
    const params = new URLSearchParams();
    if (filters.q) {
        params.set('q', filters.q);
    }
    if (filters.status) {
        params.set('status', filters.status);
    }
    const query = params.toString();
    return `/account/submissions${query ? `?${query}` : ''}`;
}

function submissionSelfHref(submission: SubmissionDetail, filters: Props['list_filters']): string {
    const id = submission.submission_id ?? submission.id;
    const params = new URLSearchParams();
    if (filters.q) {
        params.set('q', filters.q);
    }
    if (filters.status) {
        params.set('status', filters.status);
    }
    if (filters.job) {
        params.set('job', filters.job);
    }
    const query = params.toString();
    return `/account/submissions/${id}${query ? `?${query}` : ''}`;
}

function profileQuery(submission: SubmissionDetail, backHref: string, filters: Props['list_filters']): string {
    const params = new URLSearchParams();
    params.set('back', backHref);
    params.set('job_title', submission.job?.title ?? 'Recruitment requirement');
    params.set('application_status', submission.status_label ?? submission.status);
    if (submission.submitted_at) {
        params.set('applied_at', submission.submitted_at);
    }
    params.set('application_href', submissionSelfHref(submission, filters));
    return params.toString();
}

export default function EmployerSubmissionShow({ company, submission, list_filters, support_href }: Props) {
    const backHref = listHref(list_filters);
    const statusLabel = submission.status_label ?? submission.status;
    const profileHref = submission.public_profile_url
        ? `${submission.public_profile_url}?${profileQuery(submission, backHref, list_filters)}`
        : null;

    return (
        <AccountLayout
            title={`Submission · ${submission.name}`}
            description="Recruitment Partnership candidate submission"
            breadcrumbs={[
                { label: 'Hiring', href: '/account/hiring' },
                { label: 'Recruitment Partnership', href: '/account/submissions' },
                ...(submission.requirement_href && submission.job?.title
                    ? [{ label: submission.job.title, href: submission.requirement_href }]
                    : []),
                { label: submission.name },
            ]}
        >
            <div className="mx-auto max-w-3xl space-y-4 lg:max-w-none">
                <Card as="section" density="compact">
                    <Link href={backHref} className="text-sm font-semibold text-brand hover:underline">
                        ← Back to submissions
                    </Link>
                    <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-muted">{company.name}</p>
                    <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-muted">Candidate submission</p>
                    <h1 className="mt-1 text-xl font-semibold text-ink">{submission.job?.title ?? 'Recruitment requirement'}</h1>
                </Card>

                <Card as="section" density="standard">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 className="text-lg font-semibold text-ink">{submission.name}</h2>
                            {submission.designation ? (
                                <p className="mt-1 text-sm font-medium text-ink">{submission.designation}</p>
                            ) : null}
                            <p className="mt-1 text-sm text-muted">
                                {[submission.location, submission.experience, submission.qualification]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                        </div>
                        <StatusBadge status={statusLabel} />
                    </div>
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted">Submitted</dt>
                            <dd className="font-medium text-ink">{formatAppliedAt(submission.submitted_at ?? null)}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Submission status</dt>
                            <dd className="font-medium text-ink">{statusLabel}</dd>
                        </div>
                        {submission.business_id ? (
                            <div>
                                <dt className="text-muted">Submission ID</dt>
                                <dd className="font-medium text-ink">{submission.business_id}</dd>
                            </div>
                        ) : null}
                    </dl>
                    <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        {profileHref ? (
                            <Button href={profileHref} variant="secondary" className="min-h-11 w-full sm:w-auto">
                                View candidate profile
                            </Button>
                        ) : (
                            <p className="text-sm text-muted">Public profile not shared by this candidate.</p>
                        )}
                        {submission.requirement_href ? (
                            <Button href={submission.requirement_href} variant="link" className="min-h-11">
                                View requirement
                            </Button>
                        ) : null}
                    </div>
                </Card>

                <Card as="section" density="compact">
                    <h2 className="text-base font-semibold text-ink">Resume</h2>
                    {submission.has_resume && submission.resume_url ? (
                        <div className="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            <a
                                href={`${submission.resume_url}?disposition=inline`}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                            >
                                View
                            </a>
                            <a
                                href={submission.resume_url}
                                className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                            >
                                Download
                            </a>
                        </div>
                    ) : (
                        <p className="mt-2 text-sm text-muted">Resume not available for this submission snapshot.</p>
                    )}
                </Card>

                {submission.timeline.length > 0 ? (
                    <Card as="section" density="compact">
                        <h2 className="text-base font-semibold text-ink">Submission timeline</h2>
                        <ol className="mt-3 space-y-3 border-l-2 border-line pl-4">
                            {submission.timeline.map((event) => (
                                <li key={event.key} className="text-sm">
                                    <p className="font-medium text-ink">{event.label}</p>
                                    {event.at ? <p className="text-muted">{formatAppliedAt(event.at)}</p> : null}
                                </li>
                            ))}
                        </ol>
                    </Card>
                ) : null}

                <Card as="section" density="compact" className="bg-surface/40">
                    <h2 className="text-base font-semibold text-ink">Next steps</h2>
                    <p className="mt-2 text-sm text-muted">
                        OurNear recruitment operations manage review, selection, and placement. Contact us if you need
                        support on this requirement.
                    </p>
                    <Button href={support_href} variant="secondary" className="mt-4 min-h-11">
                        Request recruitment support
                    </Button>
                </Card>
            </div>
        </AccountLayout>
    );
}
