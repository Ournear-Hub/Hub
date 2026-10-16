import { Link } from '@inertiajs/react';
import HiringActivationStrip from '@/Components/Account/Hiring/HiringActivationStrip';
import Button from '@/Components/atoms/Button';
import OurNearIcon from '@/icons/OurNearIcon';
import type { AccountEmployerHiringWorkspace, AccountEmployerVisiblePerson } from '@/types/account';

type Gate = {
    hiring_active: boolean;
    message: string;
    email_verified: boolean;
    mobile_verified: boolean;
    unlimited_publishing?: boolean;
};

type Props = {
    workspace: AccountEmployerHiringWorkspace;
    gate: Gate;
};

const cardClass = 'rounded-xl border border-line bg-white p-5 sm:p-6';

function formatDate(iso: string | null | undefined): string {
    if (!iso) {
        return '—';
    }
    try {
        return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(iso));
    } catch {
        return iso;
    }
}

function KpiCard({
    href,
    icon,
    value,
    label,
    description,
}: {
    href: string;
    icon: string;
    value: number;
    label: string;
    description: string;
}) {
    return (
        <Link href={href} className={`${cardClass} block transition hover:border-brand/30 hover:shadow-sm`}>
            <div className="flex items-start justify-between gap-3">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                    <OurNearIcon name={icon} size="md" decorative />
                </span>
                <p className="text-3xl font-semibold tabular-nums text-ink">{value}</p>
            </div>
            <p className="mt-3 text-sm font-semibold text-ink">{label}</p>
            <p className="mt-1 text-xs text-muted">{description}</p>
        </Link>
    );
}

function ReadinessFallback({ workspace, gate }: Props) {
    const { eligibility } = workspace;
    const showVerifyEmail = !workspace.email_verified;
    const showVerifyMobile = !workspace.mobile_verified;
    const showCreateJob = workspace.can_post_job && workspace.jobs_count === 0;
    const showCompleteProfile =
        !workspace.eligibility.eligible || workspace.posting_restricted || !gate.hiring_active;

    if (!showVerifyEmail && !showVerifyMobile && !showCreateJob && !showCompleteProfile) {
        return null;
    }

    return (
        <section className="rounded-xl border border-brand/20 bg-brand/5 p-4 sm:p-5" aria-labelledby="company-readiness-banner">
            <h2 id="company-readiness-banner" className="text-sm font-semibold text-ink">
                Company readiness
            </h2>
            <p className="mt-1 text-sm text-muted">
                {eligibility.reason ??
                    gate.message ??
                    'Complete verification and company details to publish jobs and manage applicants.'}
            </p>
            {eligibility.next_action ? <p className="mt-2 text-sm text-ink">{eligibility.next_action}</p> : null}
            <div className="mt-4 flex flex-wrap gap-3">
                {showVerifyEmail ? (
                    <Button surface="public" href="/account/settings/verification" variant="primary" className="min-h-11">
                        Verify Email
                    </Button>
                ) : null}
                {showVerifyMobile ? (
                    <Button surface="public" type="button" variant="secondary" className="min-h-11" disabled>
                        Verify Mobile (coming soon)
                    </Button>
                ) : null}
                {showCompleteProfile ? (
                    <Button surface="public" href="/account/company" variant="secondary" className="min-h-11">
                        Company Profile
                    </Button>
                ) : null}
                {showCreateJob ? (
                    <Button surface="public" href="/account/jobs/create" variant="primary" className="min-h-11">
                        Create your first job
                    </Button>
                ) : null}
            </div>
        </section>
    );
}

function NextActionsPanel({ actions }: { actions: AccountEmployerHiringWorkspace['next_actions'] }) {
    const pending = actions.filter((action) => action.kind === 'pending');
    const items = pending.length > 0 ? pending : actions;

    return (
        <section className={cardClass}>
            <h2 className="text-base font-semibold text-ink">Next actions</h2>
            <p className="mt-1 text-sm text-muted">Operational tasks for your recruitment workspace.</p>
            {items.length === 0 ? (
                <p className="mt-4 text-sm text-muted">You are up to date on your recruitment workspace.</p>
            ) : (
                <ul className="mt-4 space-y-2">
                    {items.map((action) => (
                        <li key={`${action.href}-${action.label}`}>
                            <Link
                                href={action.href}
                                className={`block rounded-md px-3 py-2.5 text-sm hover:bg-surface ${
                                    action.kind === 'pending' ? 'bg-accent/10 font-medium text-ink' : 'text-ink'
                                }`}
                            >
                                {action.label}
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

function PipelineSection({ stages }: { stages: AccountEmployerHiringWorkspace['pipeline_hiring_stages'] }) {
    if (!stages?.length) {
        return null;
    }

    return (
        <section className={cardClass}>
            <h2 className="text-base font-semibold text-ink">Hiring Pipeline</h2>
            <p className="mt-1 text-sm text-muted">Application counts by recruitment stage across your jobs.</p>
            <div className="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                {stages.map((stage) => (
                    <Link
                        key={stage.key}
                        href={stage.href}
                        className="rounded-lg border border-line px-3 py-3 text-center transition hover:border-brand/40 hover:bg-brand/[0.04]"
                    >
                        <p className="text-2xl font-semibold tabular-nums text-ink">{stage.count}</p>
                        <p className="mt-1 text-xs font-medium text-muted">{stage.label}</p>
                    </Link>
                ))}
            </div>
        </section>
    );
}

function StaffingSummarySection({ summary }: { summary: AccountEmployerHiringWorkspace['staffing_summary'] }) {
    if (!summary) {
        return null;
    }

    return (
        <section className={cardClass}>
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-base font-semibold text-ink">Staffing</h2>
                    <p className="mt-1 text-sm text-muted">Workforce and manpower requests submitted to OurNear.</p>
                </div>
                <Link href={summary.href} className="text-sm font-semibold text-brand hover:underline">
                    View staffing requests
                </Link>
            </div>
            <div className="mt-4 grid gap-3 sm:grid-cols-3">
                <div className="rounded-lg border border-line px-3 py-3">
                    <p className="text-2xl font-semibold tabular-nums text-ink">{summary.submitted}</p>
                    <p className="mt-1 text-xs font-medium text-muted">Requirements submitted</p>
                </div>
                <div className="rounded-lg border border-line px-3 py-3">
                    <p className="text-2xl font-semibold tabular-nums text-ink">{summary.in_progress}</p>
                    <p className="mt-1 text-xs font-medium text-muted">In progress</p>
                </div>
                <div className="rounded-lg border border-line px-3 py-3">
                    <p className="text-2xl font-semibold tabular-nums text-ink">{summary.completed}</p>
                    <p className="mt-1 text-xs font-medium text-muted">Completed</p>
                </div>
            </div>
        </section>
    );
}

function RecentJobsTable({ jobs, canPostJob }: { jobs: AccountEmployerHiringWorkspace['recent_jobs']; canPostJob: boolean }) {
    return (
        <section className={cardClass}>
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-base font-semibold text-ink">Recent jobs</h2>
                    <p className="mt-1 text-sm text-muted">Latest job postings in your workspace.</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Link href="/account/jobs" className="text-sm font-semibold text-brand hover:underline">
                        View all jobs
                    </Link>
                    {canPostJob ? (
                        <Link href="/account/jobs/create" className="text-sm font-semibold text-brand hover:underline">
                            Create job
                        </Link>
                    ) : null}
                </div>
            </div>
            {!jobs?.length ? (
                <p className="mt-6 text-sm text-muted">
                    No jobs yet.{' '}
                    {canPostJob ? (
                        <Link href="/account/jobs/create" className="font-semibold text-brand hover:underline">
                            Create your first job
                        </Link>
                    ) : (
                        <Link href="/account/company" className="font-semibold text-brand hover:underline">
                            Complete your company profile
                        </Link>
                    )}{' '}
                    to start receiving applicants.
                </p>
            ) : (
                <div className="mt-4 overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead>
                            <tr className="border-b border-line text-xs uppercase tracking-wide text-muted">
                                <th className="py-2 pr-4 font-semibold">Job</th>
                                <th className="py-2 pr-4 font-semibold">Status</th>
                                <th className="py-2 pr-4 font-semibold">Location</th>
                                <th className="py-2 pr-4 font-semibold">Applicants</th>
                                <th className="py-2 pr-4 font-semibold">Updated</th>
                                <th className="py-2 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {jobs.map((job) => (
                                <tr key={job.slug ?? `${job.title}-${job.updated_at}`} className="border-b border-line/70">
                                    <td className="max-w-[12rem] py-3 pr-4 font-medium text-ink">
                                        <span className="line-clamp-2">{job.title}</span>
                                    </td>
                                    <td className="py-3 pr-4 text-muted">{job.status_label}</td>
                                    <td className="max-w-[8rem] py-3 pr-4 text-muted">
                                        <span className="line-clamp-1">{job.location ?? '—'}</span>
                                    </td>
                                    <td className="py-3 pr-4 tabular-nums text-ink">{job.applications_count}</td>
                                    <td className="whitespace-nowrap py-3 pr-4 text-muted">{formatDate(job.updated_at)}</td>
                                    <td className="py-3">
                                        <div className="flex flex-wrap gap-2">
                                            <Link
                                                href={
                                                    job.href ||
                                                    (job.slug ? `/account/jobs/${job.slug}/applicants` : '/account/jobs')
                                                }
                                                className="font-semibold text-brand hover:underline"
                                                aria-label={job.title ? `View applicants for ${job.title}` : 'View job applicants'}
                                            >
                                                View
                                            </Link>
                                            <Link href={job.edit_href} className="font-semibold text-brand hover:underline">
                                                Edit
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </section>
    );
}

function applicantStatusLabel(applicant: AccountEmployerVisiblePerson): string {
    const extended = applicant as AccountEmployerVisiblePerson & {
        ats_stage_label?: string;
        status_label?: string;
    };

    return extended.ats_stage_label ?? extended.status_label ?? applicant.status;
}

function applicantViewHref(applicant: AccountEmployerVisiblePerson): string | null {
    const extended = applicant as AccountEmployerVisiblePerson & { application_href?: string | null };

    if (extended.application_href) {
        return extended.application_href;
    }

    if (applicant.job?.slug) {
        return `/account/jobs/${applicant.job.slug}/applicants`;
    }

    return null;
}

function RecentApplicantsTable({ applicants }: { applicants: AccountEmployerVisiblePerson[] }) {
    return (
        <section className={cardClass}>
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-base font-semibold text-ink">Recent applicants</h2>
                    <p className="mt-1 text-sm text-muted">Latest applications on your job postings.</p>
                </div>
                <Link href="/account/hiring/applications" className="text-sm font-semibold text-brand hover:underline">
                    View all applications
                </Link>
            </div>
            {applicants.length === 0 ? (
                <p className="mt-6 text-sm text-muted">No applicants on your jobs yet. Share live jobs to start receiving applications.</p>
            ) : (
                <div className="mt-4 overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead>
                            <tr className="border-b border-line text-xs uppercase tracking-wide text-muted">
                                <th className="py-2 pr-4 font-semibold">Candidate</th>
                                <th className="py-2 pr-4 font-semibold">Applied job</th>
                                <th className="py-2 pr-4 font-semibold">Status</th>
                                <th className="py-2 pr-4 font-semibold">Applied</th>
                                <th className="py-2 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {applicants.map((applicant) => {
                                const viewHref = applicantViewHref(applicant);

                                return (
                                    <tr key={applicant.id} className="border-b border-line/70">
                                        <td className="py-3 pr-4 font-medium text-ink">{applicant.name}</td>
                                        <td className="max-w-[10rem] py-3 pr-4 text-muted">
                                            <span className="line-clamp-2">{applicant.job?.title ?? '—'}</span>
                                        </td>
                                        <td className="py-3 pr-4 text-muted">{applicantStatusLabel(applicant)}</td>
                                        <td className="whitespace-nowrap py-3 pr-4 text-muted">{formatDate(applicant.applied_at)}</td>
                                        <td className="py-3">
                                            {viewHref ? (
                                                <Link href={viewHref} className="font-semibold text-brand hover:underline">
                                                    View
                                                </Link>
                                            ) : (
                                                <span className="text-muted">—</span>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}
        </section>
    );
}

function HiringQuickLinks() {
    const links = [
        { href: '/account/jobs', label: 'Jobs' },
        { href: '/account/hiring/applications', label: 'Applications' },
        { href: '/account/company', label: 'Company profile' },
        { href: '/account/coordinated-talent-pool', label: 'OurNear submissions' },
    ];

    return (
        <nav className="flex flex-wrap gap-2 text-sm" aria-label="Hiring workspace shortcuts">
            {links.map((link) => (
                <Link
                    key={link.href}
                    href={link.href}
                    className="rounded-full border border-line bg-white px-3 py-1.5 font-medium text-ink hover:border-brand/40 hover:text-brand"
                >
                    {link.label}
                </Link>
            ))}
        </nav>
    );
}

export default function CompanyWorkspaceOverview({ workspace, gate }: Props) {
    const pendingActions = workspace.actions_required?.length
        ? workspace.actions_required
        : workspace.next_actions.filter((action) => action.kind === 'pending');
    const showFirstJobHero = workspace.can_post_job && workspace.jobs_count === 0;

    return (
        <div className="flex min-w-0 flex-col gap-6">
            <header className="min-w-0">
                <h1 className="text-xl font-semibold text-ink sm:text-2xl">Company Workspace</h1>
                <p className="mt-1 text-sm text-muted">
                    Manage recruitment, job postings, applicants, and hiring pipeline activity for your company.
                </p>
                <div className="mt-4">
                    <HiringQuickLinks />
                </div>
            </header>

            {showFirstJobHero ? (
                <section className="rounded-xl border border-brand/30 bg-brand/10 p-5 sm:p-6">
                    <h2 className="text-lg font-semibold text-ink">Start hiring</h2>
                    <p className="mt-2 text-sm text-muted">
                        Publish your first job to receive applications. This is your self-service ATS — separate from OurNear
                        coordinated Talent Pool submissions.
                    </p>
                    <Button surface="public" href="/account/jobs/create" variant="primary" className="mt-4 min-h-11">
                        Create Job
                    </Button>
                </section>
            ) : null}

            {gate.hiring_active ? (
                <HiringActivationStrip gate={gate} canPostJob={workspace.can_post_job} jobsCount={workspace.jobs_count} />
            ) : (
                <ReadinessFallback workspace={workspace} gate={gate} />
            )}

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <KpiCard
                    href="/account/jobs"
                    icon="briefcase"
                    value={workspace.jobs_count}
                    label="Total Jobs"
                    description="All job postings you own"
                />
                <KpiCard
                    href="/account/jobs"
                    icon="check"
                    value={workspace.active_jobs_count}
                    label="Active Jobs"
                    description="Live listings accepting applicants"
                />
                <KpiCard
                    href="/account/hiring/applications"
                    icon="user"
                    value={workspace.applicants_count}
                    label="Total Applicants"
                    description="Applications across your jobs"
                />
                <KpiCard
                    href="/account/hiring"
                    icon="clipboard"
                    value={workspace.pending_actions_count ?? pendingActions.length}
                    label="Pending Actions"
                    description="Items needing your attention"
                />
            </div>

            <div className="grid min-w-0 gap-6 lg:grid-cols-2">
                <PipelineSection stages={workspace.pipeline_hiring_stages} />
                <NextActionsPanel actions={workspace.next_actions} />
            </div>

            <div className="grid min-w-0 gap-6 lg:grid-cols-2">
                <StaffingSummarySection summary={workspace.staffing_summary} />
                <section className={cardClass}>
                    <h2 className="text-base font-semibold text-ink">Applicant pipeline</h2>
                    <p className="mt-1 text-sm text-muted">
                        Review and manage applicants in your self-service ATS (distinct from OurNear coordinated Talent Pool
                        submissions).
                    </p>
                    <Link href="/account/hiring/applications" className="mt-4 inline-block text-sm font-semibold text-brand hover:underline">
                        Open applications workspace
                    </Link>
                </section>
            </div>

            <div className="grid min-w-0 gap-6 xl:grid-cols-2">
                <RecentJobsTable jobs={workspace.recent_jobs} canPostJob={workspace.can_post_job} />
                <RecentApplicantsTable applicants={workspace.recent_applicants} />
            </div>
        </div>
    );
}
