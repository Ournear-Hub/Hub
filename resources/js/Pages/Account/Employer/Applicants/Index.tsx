import { Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import EmployerApplicantPipeline, {
    type PipelineStageSummary,
} from '@/Components/Account/Recruiter/EmployerApplicantPipeline';
import EmployerApplicantSearch from '@/Components/Account/Recruiter/EmployerApplicantSearch';
import RecruiterCandidateSummary, {
    type RecruiterCandidateSummaryData,
} from '@/Components/Account/Recruiter/RecruiterCandidateSummary';
import Button from '@/Components/Public/Button';
import Pagination from '@/Components/Pagination';
import type { Paginator } from '@/types/jobs';

type Props = {
    job: {
        title: string;
        business_id: string | null;
        slug: string;
        location?: string | null;
        status_label?: string | null;
    };
    applications: Paginator<RecruiterCandidateSummaryData>;
    pipeline_summary: PipelineStageSummary[];
    filters: {
        q: string;
        status: string;
        stage: string;
    };
    status_options: Array<{ value: string; label: string }>;
};

function applicantsListHref(jobSlug: string, filters: Props['filters']): string {
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
    return `/account/jobs/${jobSlug}/applicants${query ? `?${query}` : ''}`;
}

function applicationDetailHref(
    base: string | null | undefined,
    jobSlug: string,
    filters: Props['filters'],
): string | null {
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
    if (filters.stage) {
        params.set('stage', filters.stage);
    }
    const query = params.toString();
    return query ? `${base}?${query}` : base;
}

function profileContextQuery(
    job: Props['job'],
    applicant: RecruiterCandidateSummaryData,
    filters: Props['filters'],
): string {
    const params = new URLSearchParams();
    params.set('back', applicantsListHref(job.slug, filters));
    params.set('job_title', applicant.job?.title ?? job.title);
    params.set('application_status', applicant.status_label ?? applicant.status);
    if (applicant.applied_at) {
        params.set('applied_at', applicant.applied_at);
    }
    if (applicant.application_href) {
        params.set(
            'application_href',
            applicationDetailHref(applicant.application_href, job.slug, filters) ?? applicant.application_href,
        );
    }
    return params.toString();
}

export default function EmployerApplicants({ job, applications, filters, status_options, pipeline_summary }: Props) {
    const empty = applications.data.length === 0;
    const hasFilters = Boolean(filters.q || filters.status || filters.stage);
    const noApplicants = empty && applications.total === 0 && !hasFilters;
    const noMatches = empty && !noApplicants;

    return (
        <AccountLayout
            title={`Applicants · ${job.title}`}
            description="Direct Hiring / ATS — candidates who applied to this job."
            breadcrumbs={[
                { label: 'Dashboard', href: '/account' },
                { label: 'Hiring', href: '/account/hiring' },
                { label: 'Jobs', href: '/account/jobs' },
                { label: job.title, href: `/account/jobs/${job.slug}/edit` },
                { label: 'Applicants' },
            ]}
        >
            <section className="mb-4 rounded-xl border border-line bg-white p-4 sm:p-5">
                <p className="text-xs font-semibold uppercase tracking-wide text-muted">Applicants for</p>
                <h1 className="mt-1 text-xl font-semibold text-ink">{job.title}</h1>
                <p className="mt-1 text-sm text-muted">
                    {[job.business_id, job.location, job.status_label].filter(Boolean).join(' · ')}
                </p>
                <p className="mt-2 text-sm font-semibold text-ink">
                    {pipeline_summary.find((row) => row.key === 'all')?.count ?? applications.total} applicants
                </p>
                <div className="mt-3 flex flex-wrap gap-3 text-sm font-semibold">
                    <Link href={`/account/jobs/${job.slug}/edit`} className="text-brand hover:underline">
                        Edit job
                    </Link>
                    <Link href={`/jobs/${job.slug}`} className="text-brand hover:underline">
                        View public job
                    </Link>
                    <Link href="/account/jobs" className="text-brand hover:underline">
                        All jobs
                    </Link>
                </div>
            </section>

            <div className="mb-4 rounded-xl border border-line bg-white p-4 sm:p-5">
                <h2 className="text-sm font-semibold text-ink">Pipeline</h2>
                <div className="mt-3">
                    <EmployerApplicantPipeline
                        jobSlug={job.slug}
                        summary={pipeline_summary}
                        activeStage={filters.stage}
                        searchQuery={filters.q}
                        statusFilter={filters.status}
                    />
                </div>
            </div>

            <EmployerApplicantSearch
                jobSlug={job.slug}
                filters={filters}
                statusOptions={status_options}
                resultCount={applications.data.length}
            />

            {noApplicants ? (
                <div className="mt-4 rounded-xl border border-dashed border-line bg-white p-8 text-center">
                    <p className="text-sm text-muted">No applicants for this job yet.</p>
                    <Button href="/account/jobs" variant="secondary" className="mt-4 min-h-11">
                        View jobs
                    </Button>
                </div>
            ) : null}

            {noMatches ? (
                <div className="mt-4 rounded-xl border border-dashed border-line bg-white p-8 text-center">
                    <p className="text-sm text-muted">No applicants match these filters.</p>
                    <Button
                        href={`/account/jobs/${job.slug}/applicants`}
                        variant="secondary"
                        className="mt-4 min-h-11"
                    >
                        Clear filters
                    </Button>
                </div>
            ) : null}

            {!empty ? (
                <div className="mt-4 space-y-3">
                    <p className="text-sm text-muted">
                        {applications.total} total applicant{applications.total === 1 ? '' : 's'}
                    </p>
                    {applications.data.map((applicant) => (
                        <RecruiterCandidateSummary
                            key={applicant.id}
                            candidate={{
                                ...applicant,
                                application_href: applicationDetailHref(
                                    applicant.application_href,
                                    job.slug,
                                    filters,
                                ),
                            }}
                            jobTitle={job.title}
                            mode="application"
                            profileQuery={profileContextQuery(job, applicant, filters)}
                        />
                    ))}
                    <Pagination links={applications.links} />
                </div>
            ) : null}

            <div className="mt-6 rounded-xl border border-line bg-surface p-4 text-xs text-muted">
                <p>
                    The application resume is a historical snapshot from when the candidate applied. It is not the
                    candidate&apos;s private Resume Library.
                </p>
            </div>
        </AccountLayout>
    );
}
