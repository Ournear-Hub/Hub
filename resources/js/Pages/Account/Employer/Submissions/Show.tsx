import { Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import StatusBadge from '@/Components/Account/StatusBadge';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import ApplicantResumeOverview from '@/Components/organisms/ApplicantResumeOverview';
import ApplicationTimeline from '@/Components/organisms/ApplicationTimeline';
import BackNavLink from '@/Components/molecules/BackNavLink';
import DetailHeader from '@/Components/organisms/DetailHeader';
import DetailSection from '@/Components/organisms/DetailSection';
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
    const candidateSummaryLine = [submission.location, submission.experience, submission.qualification]
        .filter(Boolean)
        .join(' · ');
    const candidateDescription = [submission.designation, candidateSummaryLine].filter(Boolean).join(' · ') || undefined;
    const requirementTitle = submission.job?.title ?? 'Recruitment requirement';

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
                <Card as="section" padding="compact">
                    <BackNavLink href={backHref} label="Back to submissions" className="mb-3" />
                    <DetailHeader
                        className="mt-3"
                        eyebrow={company.name}
                        title={requirementTitle}
                        description="Candidate submission"
                    />
                </Card>

                <Card as="section" padding="standard">
                    <DetailHeader
                        headingLevel={2}
                        title={submission.name}
                        description={candidateDescription}
                        meta={<StatusBadge status={statusLabel} />}
                        actions={
                            <>
                                {profileHref ? (
                                    <Button surface="public" href={profileHref} variant="secondary" className="min-h-11 w-full sm:w-auto">
                                        View candidate profile
                                    </Button>
                                ) : (
                                    <p className="text-sm text-muted">Public profile not shared by this candidate.</p>
                                )}
                                {submission.requirement_href ? (
                                    <Button surface="public" href={submission.requirement_href} variant="link" className="min-h-11">
                                        View requirement
                                    </Button>
                                ) : null}
                            </>
                        }
                    />
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
                </Card>

                <ApplicantResumeOverview
                    emptyMessage="Resume not available for this submission snapshot."
                    footnote={
                        <p>Submission resume snapshots are historical files from when OurNear shared this candidate.</p>
                    }
                    actions={
                        submission.has_resume && submission.resume_url ? (
                            <>
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
                            </>
                        ) : undefined
                    }
                />

                <ApplicationTimeline
                    events={submission.timeline.map((event) => ({
                        ...event,
                        at: event.at ? formatAppliedAt(event.at) : null,
                    }))}
                    title="Submission timeline"
                    hideWhenEmpty
                    aria-label="Submission timeline"
                />

                <DetailSection
                    title="Next steps"
                    description="OurNear recruitment operations manage review, selection, and placement. Contact us if you need support on this requirement."
                    className="rounded-lg bg-surface/40 p-3 shadow-sm"
                >
                    <Button surface="public" href={support_href} variant="secondary" className="min-h-11">
                        Request recruitment support
                    </Button>
                </DetailSection>
            </div>
        </AccountLayout>
    );
}
