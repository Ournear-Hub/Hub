import { Link, usePage } from '@inertiajs/react';
import SettingsNav from '@/Components/Account/SettingsNav';
import StatusBadge from '@/Components/Account/StatusBadge';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountProfile } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type SettingsIndexProps = {
    role: 'candidate' | 'employer';
    profile: AccountProfile;
};

export default function SettingsIndex({ role, profile }: SettingsIndexProps) {
    const flash = usePage<SharedPageProps>().props.flash;

    const verificationSummary = [
        profile.email_verified ? 'Email verified' : 'Email action required',
        profile.mobile_verified ? 'Mobile verified' : 'Mobile not verified',
    ].join(' · ');

    return (
        <AccountLayout
            title="Settings"
            breadcrumbs={[
                { label: 'Account', href: '/account' },
                { label: 'Settings' },
            ]}
        >
            <SettingsNav />
            {flash.success ? (
                <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success" role="status">
                    {flash.success}
                </p>
            ) : null}

            <div className="mx-auto max-w-3xl space-y-4 lg:max-w-none">
                <header className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <h1 className="text-xl font-semibold text-ink">Account settings</h1>
                    <p className="mt-2 text-sm text-muted">Identity, verification, security, and communication preferences.</p>
                    <dl className="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                        {profile.business_id ? (
                            <div>
                                <dt className="text-muted">OurNear ID</dt>
                                <dd className="font-medium text-ink">{profile.business_id}</dd>
                            </div>
                        ) : null}
                        <div>
                            <dt className="text-muted">Username</dt>
                            <dd className="font-medium text-ink">{profile.username}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Email</dt>
                            <dd className="font-medium text-ink">{profile.email}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Mobile</dt>
                            <dd className="font-medium text-ink">{profile.mobile || 'Not saved'}</dd>
                        </div>
                    </dl>
                </header>

                <div className="grid gap-4">
                    <Link href="/account/settings/account" className="rounded-xl border border-line bg-white p-5 hover:border-brand">
                        <h2 className="text-lg font-semibold text-ink">Account identity</h2>
                        <p className="mt-2 text-sm text-muted">Username, mobile, WhatsApp number, and language.</p>
                        <p className="mt-2 text-xs text-muted">{profile.account_status_label}</p>
                    </Link>

                    <Link href="/account/settings/security" className="rounded-xl border border-line bg-white p-5 hover:border-brand">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <h2 className="text-lg font-semibold text-ink">Verification &amp; security</h2>
                            {!profile.email_verified || !profile.mobile_verified ? (
                                <StatusBadge status="Action required" />
                            ) : (
                                <StatusBadge status="Verified" />
                            )}
                        </div>
                        <p className="mt-2 text-sm text-muted">Email and mobile verification, password, and Hiring requirements.</p>
                        <p className="mt-2 text-xs text-muted">{verificationSummary}</p>
                    </Link>

                    <Link href="/account/profile" className="rounded-xl border border-line bg-white p-5 hover:border-brand">
                        <h2 className="text-lg font-semibold text-ink">Professional profile</h2>
                        <p className="mt-2 text-sm text-muted">Photo, name, experience, and profile completion — not managed here.</p>
                        <p className="mt-2 text-xs text-muted">{profile.profile_completion}% complete</p>
                    </Link>

                    {role === 'candidate' ? (
                        <Link href="/account/settings/communication" className="rounded-xl border border-line bg-white p-5 hover:border-brand">
                            <h2 className="text-lg font-semibold text-ink">Communication preferences</h2>
                            <p className="mt-2 text-sm text-muted">Marketing and product notification channels backed by your stored preferences.</p>
                        </Link>
                    ) : null}

                    <Link href="/account/notifications" className="rounded-xl border border-line bg-white p-5 hover:border-brand">
                        <h2 className="text-lg font-semibold text-ink">Notifications</h2>
                        <p className="mt-2 text-sm text-muted">Application updates and account activity.</p>
                    </Link>
                </div>

                <p className="text-xs text-muted">Signed in as {role}.</p>
            </div>
        </AccountLayout>
    );
}
