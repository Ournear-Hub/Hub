import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import VerificationCenter, { type HiringActivationGate } from '@/Components/Account/Verification/VerificationCenter';
import SettingsNav from '@/Components/Account/SettingsNav';
import PasswordStrength from '@/Components/Account/PasswordStrength';
import FormField from '@/Components/Public/FormField';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountProfile } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type SecurityProps = {
    role: 'candidate' | 'employer';
    profile: AccountProfile;
    mobile_otp_retry_after?: number;
    hiring_gate?: HiringActivationGate | null;
};

export default function SecuritySettings({
    profile,
    role,
    mobile_otp_retry_after = 0,
    hiring_gate = null,
}: SecurityProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submitPassword = (event: FormEvent) => {
        event.preventDefault();
        passwordForm.put('/account/settings/password', {
            onSuccess: () => passwordForm.reset(),
        });
    };

    return (
        <AccountLayout
            title="Verification & security"
            description="Email and mobile verification, password, and Hiring requirements."
            breadcrumbs={[
                { label: 'Account', href: '/account' },
                { label: 'Settings', href: '/account/settings' },
                { label: 'Verification & security' },
            ]}
        >
            <SettingsNav />
            {(flash.success || flash.status) && (
                <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success" role="status">
                    {flash.success ?? flash.status}
                </p>
            )}

            <div className="mx-auto max-w-3xl space-y-6 lg:max-w-none">
                <header>
                    <h1 className="text-xl font-semibold text-ink">Verification center</h1>
                    <p className="mt-2 text-sm text-muted">
                        Manage verification for your account. Hiring employers must verify email and mobile before activating
                        the Hiring workspace.
                    </p>
                </header>

                <VerificationCenter
                    profile={profile}
                    role={role}
                    mobileOtpRetryAfter={mobile_otp_retry_after}
                    hiringGate={hiring_gate}
                />

                <form onSubmit={submitPassword} className="rounded-xl border border-line bg-white p-4 sm:p-6">
                    <h2 className="text-lg font-semibold text-ink">Change password</h2>
                    <div className="mt-4 space-y-4">
                        <FormField
                            id="current_password"
                            label="Current password"
                            type="password"
                            autoComplete="current-password"
                            value={passwordForm.data.current_password}
                            onChange={(event) => passwordForm.setData('current_password', event.target.value)}
                        />
                        {passwordForm.errors.current_password ? (
                            <p className="-mt-2 text-sm text-danger">{passwordForm.errors.current_password}</p>
                        ) : null}
                        <FormField
                            id="password"
                            label="New password"
                            type="password"
                            autoComplete="new-password"
                            value={passwordForm.data.password}
                            onChange={(event) => passwordForm.setData('password', event.target.value)}
                        />
                        <PasswordStrength password={passwordForm.data.password} />
                        {passwordForm.errors.password ? (
                            <p className="-mt-2 text-sm text-danger">{passwordForm.errors.password}</p>
                        ) : null}
                        <FormField
                            id="password_confirmation"
                            label="Confirm new password"
                            type="password"
                            autoComplete="new-password"
                            value={passwordForm.data.password_confirmation}
                            onChange={(event) => passwordForm.setData('password_confirmation', event.target.value)}
                        />
                        <button
                            type="submit"
                            disabled={passwordForm.processing}
                            className="inline-flex min-h-11 items-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                        >
                            Update password
                        </button>
                    </div>
                </form>
            </div>
        </AccountLayout>
    );
}
