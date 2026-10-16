import { Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import EmployerPartnershipSearch from '@/Components/Account/Recruiter/EmployerPartnershipSearch';
import RecruiterCandidateSummary, {
    type RecruiterCandidateSummaryData,
} from '@/Components/Account/Recruiter/RecruiterCandidateSummary';
import RecruitmentRequirementCard, {
    type RecruitmentRequirement,
} from '@/Components/Account/Recruiter/RecruitmentRequirementCard';
import BackNavLink from '@/Components/molecules/BackNavLink';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/Account/StatusBadge';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';
import type { Paginator } from '@/types/jobs';

type SubmittedCandidate = RecruiterCandidateSummaryData & {
    submission_id?: number;
    submitted_at: string | null;
};

type WorkspaceSummary = {
    active_requirements: number;
    candidate_submissions: number;
    selected_count: number;
    pending_review_count: number;
};

type Props = {
    company: {
        name: string;
        business_id: string | null;
    };
    summary: WorkspaceSummary;
    requirements: RecruitmentRequirement[];
    recent_submissions: SubmittedCandidate[];
    submissions: Paginator<SubmittedCandidate>;
    filters: {
        q: string;
        status: string;
        job: string;
    };
    status_options: Array<{ value: string; label: string }>;
    support_href: string;
};

function submissionDetailHref(base: string | null | undefined, filters: Props['filters']): string | null {
    if (!base) {
        return null;
    }
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
    return query ? `${base}?${query}` : base;
}

function submissionsListHref(filters: Props['filters']): string {
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
    return `/account/submissions${query ? `?${query}` : ''}`;
}

function profileContextQuery(candidate: SubmittedCandidate, filters: Props['filters'], listBack: string): string {
    const params = new URLSearchParams();
    params.set('back', listBack);
    params.set('job_title', candidate.job?.title ?? 'Recruitment requirement');
    params.set('application_status', candidate.status_label ?? candidate.status);
    if (candidate.submitted_at) {
        params.set('applied_at', candidate.submitted_at);
    }
    const submissionHref = submissionDetailHref(candidate.submission_href, filters);
    if (submissionHref) {
        params.set('application_href', submissionHref);
    }
    return params.toString();
}

export default function EmployerSubmissions({
    company,
    summary,
    requirements,
    recent_submissions,
    submissions,
    filters,
    status_options,
    support_href,
}: Props) {
    const listBack = submissionsListHref(filters);
    const empty = submissions.data.length === 0;
    const hasFilters = Boolean(filters.q || filters.status || filters.job);
    const noSubmissions = empty && submissions.total === 0 && !hasFilters && summary.candidate_submissions === 0;
    const noMatches = empty && !noSubmissions;

    return (
        <AccountLayout
            title="Recruitment Partnership"
            description="Work with OurNear on recruitment requirements and candidate submissions."
            breadcrumbs={[
                { label: 'Dashboard', href: '/account' },
                { label: 'Hiring', href: '/account/hiring' },
                { label: 'Recruitment Partnership' },
            ]}
        >
            <div className="mx-auto max-w-3xl space-y-4 lg:max-w-none">
                <header className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted">Recruitment Partnership</p>
                    <h1 className="mt-1 text-xl font-semibold text-ink sm:text-2xl">{company.name}</h1>
                    {company.business_id ? <p className="mt-1 text-sm text-muted">{company.business_id}</p> : null}
                    <p className="mt-3 text-sm text-muted">
                        Work with OurNear to manage recruitment requirements and candidate submissions. This workspace is
                        separate from Direct Hiring / ATS on your job applicants.
                    </p>
                    <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        <Button surface="public" href={support_href} variant="primary" className="min-h-11 w-full sm:w-auto">
                            Request recruitment support
                        </Button>
                        <BackNavLink href="/account/hiring" label="Back to Hiring Command Center" />
                    </div>
                </header>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <Card padding="compact" as="article">
                        <p className="text-xs text-muted">Active requirements</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.active_requirements}</p>
                    </Card>
                    <Card padding="compact" as="article">
                        <p className="text-xs text-muted">Candidate submissions</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.candidate_submissions}</p>
                    </Card>
                    <Card padding="compact" as="article">
                        <p className="text-xs text-muted">Pending review</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.pending_review_count}</p>
                    </Card>
                    <Card padding="compact" as="article">
                        <p className="text-xs text-muted">Selected</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{summary.selected_count}</p>
                    </Card>
                </div>

                {requirements.length > 0 ? (
                    <section aria-labelledby="active-requirements">
                        <h2 id="active-requirements" className="text-base font-semibold text-ink">
                            Active requirements
                        </h2>
                        <div className="mt-3 space-y-3">
                            {requirements.map((requirement) => (
                                <RecruitmentRequirementCard key={requirement.job_id} requirement={requirement} />
                            ))}
                        </div>
                    </section>
                ) : null}

                {recent_submissions.length > 0 && !hasFilters ? (
                    <Card as="section" padding="compact">
                        <h2 className="text-base font-semibold text-ink">Recent candidate submissions</h2>
                        <ul className="mt-3 divide-y divide-line">
                            {recent_submissions.map((candidate) => (
                                <li key={candidate.submission_id ?? candidate.id} className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="min-w-0 text-sm">
                                        <p className="font-medium text-ink">{candidate.name}</p>
                                        <p className="text-muted">{candidate.job?.title ?? 'Requirement'}</p>
                                        <div className="mt-1 flex flex-wrap items-center gap-2">
                                            <StatusBadge status={candidate.status_label ?? candidate.status} />
                                            {candidate.submitted_at ? (
                                                <span className="text-muted">{formatAppliedAt(candidate.submitted_at)}</span>
                                            ) : null}
                                        </div>
                                    </div>
                                    {candidate.submission_href ? (
                                        <Link
                                            href={candidate.submission_href}
                                            className="inline-flex min-h-11 shrink-0 items-center text-sm font-semibold text-brand hover:underline"
                                        >
                                            View
                                        </Link>
                                    ) : null}
                                </li>
                            ))}
                        </ul>
                    </Card>
                ) : null}

                {noSubmissions ? (
                    <div className="rounded-xl border border-dashed border-line bg-white p-8 text-center">
                        <p className="text-sm text-muted">No recruitment requirements or candidate submissions yet.</p>
                        <p className="mt-2 text-sm text-muted">
                            When OurNear shares candidates for your roles, they will appear here grouped by requirement.
                        </p>
                        <Button surface="public" href={support_href} variant="secondary" className="mt-4 min-h-11">
                            Request recruitment support
                        </Button>
                    </div>
                ) : (
                    <>
                        <EmployerPartnershipSearch
                            basePath="/account/submissions"
                            filters={filters}
                            statusOptions={status_options}
                            resultCount={submissions.data.length}
                        />

                        {noMatches ? (
                            <div className="rounded-xl border border-dashed border-line bg-white p-8 text-center">
                                <p className="text-sm text-muted">No requirements match these filters.</p>
                                <Button surface="public" href="/account/submissions" variant="secondary" className="mt-4 min-h-11">
                                    Clear filters
                                </Button>
                            </div>
                        ) : (
                            <section aria-labelledby="all-submissions">
                                <h2 id="all-submissions" className="text-base font-semibold text-ink">
                                    All candidate submissions
                                </h2>
                                <p className="mt-1 text-sm text-muted">{submissions.total} total</p>
                                <div className="mt-3 space-y-3">
                                    {submissions.data.map((candidate) => (
                                        <RecruiterCandidateSummary
                                            key={candidate.submission_id ?? candidate.id}
                                            candidate={{
                                                ...candidate,
                                                submission_href: submissionDetailHref(candidate.submission_href, filters),
                                            }}
                                            mode="submission"
                                            profileQuery={profileContextQuery(candidate, filters, listBack)}
                                        />
                                    ))}
                                    <Pagination links={submissions.links} />
                                </div>
                            </section>
                        )}
                    </>
                )}

                <Card as="section" padding="compact" className="bg-surface/40">
                    <h2 className="text-sm font-semibold text-ink">Need workforce supply?</h2>
                    <p className="mt-2 text-sm text-muted">
                        Staffing Services enquiries are separate from Recruitment Partnership submissions.
                    </p>
                    <Link href="/account/enquiries" className="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                        View staffing enquiries
                    </Link>
                </Card>
            </div>
        </AccountLayout>
    );
}
