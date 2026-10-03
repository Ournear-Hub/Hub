import { Link, usePage } from '@inertiajs/react';
import ProfileCompletionCenter from '@/Components/Profile/ProfileCompletionCenter';
import { isHireResumeReady } from '@/Components/Resume/resumeReadiness';
import {
    candidateFirstName,
    formatAppliedAt,
    resolveCandidateNextAction,
} from '@/Components/Account/CandidateHome/candidateHomeNextAction';
import ProfileShell from '@/Components/Profile/ProfileShell';
import Button from '@/Components/Public/Button';
import Card from '@/Components/UI/Card';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountCandidateDashboard, AccountEmployerDashboard, AccountProfile } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type AccountHomeProps = {
    role: 'candidate' | 'employer';
    profile: AccountProfile;
    dashboard?: AccountCandidateDashboard | AccountEmployerDashboard | null;
};

const cardClass = 'rounded-xl border border-line bg-white p-6';

function CandidateDashboard({
    dashboard,
    profile,
}: {
    dashboard: AccountCandidateDashboard;
    profile: AccountProfile;
}) {
    const header = dashboard.header;
    const nextAction = resolveCandidateNextAction(profile, dashboard);
    const page = usePage<SharedPageProps>().props;
    const unreadNotifications = page.accountAlerts?.unread ?? 0;
    const headline = header.designation?.trim() || 'Complete your professional profile';
    const hireResumeReady = isHireResumeReady(profile.completion_items);
    const myResumeUploaded = Boolean(dashboard.resume.primary);

    return (
        <div className="mx-auto flex max-w-3xl flex-col gap-4 lg:max-w-none lg:gap-5">
            <header className="rounded-xl border border-line bg-white p-4 sm:p-5">
                <p className="text-sm text-muted">Welcome back,</p>
                <h2 className="mt-1 text-xl font-semibold text-ink sm:text-2xl">{candidateFirstName(header.name)}</h2>
                <p className="mt-1 text-sm text-ink">{headline}</p>
                <div className="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                    <span className="font-semibold text-brand">{header.profile_completion}% profile complete</span>
                    <Link href="/account/profile" className="font-semibold text-brand hover:underline">
                        Continue profile
                    </Link>
                </div>
            </header>

            <section
                className="rounded-xl border-2 border-accent/30 bg-accent/5 p-5 sm:p-6"
                aria-labelledby="candidate-next-action"
            >
                <p className="text-xs font-semibold uppercase tracking-wide text-accent">Next action</p>
                <h2 id="candidate-next-action" className="mt-2 text-lg font-semibold text-ink sm:text-xl">
                    {nextAction.title}
                </h2>
                <p className="mt-2 text-sm text-muted">{nextAction.description}</p>
                <Button href={nextAction.href} variant="primary" className="mt-4 min-h-11 w-full sm:w-auto">
                    {nextAction.cta}
                </Button>
            </section>

            <div className="grid gap-4 md:grid-cols-2">
                <ProfileCompletionCenter profile={profile} compact />
                <Card as="section" density="compact" className="flex flex-col">
                    <h2 className="text-base font-semibold text-ink">Resume</h2>
                    <ul className="mt-3 space-y-2 text-sm text-ink">
                        <li>
                            <span className="text-muted">OurNear Resume:</span>{' '}
                            <span className="font-medium">{hireResumeReady ? 'Ready' : 'Needs profile'}</span>
                        </li>
                        <li>
                            <span className="text-muted">My Resume:</span>{' '}
                            <span className="font-medium">{myResumeUploaded ? 'Uploaded' : 'Not uploaded'}</span>
                        </li>
                    </ul>
                    <Link
                        href="/account/resumes"
                        className="mt-auto inline-flex min-h-11 items-center pt-4 text-sm font-semibold text-brand hover:underline"
                    >
                        Manage resumes
                    </Link>
                </Card>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <Card as="section" density="compact">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <h2 className="text-base font-semibold text-ink">Applications</h2>
                        {dashboard.applications.count > 0 ? (
                            <p className="text-sm text-muted">{dashboard.applications.count} total</p>
                        ) : null}
                    </div>
                    {dashboard.applications.count > 0 ? (
                        <>
                            <dl className="mt-3 grid grid-cols-2 gap-3 text-sm">
                                {dashboard.applications.status_summary.map((row) => (
                                    <div key={row.label}>
                                        <dt className="text-muted">{row.label}</dt>
                                        <dd className="text-lg font-semibold text-ink">{row.count}</dd>
                                    </div>
                                ))}
                            </dl>
                            <Link
                                href="/account/applications"
                                className="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                            >
                                View applications
                            </Link>
                        </>
                    ) : (
                        <>
                            <p className="mt-3 text-sm text-muted">No applications yet.</p>
                            <Link
                                href="/jobs"
                                className="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                            >
                                Browse jobs
                            </Link>
                        </>
                    )}
                </Card>

                <Card as="section" density="compact">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <h2 className="text-base font-semibold text-ink">Saved jobs</h2>
                        {dashboard.saved_jobs.count > 0 ? (
                            <p className="text-sm text-muted">
                                {dashboard.saved_jobs.count} saved job{dashboard.saved_jobs.count === 1 ? '' : 's'}
                            </p>
                        ) : null}
                    </div>
                    {dashboard.saved_jobs.count > 0 ? (
                        <>
                            <ul className="mt-3 space-y-3">
                                {dashboard.saved_jobs.recent.slice(0, 3).map((saved) => (
                                    <li key={`${saved.title}-${saved.saved_at ?? saved.slug}`} className="text-sm">
                                        {saved.can_view && saved.slug ? (
                                            <Link href={`/jobs/${saved.slug}`} className="font-medium text-ink hover:text-brand">
                                                {saved.title}
                                            </Link>
                                        ) : (
                                            <p className="font-medium text-ink">{saved.title}</p>
                                        )}
                                        {saved.project ? <p className="text-muted">{saved.project}</p> : null}
                                    </li>
                                ))}
                            </ul>
                            <Link
                                href="/account/saved-jobs"
                                className="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                            >
                                View saved jobs
                            </Link>
                        </>
                    ) : (
                        <>
                            <p className="mt-3 text-sm text-muted">No saved jobs yet.</p>
                            <Link
                                href="/jobs"
                                className="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                            >
                                Browse jobs
                            </Link>
                        </>
                    )}
                </Card>
            </div>

            <Card as="section" density="compact">
                <h2 className="text-base font-semibold text-ink">Recent applications</h2>
                {dashboard.applications.recent.length > 0 ? (
                    <ul className="mt-3 divide-y divide-line">
                        {dashboard.applications.recent.map((application) => (
                            <li
                                key={`${application.href ?? application.slug ?? application.title}-${application.applied_at}`}
                                className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="min-w-0 text-sm">
                                    <p className="font-medium text-ink">{application.title}</p>
                                    {application.company ? <p className="text-muted">{application.company}</p> : null}
                                    <p className="text-muted">
                                        {application.status_detail ?? application.status}
                                        {' · '}
                                        {formatAppliedAt(application.applied_at)}
                                    </p>
                                </div>
                                {application.href ? (
                                    <Link
                                        href={application.href}
                                        className="inline-flex min-h-11 shrink-0 items-center text-sm font-semibold text-brand hover:underline"
                                    >
                                        View
                                    </Link>
                                ) : null}
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="mt-3 text-sm text-muted">Apply to a role to see updates here.</p>
                )}
            </Card>

            <div className="grid gap-4 sm:grid-cols-2">
                <Card as="section" density="compact">
                    <h2 className="text-base font-semibold text-ink">Notifications</h2>
                    {unreadNotifications > 0 ? (
                        <p className="mt-2 text-sm text-ink">
                            <span className="font-semibold">{unreadNotifications}</span> unread
                        </p>
                    ) : (
                        <p className="mt-2 text-sm text-muted">You are caught up.</p>
                    )}
                    <Link
                        href="/account/notifications"
                        className="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                    >
                        View notifications
                    </Link>
                </Card>

                <Card as="section" density="compact">
                    <h2 className="text-base font-semibold text-ink">Account</h2>
                    <ul className="mt-2 space-y-1 text-sm text-ink">
                        <li>{header.email_verified ? 'Email verified' : 'Email not verified'}</li>
                    </ul>
                    {!header.email_verified ? (
                        <Link
                            href="/account/settings/security"
                            className="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                        >
                            Verify your email
                        </Link>
                    ) : (
                        <Link
                            href="/account/settings"
                            className="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                        >
                            Account settings
                        </Link>
                    )}
                </Card>
            </div>
        </div>
    );
}

function verificationLabel(ok: boolean, yes: string, no: string): string {
    return ok ? yes : no;
}

function EmployerDashboard({ dashboard }: { dashboard: AccountEmployerDashboard }) {
    return (
        <>
            <ProfileShell
                name={dashboard.company_name}
                photoUrl={dashboard.photo_url}
                businessId={dashboard.ournear_id || dashboard.user_business_id}
                secondaryId={dashboard.business_id}
                secondaryIdLabel="Company ID"
                subtitle={dashboard.industry || 'Complete your company profile'}
                shape="rounded"
                headerVariant="compact"
                heading="h2"
                badge={dashboard.ournear_verified ? 'OurNear verified' : null}
                actions={
                    dashboard.eligibility.eligible ? (
                        <Link href="/account/jobs/create" className="rounded-md bg-accent px-4 py-2 text-center text-sm font-semibold text-white">
                            Post Job
                        </Link>
                    ) : (
                        <Link href="/account/profile" className="rounded-md border border-line px-4 py-2 text-sm font-semibold text-ink">
                            Complete company profile
                        </Link>
                    )
                }
                tabs={[
                    { href: '/account', label: 'Dashboard', active: true },
                    { href: '/account/profile', label: 'Company' },
                    { href: '/account/jobs', label: 'Jobs' },
                    { href: '/account/submissions', label: 'Submitted candidates' },
                    { href: '/account/enquiries', label: 'Enquiries' },
                ]}
            >
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <Link href="/account/jobs" className={cardClass}>
                        <p className="text-sm text-muted">Own jobs</p>
                        <p className="mt-2 text-3xl font-semibold text-ink">{dashboard.jobs_count}</p>
                        <p className="mt-1 text-sm text-muted">{dashboard.active_jobs_count} active</p>
                    </Link>
                    <Link href="/account/jobs" className={cardClass}>
                        <p className="text-sm text-muted">Applicants on your jobs</p>
                        <p className="mt-2 text-3xl font-semibold text-ink">{dashboard.applicants_count}</p>
                        <p className="mt-1 text-sm text-muted">Ownership-scoped only</p>
                    </Link>
                    <Link href="/account/submissions" className={cardClass}>
                        <p className="text-sm text-muted">Submitted candidates</p>
                        <p className="mt-2 text-3xl font-semibold text-ink">{dashboard.submissions_count}</p>
                        <p className="mt-1 text-sm text-muted">View-only</p>
                    </Link>
                    <Link href="/account/enquiries" className={cardClass}>
                        <p className="text-sm text-muted">Own enquiries</p>
                        <p className="mt-2 text-3xl font-semibold text-ink">{dashboard.enquiries_count}</p>
                        <p className="mt-1 text-sm text-muted">Workforce requests you submitted</p>
                    </Link>
                    <Link href="/account/profile" className={cardClass}>
                        <p className="text-sm text-muted">Company profile</p>
                        <p className="mt-2 font-semibold text-ink">Logo, company information, and website</p>
                    </Link>
                </div>
            </ProfileShell>
            <section className={`${cardClass} mb-6`}>
                <h2 className="text-lg font-semibold text-ink">Verification and posting</h2>
                <ul className="mt-4 flex flex-wrap gap-2 text-xs">
                    <li className="rounded-full bg-surface px-3 py-1 text-ink">
                        {verificationLabel(dashboard.email_verified, 'Email verified', 'Email not verified')}
                    </li>
                    <li className="rounded-full bg-surface px-3 py-1 text-ink">
                        {verificationLabel(dashboard.mobile_verified, 'Mobile verified', 'Mobile not verified')}
                    </li>
                    <li className="rounded-full bg-surface px-3 py-1 text-ink">
                        {verificationLabel(dashboard.ournear_verified, 'OurNear verified', 'OurNear not verified')}
                    </li>
                    {dashboard.posting_restricted ? (
                        <li className="rounded-full bg-danger/10 px-3 py-1 text-danger">Posting restricted</li>
                    ) : null}
                </ul>
                {!dashboard.eligibility.eligible ? (
                    <div className="mt-4 rounded-md bg-accent/10 px-3 py-3 text-sm text-ink">
                        <p className="font-semibold">
                            {dashboard.posting_restricted ? 'Job posting restricted' : 'Posting eligibility incomplete'}
                        </p>
                        <p className="mt-1">{dashboard.eligibility.reason}</p>
                        <p className="mt-1 text-muted">{dashboard.eligibility.next_action}</p>
                    </div>
                ) : null}
            </section>
            <section className={`${cardClass} mb-6`}>
                <h2 className="text-lg font-semibold text-ink">Pending and next actions</h2>
                {dashboard.next_actions.length === 0 ? (
                    <p className="mt-3 text-sm text-muted">You are up to date on your recruitment workspace.</p>
                ) : (
                    <ul className="mt-4 space-y-3">
                        {dashboard.next_actions.map((action) => (
                            <li key={`${action.href}-${action.label}`}>
                                <Link
                                    href={action.href}
                                    className={`block rounded-md px-3 py-3 text-sm ${
                                        action.kind === 'pending' ? 'bg-accent/10 text-ink' : 'bg-surface text-ink'
                                    } hover:border-brand`}
                                >
                                    <span className="font-medium">{action.label}</span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
            <div className="mb-6 grid gap-4 lg:grid-cols-2">
                <section className={cardClass}>
                    <h2 className="text-lg font-semibold text-ink">Recent applicants</h2>
                    {dashboard.recent_applicants.length === 0 ? (
                        <p className="mt-3 text-sm text-muted">No applicants on your jobs yet.</p>
                    ) : (
                        <ul className="mt-4 space-y-3">
                            {dashboard.recent_applicants.map((applicant) => (
                                <li key={`applicant-${applicant.id}`}>
                                    {applicant.job?.slug ? (
                                        <Link href={`/account/jobs/${applicant.job.slug}/applicants`} className="block text-sm hover:text-brand">
                                            <p className="font-medium text-ink">{applicant.name}</p>
                                            <p className="text-muted">{applicant.job.title}</p>
                                        </Link>
                                    ) : (
                                        <div className="text-sm">
                                            <p className="font-medium text-ink">{applicant.name}</p>
                                            <p className="text-muted">{applicant.job?.title ?? 'Job'}</p>
                                        </div>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                    <Link href="/account/jobs" className="mt-4 inline-block text-sm font-medium text-brand">
                        Open jobs and applicants
                    </Link>
                </section>
                <section className={cardClass}>
                    <h2 className="text-lg font-semibold text-ink">Recent submissions</h2>
                    {dashboard.recent_submissions.length === 0 ? (
                        <p className="mt-3 text-sm text-muted">No candidates have been submitted to your company yet.</p>
                    ) : (
                        <ul className="mt-4 space-y-3">
                            {dashboard.recent_submissions.map((submission) => (
                                <li key={`submission-${submission.id}`} className="text-sm">
                                    <p className="font-medium text-ink">{submission.name}</p>
                                    <p className="text-muted">
                                        {submission.job?.title ?? 'Job'}
                                        {submission.business_id ? ` · ${submission.business_id}` : ''}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                    <Link href="/account/submissions" className="mt-4 inline-block text-sm font-medium text-brand">
                        View submitted candidates
                    </Link>
                </section>
            </div>
        </>
    );
}

export default function AccountHome({ role, profile, dashboard }: AccountHomeProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const candidateDashboard = role === 'candidate' ? dashboard as AccountCandidateDashboard | null : null;
    const employerDashboard = role === 'employer' ? dashboard as AccountEmployerDashboard | null : null;

    return (
        <AccountLayout
            title={candidateDashboard || employerDashboard ? 'Dashboard' : 'Account'}
            description={candidateDashboard
                ? 'Your profile, applications, and saved jobs.'
                : employerDashboard
                    ? 'Manage your company, jobs, applicants, and enquiries.'
                    : 'Manage your OurNear account.'}
            breadcrumbs={[{ label: 'Dashboard' }]}
        >
            {flash.registered_email ? (
                <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                    Verification email sent to: {flash.registered_email}
                </p>
            ) : flash.status ? (
                <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                    {flash.status}
                </p>
            ) : null}
            {candidateDashboard ? (
                <CandidateDashboard dashboard={candidateDashboard} profile={profile} />
            ) : employerDashboard ? (
                <EmployerDashboard dashboard={employerDashboard} />
            ) : (
                <>
                    <div className="mb-6 rounded-xl border border-line bg-white p-6">
                        <p className="text-sm text-muted">Profile completion</p>
                        <p className="mt-1 text-2xl font-semibold text-ink">{profile.profile_completion}%</p>
                        <p className="mt-1 text-sm text-muted">
                            {profile.email_verified ? 'Email verified' : 'Email not verified'}
                        </p>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Link href="/account/profile" className="rounded-xl border border-line bg-white p-6 hover:border-brand">
                            <h2 className="text-lg font-semibold text-ink">Profile</h2>
                            <p className="mt-2 text-sm text-muted">Photo, name, bio, and personal details.</p>
                        </Link>
                        {role === 'employer' ? (
                            <Link href="/account/enquiries" className="rounded-xl border border-line bg-white p-6 hover:border-brand">
                                <h2 className="text-lg font-semibold text-ink">Enquiries</h2>
                                <p className="mt-2 text-sm text-muted">View employer enquiries linked to this account.</p>
                            </Link>
                        ) : null}
                        <Link href="/account/settings" className="rounded-xl border border-line bg-white p-6 hover:border-brand">
                            <h2 className="text-lg font-semibold text-ink">Settings</h2>
                            <p className="mt-2 text-sm text-muted">Account, profile, and security.</p>
                        </Link>
                    </div>
                </>
            )}
        </AccountLayout>
    );
}
