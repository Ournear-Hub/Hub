import { Link } from '@inertiajs/react';
import JobCard from '@/Components/Public/JobCard';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';
import Avatar from '@/Components/atoms/Avatar';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import OurNearIcon from '@/icons/OurNearIcon';
import { accountLayout, typography } from '@/design-system/classNames';
import type { AccountCandidateDashboard, AccountProfile } from '@/types/account';
import type { PublicJobCard } from '@/types/jobs';
import { publicJobsWorkspaceWithJob } from '@/utils/publicJobsUrl';

type Props = {
    dashboard: AccountCandidateDashboard;
    profile: AccountProfile;
};

export function CandidateProfileSummary({ dashboard, profile }: Props) {
    const header = dashboard.header;
    const completion = header.profile_completion ?? profile.profile_completion ?? 0;
    const headlineRaw = header.professional_headline?.trim() || profile.professional_headline?.trim() || null;
    const designation = header.designation?.trim() || profile.professional?.current_designation?.trim() || null;
    const headline = headlineRaw && headlineRaw !== designation ? headlineRaw : null;
    const username = header.username?.trim() || profile.username?.trim() || null;
    const organization = profile.professional?.current_company?.trim() || null;

    return (
        <Card as="section" padding="compact" className="min-w-0 w-full" aria-labelledby="candidate-profile-summary-heading">
            <div className="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div className="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:gap-5">
                    <Link
                        href="/account/profile#personal"
                        className="mx-auto shrink-0 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/35 sm:mx-0"
                    >
                        <Avatar
                            name={header.name}
                            src={header.photo_url ?? undefined}
                            size="profileSummary"
                            shape="circle"
                            className="ring-2 ring-line ring-offset-2 ring-offset-white"
                        />
                    </Link>
                    <div className="min-w-0 flex-1 text-center sm:text-left">
                        <h2 id="candidate-profile-summary-heading" className={`${typography.sectionTitle} text-brand`}>
                            {header.name}
                        </h2>
                        {username ? <p className={`${typography.secondary} mt-0.5`}>^{username}</p> : null}
                        {designation ? (
                            <p className={`${typography.body} mt-1 font-medium text-ink`}>{designation}</p>
                        ) : null}
                        {headline ? (
                            <p className={`${typography.secondary} mt-1 line-clamp-3 break-words`}>{headline}</p>
                        ) : null}
                        {organization ? (
                            <p className={`${typography.secondary} mt-1 line-clamp-2 break-words text-ink/85`}>
                                {organization}
                            </p>
                        ) : null}
                    </div>
                </div>
                <div className="min-w-0 lg:max-w-xs lg:text-right">
                    <div className="flex items-center justify-between gap-2 text-sm lg:justify-end">
                        <span className="font-medium text-ink">Profile completion</span>
                        <span className="font-semibold text-brand">{completion}%</span>
                    </div>
                    <div className="mt-1.5 h-2 overflow-hidden rounded-full bg-surface">
                        <div
                            className="h-full rounded-full bg-brand transition-[width]"
                            style={{ width: `${Math.min(100, Math.max(0, completion))}%` }}
                            role="progressbar"
                            aria-valuenow={completion}
                            aria-valuemin={0}
                            aria-valuemax={100}
                            aria-label="Profile completion"
                        />
                    </div>
                    <Button surface="public" href="/account/profile#personal" variant="secondary" className="mt-2.5 min-h-10 w-full sm:w-auto lg:ml-auto lg:inline-flex">
                        View Profile
                    </Button>
                </div>
            </div>
        </Card>
    );
}

export function CandidateMetricCards({ dashboard }: { dashboard: AccountCandidateDashboard }) {
    return (
        <div className="grid grid-cols-2 gap-3 sm:gap-3 lg:grid-cols-2 xl:max-w-3xl">
            <Link
                href="/account/applications"
                className="flex items-start gap-2.5 rounded-xl border border-line bg-white p-4 transition-colors hover:border-brand/35"
            >
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                    <OurNearIcon name="clipboard" size="md" decorative />
                </span>
                <span>
                    <p className="text-xs font-medium uppercase tracking-wide text-muted">Applications</p>
                    <p className="mt-0.5 text-2xl font-semibold text-ink">{dashboard.applications.count}</p>
                </span>
            </Link>
            <Link
                href="/account/saved-jobs"
                className="flex items-start gap-2.5 rounded-xl border border-line bg-white p-4 transition-colors hover:border-brand/35"
            >
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                    <OurNearIcon name="search" size="md" decorative />
                </span>
                <span>
                    <p className="text-xs font-medium uppercase tracking-wide text-muted">Saved jobs</p>
                    <p className="mt-0.5 text-2xl font-semibold text-ink">{dashboard.saved_jobs.count}</p>
                </span>
            </Link>
        </div>
    );
}

export function CandidateRecentApplications({ dashboard }: { dashboard: AccountCandidateDashboard }) {
    const recent = dashboard.applications.recent;

    return (
        <Card as="section" padding="compact" className="min-w-0">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h2 className={`flex items-center gap-2 ${typography.cardTitle}`}>
                    <OurNearIcon name="clipboard" size="sm" className="text-brand" decorative />
                    Recent applications
                </h2>
                <Link href="/account/applications" className="text-sm font-semibold text-brand hover:underline">
                    View all
                </Link>
            </div>
            {recent.length === 0 ? (
                <p className={`${typography.secondary} ${accountLayout.headingToBody}`}>You have not applied to any roles yet.</p>
            ) : (
                <ul className={`divide-y divide-line ${accountLayout.headingToBody}`}>
                    {recent.map((application) => (
                        <li
                            key={`${application.href ?? application.slug ?? application.title}-${application.applied_at}`}
                            className={`flex flex-col gap-2 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between ${accountLayout.listDivideRow}`}
                        >
                            <div className="min-w-0 text-sm">
                                <p className="font-medium text-ink">{application.title}</p>
                                {application.company ? <p className="text-muted">{application.company}</p> : null}
                                <p className="text-muted">
                                    {application.status_detail ?? application.status}
                                    {application.applied_at ? ` · ${formatAppliedAt(application.applied_at)}` : ''}
                                </p>
                            </div>
                            {application.href ? (
                                <Link
                                    href={application.href}
                                    className="inline-flex min-h-10 shrink-0 items-center text-sm font-semibold text-brand hover:underline"
                                >
                                    View
                                </Link>
                            ) : null}
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}

export function CandidateSavedJobsSummary({ dashboard }: { dashboard: AccountCandidateDashboard }) {
    const recent = dashboard.saved_jobs.recent;

    return (
        <Card as="section" padding="compact" className="min-w-0">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h2 className={`flex items-center gap-2 ${typography.cardTitle}`}>
                    <OurNearIcon name="search" size="sm" className="text-brand" decorative />
                    Saved jobs
                </h2>
                <Link href="/account/saved-jobs" className="text-sm font-semibold text-brand hover:underline">
                    View all
                </Link>
            </div>
            {recent.length === 0 ? (
                <p className={`${typography.secondary} ${accountLayout.headingToBody}`}>No saved jobs yet.</p>
            ) : (
                <ul className={`divide-y divide-line ${accountLayout.headingToBody}`}>
                    {recent.map((job) => (
                        <li key={`${job.slug ?? job.title}-${job.saved_at}`} className={`first:pt-0 last:pb-0 ${accountLayout.listDivideRow}`}>
                            <p className="text-sm font-medium text-ink">{job.title}</p>
                            <p className="text-xs text-muted">
                                {job.status_label}
                                {job.saved_at ? ` · Saved ${formatAppliedAt(job.saved_at)}` : ''}
                            </p>
                            <div className="mt-1.5 flex flex-wrap gap-2">
                                {job.can_view && job.slug ? (
                                    <Link
                                        href={publicJobsWorkspaceWithJob(job.slug)}
                                        className="text-sm font-semibold text-brand hover:underline"
                                    >
                                        View job
                                    </Link>
                                ) : (
                                    <span className="text-sm text-muted">Job no longer publicly available</span>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}

export function CandidateRecommendedJobs({ jobs }: { jobs: PublicJobCard[] }) {
    return (
        <Card as="section" padding="compact" className="min-w-0">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h2 className={`flex items-center gap-2 ${typography.cardTitle}`}>
                    <OurNearIcon name="briefcase" size="sm" className="text-brand" decorative />
                    Recommended jobs
                </h2>
                <Link href="/jobs" className="text-sm font-semibold text-brand hover:underline">
                    Browse jobs
                </Link>
            </div>
            {jobs.length === 0 ? (
                <p className={`${typography.secondary} ${accountLayout.headingToBody}`}>
                    No open roles match your profile right now. Check the jobs board for new listings.
                </p>
            ) : (
                <ul className={`grid grid-cols-1 gap-3 min-[480px]:grid-cols-2 ${accountLayout.headingToBody}`}>
                    {jobs.map((job) => (
                        <li key={job.slug}>
                            <JobCard job={job} compact openInWorkspace />
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}
