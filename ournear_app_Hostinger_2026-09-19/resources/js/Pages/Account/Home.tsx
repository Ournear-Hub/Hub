import { Link, usePage } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountCandidateDashboard, AccountEmployerDashboard, AccountProfile } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type AccountHomeProps = {
    role: 'candidate' | 'employer';
    profile: AccountProfile;
    dashboard?: AccountCandidateDashboard | AccountEmployerDashboard | null;
};

const cardClass = 'rounded-xl border border-line bg-white p-6';

function CandidateDashboard({ dashboard }: { dashboard: AccountCandidateDashboard }) {
    const header = dashboard.header;
    const initials = header.name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');

    return (
        <>
            <section className={`${cardClass} mb-6`}>
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                    {header.photo_url ? (
                        <img
                            src={header.photo_url}
                            alt={`${header.name} profile`}
                            className="h-24 w-24 shrink-0 rounded-full object-cover"
                        />
                    ) : (
                        <div
                            className="flex h-24 w-24 shrink-0 items-center justify-center rounded-full bg-surface text-xl font-semibold text-brand"
                            aria-label="No profile photo"
                        >
                            {initials || '—'}
                        </div>
                    )}
                    <div className="min-w-0">
                        <h2 className="break-words text-2xl font-semibold text-ink">{header.name}</h2>
                        {header.username ? <p className="mt-1 break-all text-sm text-muted">@{header.username}</p> : null}
                        <p className={`mt-2 text-sm ${header.designation ? 'font-medium text-ink' : 'text-muted'}`}>
                            {header.designation || 'Designation not added'}
                        </p>
                    </div>
                </div>
            </section>
            <div className={`${cardClass} mb-6`}>
                <div className="flex items-center justify-between gap-3 text-sm">
                    <p className="font-medium text-ink">Profile completion</p>
                    <p className="font-semibold text-brand">{header.profile_completion}%</p>
                </div>
                <div className="mt-2 h-2 overflow-hidden rounded-full bg-surface" aria-hidden="true">
                    <div
                        className="h-full rounded-full bg-brand"
                        style={{ width: `${Math.max(0, Math.min(100, header.profile_completion))}%` }}
                    />
                </div>
                <p className="mt-2 text-sm text-muted">
                    {header.email_verified ? 'Email verified' : 'Email not verified'}
                </p>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <section className={cardClass}>
                    <h2 className="text-lg font-semibold text-ink">Resume</h2>
                    {dashboard.resume.primary ? (
                        <>
                            <p className="mt-3 text-sm font-medium text-ink">{dashboard.resume.primary.title}</p>
                            <p className="mt-1 text-sm text-muted">
                                {dashboard.resume.primary.status_label ?? 'Resume'} · Primary
                            </p>
                            <Link href="/account/resumes" className="mt-4 inline-block text-sm font-medium text-brand">
                                Manage resumes
                            </Link>
                        </>
                    ) : (
                        <>
                            <p className="mt-3 text-sm text-muted">No primary resume</p>
                            <Link href="/account/resumes" className="mt-4 inline-block text-sm font-medium text-brand">
                                Upload Resume
                            </Link>
                        </>
                    )}
                </section>
                <section className={cardClass}>
                    <h2 className="text-lg font-semibold text-ink">Documents</h2>
                    {dashboard.documents.count > 0 ? (
                        <>
                            <p className="mt-3 text-2xl font-semibold text-ink">{dashboard.documents.count}</p>
                            <Link href="/account/documents" className="mt-4 inline-block text-sm font-medium text-brand">
                                Manage documents
                            </Link>
                        </>
                    ) : (
                        <>
                            <p className="mt-3 text-sm text-muted">No documents uploaded.</p>
                            <Link href="/account/documents" className="mt-4 inline-block text-sm font-medium text-brand">
                                Add Documents
                            </Link>
                        </>
                    )}
                </section>
                <section className={cardClass}>
                    <h2 className="text-lg font-semibold text-ink">Saved Jobs</h2>
                    {dashboard.saved_jobs.count > 0 ? (
                        <>
                            <p className="mt-3 text-2xl font-semibold text-ink">{dashboard.saved_jobs.count}</p>
                            <Link href="/account/saved-jobs" className="mt-4 inline-block text-sm font-medium text-brand">
                                View Saved Jobs
                            </Link>
                        </>
                    ) : (
                        <>
                            <p className="mt-3 text-sm text-muted">No saved jobs yet.</p>
                            <Link href="/jobs" className="mt-4 inline-block text-sm font-medium text-brand">
                                Explore Jobs
                            </Link>
                        </>
                    )}
                </section>
                <section className={cardClass}>
                    <h2 className="text-lg font-semibold text-ink">Applications</h2>
                    {dashboard.applications.count > 0 ? (
                        <>
                            <p className="mt-3 text-2xl font-semibold text-ink">{dashboard.applications.count}</p>
                            <ul className="mt-3 space-y-2">
                                {dashboard.applications.recent.map((application) => (
                                    <li key={`${application.href ?? application.slug ?? application.title}-${application.applied_at}`} className="text-sm">
                                        {application.href ? (
                                            <Link href={application.href} className="font-medium text-ink hover:text-brand">
                                                {application.title}
                                            </Link>
                                        ) : (
                                            <p className="font-medium text-ink">{application.title}</p>
                                        )}
                                        <p className="text-muted">{application.status}</p>
                                    </li>
                                ))}
                            </ul>
                            <Link href="/account/applications" className="mt-4 inline-block text-sm font-medium text-brand">
                                View Applications
                            </Link>
                        </>
                    ) : (
                        <>
                            <p className="mt-3 text-sm text-muted">No applications yet.</p>
                            <Link href="/jobs" className="mt-4 inline-block text-sm font-medium text-brand">
                                Browse Jobs
                            </Link>
                        </>
                    )}
                </section>
            </div>
        </>
    );
}

function EmployerDashboard({ dashboard }: { dashboard: AccountEmployerDashboard }) {
    return (
        <>
            <section className={`${cardClass} mb-6`}>
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold text-ink">{dashboard.company_name}</h2>
                        <p className="mt-1 text-sm text-muted">
                            {[dashboard.business_id, dashboard.industry].filter(Boolean).join(' · ') || 'Complete your company profile'}
                        </p>
                    </div>
                    {dashboard.eligibility.eligible ? (
                        <Link href="/account/jobs/create" className="rounded-md bg-accent px-4 py-2 text-center text-sm font-semibold text-white">
                            Post Job
                        </Link>
                    ) : null}
                </div>
                {!dashboard.eligibility.eligible ? (
                    <div className="mt-4 rounded-md bg-accent/10 px-3 py-3 text-sm text-ink">
                        <p className="font-semibold">Posting eligibility incomplete</p>
                        <p className="mt-1">{dashboard.eligibility.reason}</p>
                        <p className="mt-1 text-muted">{dashboard.eligibility.next_action}</p>
                    </div>
                ) : null}
            </section>
            <div className="grid gap-4 sm:grid-cols-3">
                <Link href="/account/jobs" className={cardClass}>
                    <p className="text-sm text-muted">Own jobs</p>
                    <p className="mt-2 text-3xl font-semibold text-ink">{dashboard.jobs_count}</p>
                    <p className="mt-1 text-sm text-muted">{dashboard.active_jobs_count} active</p>
                </Link>
                <Link href="/account/jobs" className={cardClass}>
                    <p className="text-sm text-muted">Applicants</p>
                    <p className="mt-2 text-3xl font-semibold text-ink">{dashboard.applicants_count}</p>
                    <p className="mt-1 text-sm text-brand">Open job management</p>
                </Link>
                <Link href="/account/profile" className={cardClass}>
                    <p className="text-sm text-muted">Company profile</p>
                    <p className="mt-2 font-semibold text-ink">Logo, company information, and website</p>
                </Link>
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
                    ? 'Manage your company, jobs, and applicants.'
                    : 'Manage your OurNear account.'}
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
                <CandidateDashboard dashboard={candidateDashboard} />
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
