import { Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import EmployerPartnershipSearch from '@/Components/Account/Recruiter/EmployerPartnershipSearch';
import RecruiterCandidateSummary, {
    type RecruiterCandidateSummaryData,
} from '@/Components/Account/Recruiter/RecruiterCandidateSummary';
import RecruitmentRequirementCard, {
    type RecruitmentRequirement,
} from '@/Components/Account/Recruiter/RecruitmentRequirementCard';
import Button from '@/Components/atoms/Button';
import Pagination from '@/Components/Pagination';
import type { Paginator } from '@/types/jobs';

type SubmittedCandidate = RecruiterCandidateSummaryData & {
    submission_id?: number;
    submitted_at: string | null;
};

type Props = {
    company: {
        name: string;
        business_id: string | null;
    };
    requirement: RecruitmentRequirement;
    submissions: Paginator<SubmittedCandidate>;
    filters: {
        q: string;
        status: string;
    };
    status_options: Array<{ value: string; label: string }>;
    support_href: string;
};

function requirementHref(slug: string, filters: Props['filters']): string {
    const params = new URLSearchParams();
    if (filters.q) {
        params.set('q', filters.q);
    }
    if (filters.status) {
        params.set('status', filters.status);
    }
    const query = params.toString();
    return `/account/submissions/requirements/${slug}${query ? `?${query}` : ''}`;
}

function submissionDetailHref(base: string | null | undefined, filters: Props['filters'], jobSlug: string): string | null {
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
    params.set('job', jobSlug);
    const query = params.toString();
    return query ? `${base}?${query}` : base;
}

export default function EmployerSubmissionRequirement({
    company,
    requirement,
    submissions,
    filters,
    status_options,
    support_href,
}: Props) {
    const empty = submissions.data.length === 0;
    const hasFilters = Boolean(filters.q || filters.status);
    const noMatches = empty && hasFilters;

    return (
        <AccountLayout
            title={`Requirement · ${requirement.title}`}
            description="Recruitment Partnership requirement"
            breadcrumbs={[
                { label: 'Hiring', href: '/account/hiring' },
                { label: 'Recruitment Partnership', href: '/account/submissions' },
                { label: requirement.title },
            ]}
        >
            <div className="mx-auto max-w-3xl space-y-4 lg:max-w-none">
                <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <Link href="/account/submissions" className="text-sm font-semibold text-brand hover:underline">
                        ← Recruitment Partnership
                    </Link>
                    <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-muted">{company.name}</p>
                    <h1 className="mt-1 text-xl font-semibold text-ink">Recruitment requirement</h1>
                </section>

                <RecruitmentRequirementCard requirement={requirement} />

                <EmployerPartnershipSearch
                    basePath={`/account/submissions/requirements/${requirement.slug}`}
                    filters={{ ...filters, job: requirement.slug }}
                    statusOptions={status_options}
                    resultCount={submissions.data.length}
                />

                {noMatches ? (
                    <div className="rounded-xl border border-dashed border-line bg-white p-8 text-center">
                        <p className="text-sm text-muted">No candidate submissions match these filters.</p>
                        <Button surface="public" href={requirementHref(requirement.slug, { q: '', status: '' })} variant="secondary" className="mt-4 min-h-11">
                            Clear filters
                        </Button>
                    </div>
                ) : (
                    <section aria-labelledby="requirement-submissions">
                        <h2 id="requirement-submissions" className="text-base font-semibold text-ink">
                            Candidate submissions
                        </h2>
                        <p className="mt-1 text-sm text-muted">{submissions.total} submitted for this role</p>
                        <div className="mt-3 space-y-3">
                            {submissions.data.map((candidate) => (
                                <RecruiterCandidateSummary
                                    key={candidate.submission_id ?? candidate.id}
                                    candidate={{
                                        ...candidate,
                                        submission_href: submissionDetailHref(
                                            candidate.submission_href,
                                            filters,
                                            requirement.slug,
                                        ),
                                    }}
                                    jobTitle={requirement.title}
                                    mode="submission"
                                />
                            ))}
                            <Pagination links={submissions.links} />
                        </div>
                    </section>
                )}

                <div className="rounded-xl border border-line bg-surface/40 p-4 text-sm text-muted">
                    <p>Status updates and placement steps are managed with OurNear recruitment operations.</p>
                    <Link href={support_href} className="mt-3 inline-flex min-h-11 items-center font-semibold text-brand hover:underline">
                        Request recruitment support
                    </Link>
                </div>
            </div>
        </AccountLayout>
    );
}
