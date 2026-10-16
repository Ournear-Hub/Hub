import { Link } from '@inertiajs/react';
import ProfileShell from '@/Components/Profile/ProfileShell';
import type { AccountEmployerDashboard } from '@/types/account';

const cardClass = 'rounded-xl border border-line bg-white p-6';

type EmployerAccountDashboardProps = {
    dashboard: AccountEmployerDashboard;
    /** ProfileShell “Dashboard” tab target (employer home vs hiring overview for dual-role). */
    overviewHref?: string;
};

export default function EmployerAccountDashboard({
    dashboard,
    overviewHref = '/account',
}: EmployerAccountDashboardProps) {
    return (
        <>
            <ProfileShell
                name={dashboard.company_name}
                photoUrl={dashboard.logo_url ?? null}
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
                        <Link href="/account/company" className="rounded-md border border-line px-4 py-2 text-sm font-semibold text-ink">
                            Complete company profile
                        </Link>
                    )
                }
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
                    <Link href="/account/enquiries" className={cardClass}>
                        <p className="text-sm text-muted">Own enquiries</p>
                        <p className="mt-2 text-3xl font-semibold text-ink">{dashboard.enquiries_count}</p>
                        <p className="mt-1 text-sm text-muted">Workforce requests you submitted</p>
                    </Link>
                    <Link href="/account/company" className={cardClass}>
                        <p className="text-sm text-muted">Company profile</p>
                        <p className="mt-2 font-semibold text-ink">Logo, company information, and website</p>
                    </Link>
                </div>
            </ProfileShell>
            {!dashboard.eligibility.eligible ? (
                <section className={`${cardClass} mb-6`}>
                    <div className="rounded-md bg-accent/10 px-3 py-3 text-sm text-ink">
                        <p className="font-semibold">
                            {dashboard.posting_restricted ? 'Job posting restricted' : 'Posting eligibility incomplete'}
                        </p>
                        <p className="mt-1">{dashboard.eligibility.reason}</p>
                        <p className="mt-1 text-muted">{dashboard.eligibility.next_action}</p>
                    </div>
                </section>
            ) : null}
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
            <section className={`${cardClass} mb-6`}>
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
        </>
    );
}
