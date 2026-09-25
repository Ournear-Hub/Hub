import { Head, Link, usePage } from '@inertiajs/react';
import SettingsNav from '@/Components/Account/SettingsNav';
import PageContainer from '@/Components/Public/PageContainer';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountProfile } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type SettingsIndexProps = {
    role: 'candidate' | 'employer';
    profile: AccountProfile;
};

export default function SettingsIndex({ role, profile }: SettingsIndexProps) {
    const flash = usePage<SharedPageProps>().props.flash;

    return (
        <AccountLayout title="Settings">
            
                    <SettingsNav />
                    {flash.success && (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.success}
                        </p>
                    )}
                    <div className="grid gap-4">
                        <Link href="/account/settings/account" className="rounded-xl border border-line bg-white p-6 hover:border-brand">
                            <h2 className="text-lg font-semibold text-ink">Account</h2>
                            <p className="mt-2 text-sm text-muted">
                                Username, email, mobile, language, and account status.
                            </p>
                            <p className="mt-2 text-xs text-muted">@{profile.username} · {profile.account_status_label}</p>
                        </Link>
                        <Link href="/account/profile" className="rounded-xl border border-line bg-white p-6 hover:border-brand">
                            <h2 className="text-lg font-semibold text-ink">Profile</h2>
                            <p className="mt-2 text-sm text-muted">Photo, name, bio, location, and language.</p>
                            <p className="mt-2 text-xs text-muted">{profile.profile_completion}% complete</p>
                        </Link>
                        {role === 'candidate' ? (
                            <Link href="/account/settings/communication" className="rounded-xl border border-line bg-white p-6 hover:border-brand">
                                <h2 className="text-lg font-semibold text-ink">Communication</h2>
                                <p className="mt-2 text-sm text-muted">Email, SMS, and platform notification preferences.</p>
                            </Link>
                        ) : null}
                        <Link href="/account/settings/security" className="rounded-xl border border-line bg-white p-6 hover:border-brand">
                            <h2 className="text-lg font-semibold text-ink">Security</h2>
                            <p className="mt-2 text-sm text-muted">Email verification and password.</p>
                            <p className="mt-2 text-xs text-muted">
                                {profile.email_verified ? 'Email verified' : 'Email not verified'}
                            </p>
                        </Link>
                    </div>
                    <p className="mt-2 text-xs text-muted">Signed in as {role}.</p>
                </AccountLayout>
    );
}
