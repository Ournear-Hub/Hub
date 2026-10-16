import { Link, usePage } from '@inertiajs/react';
import {
    CandidateMetricCards,
    CandidateProfileSummary,
    CandidateRecentApplications,
    CandidateRecommendedJobs,
    CandidateSavedJobsSummary,
} from '@/Components/Account/CandidateHome/CandidateDashboardSections';
import CandidateProfileCompletionPrompt from '@/Components/Account/CandidateProfileCompletionPrompt';
import AccountPageStack from '@/Components/Account/AccountPageStack';
import AccountLayout from '@/Layouts/AccountLayout';
import { accountLayout } from '@/design-system/classNames';
import type { AccountCandidateDashboard, AccountProfile } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type AccountHomeProps = {
    role: 'candidate' | 'employer';
    profile: AccountProfile;
    dashboard?: AccountCandidateDashboard | null;
};

function CandidateDashboard({
    dashboard,
    profile,
}: {
    dashboard: AccountCandidateDashboard;
    profile: AccountProfile;
}) {
    return (
        <AccountPageStack>
            <CandidateProfileCompletionPrompt action={dashboard.next_action} />
            <CandidateProfileSummary dashboard={dashboard} profile={profile} />
            <CandidateMetricCards dashboard={dashboard} />
            <div className={`grid min-w-0 lg:grid-cols-2 ${accountLayout.cardGrid}`}>
                <CandidateRecentApplications dashboard={dashboard} />
                <CandidateSavedJobsSummary dashboard={dashboard} />
            </div>
            <CandidateRecommendedJobs jobs={dashboard.recommended_jobs ?? []} />
        </AccountPageStack>
    );
}

export default function AccountHome({ role, profile, dashboard }: AccountHomeProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const candidateDashboard = role === 'candidate' ? (dashboard as AccountCandidateDashboard | null) : null;

    return (
        <AccountLayout
            hideSidebarOurnearId={Boolean(candidateDashboard)}
            compactHeader={Boolean(candidateDashboard)}
            title={candidateDashboard ? 'Personal Dashboard' : 'Account'}
            description={candidateDashboard ? undefined : 'Manage your OurNear account.'}
            breadcrumbs={candidateDashboard ? undefined : [{ label: 'Account' }]}
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
                            <Link href="/account/hiring" className="rounded-xl border border-line bg-white p-6 hover:border-brand">
                                <h2 className="text-lg font-semibold text-ink">Company workspace</h2>
                                <p className="mt-2 text-sm text-muted">Jobs, applicants, staffing, and hiring pipeline.</p>
                            </Link>
                        ) : null}
                        <Link href="/account/settings/account" className="rounded-xl border border-line bg-white p-6 hover:border-brand">
                            <h2 className="text-lg font-semibold text-ink">Settings</h2>
                            <p className="mt-2 text-sm text-muted">Account, profile, and security.</p>
                        </Link>
                    </div>
                </>
            )}
        </AccountLayout>
    );
}
