import { Link } from '@inertiajs/react';
import SettingsPageShell from '@/Components/Account/SettingsPageShell';
import StatusBadge from '@/Components/Account/StatusBadge';
import type { AccountProfile } from '@/types/account';

type SecurityProps = {
    role: 'candidate' | 'employer';
    profile: AccountProfile;
};

export default function SecuritySettings({ profile, role }: SecurityProps) {
    const needsVerification = !profile.email_verified || (role === 'employer' && !profile.mobile_verified);

    return (
        <SettingsPageShell>
            <header className="mb-6">
                <h1 className="text-xl font-semibold text-ink">Security</h1>
                <p className="mt-1 text-sm text-muted">
                    Account security overview. Verify contacts and change your password using the dedicated tabs.
                </p>
            </header>

            <div className="space-y-4">
                <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <div className="flex flex-wrap items-start justify-between gap-2">
                        <h2 className="text-base font-semibold text-ink">Sign-in and recovery</h2>
                        {needsVerification ? <StatusBadge status="Action required" /> : <StatusBadge status="Verified" />}
                    </div>
                    <dl className="mt-4 space-y-3 text-sm">
                        <div>
                            <dt className="text-muted">Email</dt>
                            <dd className="font-medium text-ink">{profile.email}</dd>
                            <dd className="text-muted">{profile.email_verified ? 'Verified' : 'Not verified'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Mobile</dt>
                            <dd className="font-medium text-ink">{profile.mobile || 'Not saved'}</dd>
                            <dd className="text-muted">{profile.mobile_verified ? 'Verified' : 'Not verified'}</dd>
                        </div>
                        {profile.whatsapp_number ? (
                            <div>
                                <dt className="text-muted">WhatsApp</dt>
                                <dd className="font-medium text-ink">{profile.whatsapp_number}</dd>
                                <dd className="text-muted">{profile.whatsapp_verified ? 'Verified' : 'Not verified'}</dd>
                            </div>
                        ) : null}
                    </dl>
                    <Link
                        href="/account/settings/verification"
                        className="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                    >
                        Open Verification
                    </Link>
                </section>

                <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <h2 className="text-base font-semibold text-ink">Password</h2>
                    <p className="mt-2 text-sm text-muted">
                        Change your password with your current password. We never display existing passwords.
                    </p>
                    <Link
                        href="/account/settings/password"
                        className="mt-4 inline-flex min-h-11 items-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-95"
                    >
                        Change password
                    </Link>
                </section>

                <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <h2 className="text-base font-semibold text-ink">Account status</h2>
                    <p className="mt-2 text-sm text-ink">{profile.account_status_label}</p>
                    <p className="mt-1 text-xs text-muted">Signed in as {role}.</p>
                </section>
            </div>
        </SettingsPageShell>
    );
}
