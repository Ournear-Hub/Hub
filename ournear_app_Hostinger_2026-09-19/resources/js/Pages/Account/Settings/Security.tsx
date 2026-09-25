import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import SettingsNav from '@/Components/Account/SettingsNav';
import PasswordStrength from '@/Components/Account/PasswordStrength';
import FormField from '@/Components/Public/FormField';
import PageContainer from '@/Components/Public/PageContainer';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountProfile } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type SecurityProps = {
    role: 'candidate' | 'employer';
    profile: AccountProfile;
};

export default function SecuritySettings({ profile }: SecurityProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const verificationForm = useForm({});

    const submitPassword = (event: FormEvent) => {
        event.preventDefault();
        passwordForm.put('/account/settings/password', {
            onSuccess: () => passwordForm.reset(),
        });
    };

    const resendVerification = () => {
        verificationForm.post('/account/settings/verification');
    };

    return (
        <AccountLayout title="Security">
            
                    <SettingsNav />
                    {flash.success && (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.success}
                        </p>
                    )}
                    {flash.status && (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.status}
                        </p>
                    )}

                    <div className="mb-6 space-y-3 rounded-xl border border-line bg-white p-6">
                        <h2 className="text-lg font-semibold text-ink">Email verification</h2>
                        <p className="text-sm text-muted">{profile.email}</p>
                        <p className="text-sm font-medium text-ink">
                            {profile.email_verified ? 'Verified' : 'Not verified'}
                        </p>
                        {profile.email_verified ? (
                            <p className="text-sm text-muted">Your email address is verified.</p>
                        ) : (
                            <button
                                type="button"
                                disabled={verificationForm.processing}
                                onClick={resendVerification}
                                className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                            >
                                Send verification email
                            </button>
                        )}
                    </div>

                    <form onSubmit={submitPassword} className="space-y-4 rounded-xl border border-line bg-white p-6">
                        <h2 className="text-lg font-semibold text-ink">Change password</h2>
                        <FormField
                            id="current_password"
                            label="Current password"
                            type="password"
                            autoComplete="current-password"
                            value={passwordForm.data.current_password}
                            onChange={(event) => passwordForm.setData('current_password', event.target.value)}
                        />
                        {passwordForm.errors.current_password && (
                            <p className="-mt-2 text-sm text-danger">{passwordForm.errors.current_password}</p>
                        )}
                        <FormField
                            id="password"
                            label="New password"
                            type="password"
                            autoComplete="new-password"
                            value={passwordForm.data.password}
                            onChange={(event) => passwordForm.setData('password', event.target.value)}
                        />
                        <PasswordStrength password={passwordForm.data.password} />
                        {passwordForm.errors.password && (
                            <p className="-mt-2 text-sm text-danger">{passwordForm.errors.password}</p>
                        )}
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
                            className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                        >
                            Update password
                        </button>
                    </form>
                </AccountLayout>
    );
}
