import { Link } from '@inertiajs/react';
import Button from '@/Components/Public/Button';
import Card from '@/Components/UI/Card';
import type { AccountEmployerHiringWorkspace } from '@/types/account';

const modules = [
    {
        key: 'ats',
        title: 'Direct Hiring / ATS',
        description: 'Manage your own hiring pipeline, jobs, and applicants.',
        href: '/account/jobs',
    },
    {
        key: 'partnership',
        title: 'Recruitment Partnership',
        description: 'Recruitment requirements and candidates OurNear submits to your company.',
        href: '/account/submissions',
    },
    {
        key: 'staffing',
        title: 'Staffing Services',
        description: 'Request and track manpower and workforce staffing support.',
        href: '/account/enquiries',
    },
    {
        key: 'talent',
        title: 'Talent Pool',
        description: 'Candidates from your job applications, grouped by hiring stage.',
        href: '/account/hiring/candidates',
    },
] as const;

function hiringFirstName(fullName: string): string {
    const parts = fullName.trim().split(/\s+/).filter(Boolean);
    return parts[0] ?? fullName;
}

function greeting(): string {
    const hour = new Date().getHours();
    if (hour < 12) {
        return 'Good morning';
    }
    if (hour < 17) {
        return 'Good afternoon';
    }
    return 'Good evening';
}

type Props = {
    workspace: AccountEmployerHiringWorkspace;
    canAdminHiring: boolean;
};

export default function EmployerHiringCommandCenter({ workspace, canAdminHiring }: Props) {
    const primaryAction = workspace.actions_required[0];

    return (
        <div className="mx-auto flex max-w-3xl flex-col gap-4 lg:max-w-none lg:gap-5">
            <header className="rounded-xl border border-line bg-white p-4 sm:p-5">
                <p className="text-sm text-muted">
                    {greeting()}, {hiringFirstName(workspace.user_name)}
                </p>
                <h1 className="mt-1 text-xl font-semibold text-ink sm:text-2xl">{workspace.company_name}</h1>
                <p className="mt-1 text-sm font-semibold text-brand">Hiring Command Center</p>
                <p className="mt-2 text-sm text-muted">
                    Manage jobs, candidates, and hiring activity from one workspace.
                </p>
                <div className="mt-3 flex flex-wrap gap-2">
                    <Link href="/account" className="text-sm font-semibold text-brand hover:underline">
                        Account home
                    </Link>
                    {canAdminHiring ? (
                        <Link href="/admin/hiring" className="text-sm font-semibold text-brand hover:underline">
                            Staff command center
                        </Link>
                    ) : null}
                </div>
            </header>

            <section
                className={
                    primaryAction
                        ? 'rounded-xl border-2 border-accent/30 bg-accent/5 p-5 sm:p-6'
                        : 'rounded-xl border border-line bg-white p-5 sm:p-6'
                }
                aria-labelledby="hiring-action-required"
            >
                <p className="text-xs font-semibold uppercase tracking-wide text-accent">Action required</p>
                {primaryAction ? (
                    <>
                        <h2 id="hiring-action-required" className="mt-2 text-lg font-semibold text-ink">
                            {primaryAction.label}
                        </h2>
                        <Button href={primaryAction.href} variant="primary" className="mt-4 min-h-11 w-full sm:w-auto">
                            Continue
                        </Button>
                        {workspace.actions_required.length > 1 ? (
                            <ul className="mt-4 space-y-2 border-t border-line pt-4 text-sm">
                                {workspace.actions_required.slice(1, 4).map((action) => (
                                    <li key={`${action.href}-${action.label}`}>
                                        <Link href={action.href} className="font-medium text-brand hover:underline">
                                            {action.label}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        ) : null}
                    </>
                ) : (
                    <>
                        <h2 id="hiring-action-required" className="mt-2 text-lg font-semibold text-ink">
                            Hiring is up to date
                        </h2>
                        <p className="mt-2 text-sm text-muted">No urgent hiring tasks need your attention right now.</p>
                        <Button href="/account/jobs" variant="secondary" className="mt-4 min-h-11 w-full sm:w-auto">
                            View jobs
                        </Button>
                    </>
                )}
            </section>

            <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                <Card density="compact" as="article">
                    <p className="text-xs text-muted">Active jobs</p>
                    <p className="mt-1 text-2xl font-semibold text-ink">{workspace.active_jobs_count}</p>
                </Card>
                <Card density="compact" as="article">
                    <p className="text-xs text-muted">Applications</p>
                    <p className="mt-1 text-2xl font-semibold text-ink">{workspace.applicants_count}</p>
                </Card>
                <Card density="compact" as="article">
                    <p className="text-xs text-muted">Submitted candidates</p>
                    <p className="mt-1 text-2xl font-semibold text-ink">{workspace.submissions_count}</p>
                </Card>
                <Card density="compact" as="article">
                    <p className="text-xs text-muted">Enquiries</p>
                    <p className="mt-1 text-2xl font-semibold text-ink">{workspace.enquiries_count}</p>
                </Card>
            </div>

            {workspace.can_post_job ? (
                <div className="flex flex-wrap gap-3">
                    <Button href="/account/jobs/create" variant="primary" className="min-h-11 w-full sm:w-auto">
                        Post a job
                    </Button>
                </div>
            ) : null}

            <div className="grid gap-4 md:grid-cols-2">
                <Card as="section" density="compact">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <h2 className="text-base font-semibold text-ink">Active jobs</h2>
                        <Link href="/account/jobs" className="text-sm font-semibold text-brand hover:underline">
                            View all jobs
                        </Link>
                    </div>
                    {workspace.active_jobs.length > 0 ? (
                        <ul className="mt-3 divide-y divide-line">
                            {workspace.active_jobs.map((job) => (
                                <li key={`${job.href}-${job.title}`} className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="min-w-0 text-sm">
                                        <p className="font-medium text-ink">{job.title}</p>
                                        {job.location ? <p className="text-muted">{job.location}</p> : null}
                                        <p className="text-muted">
                                            {job.applications_count} application{job.applications_count === 1 ? '' : 's'} · {job.status_label}
                                        </p>
                                    </div>
                                    <Link
                                        href={job.href}
                                        className="inline-flex min-h-11 shrink-0 items-center text-sm font-semibold text-brand hover:underline"
                                    >
                                        Review
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <>
                            <p className="mt-3 text-sm text-muted">No active jobs yet.</p>
                            {workspace.can_post_job ? (
                                <Link href="/account/jobs/create" className="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                                    Post a job
                                </Link>
                            ) : (
                                <Link href="/account/profile" className="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                                    Complete company profile
                                </Link>
                            )}
                        </>
                    )}
                </Card>

                <Card as="section" density="compact">
                    <h2 className="text-base font-semibold text-ink">Recent applicant activity</h2>
                    {workspace.recent_applicants.length > 0 ? (
                        <ul className="mt-3 divide-y divide-line">
                            {workspace.recent_applicants.slice(0, 5).map((applicant) => (
                                <li key={`applicant-${applicant.id}`} className="py-3 first:pt-0 last:pb-0 text-sm">
                                    <p className="font-medium text-ink">{applicant.name}</p>
                                    <p className="text-muted">{applicant.job?.title ?? 'Job'}</p>
                                    {applicant.job?.slug ? (
                                        <Link
                                            href={`/account/jobs/${applicant.job.slug}/applicants`}
                                            className="mt-1 inline-flex min-h-11 items-center font-semibold text-brand hover:underline"
                                        >
                                            Review
                                        </Link>
                                    ) : null}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <>
                            <p className="mt-3 text-sm text-muted">No applications yet.</p>
                            <Link href="/account/jobs" className="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                                View jobs
                            </Link>
                        </>
                    )}
                </Card>
            </div>

            <section aria-labelledby="hiring-modules">
                <h2 id="hiring-modules" className="text-base font-semibold text-ink">
                    Hiring modules
                </h2>
                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                    {modules.map((module) => (
                        <Card key={module.key} as="article" density="compact" className="flex flex-col">
                            <h3 className="font-semibold text-ink">{module.title}</h3>
                            <p className="mt-2 flex-1 text-sm text-muted">{module.description}</p>
                            <Link
                                href={module.href}
                                className="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                            >
                                Open
                            </Link>
                        </Card>
                    ))}
                </div>
            </section>
        </div>
    );
}
