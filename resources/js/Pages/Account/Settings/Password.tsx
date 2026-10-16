import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import SettingsPageShell from '@/Components/Account/SettingsPageShell';
import PasswordStrength from '@/Components/Account/PasswordStrength';
import FormField from '@/Components/Public/FormField';
import Alert from '@/Components/molecules/Alert';
import type { SharedPageProps } from '@/types/page';

export default function PasswordSettings() {
    const flash = usePage<SharedPageProps>().props.flash;
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submitPassword = (event: FormEvent) => {
        event.preventDefault();
        passwordForm.put('/account/settings/password', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };

    return (
        <SettingsPageShell
            flash={
                flash.success ? (
                    <Alert variant="success" className="mb-4">
                        {flash.success}
                    </Alert>
                ) : null
            }
        >
            <header className="mb-6">
                <h1 className="text-xl font-semibold text-ink">Change password</h1>
                <p className="mt-1 text-sm text-muted">Use a strong password you do not reuse on other sites.</p>
            </header>
            <form onSubmit={submitPassword} className="rounded-xl border border-line bg-white p-4 sm:p-6">
                <div className="space-y-4">
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
                        {passwordForm.processing ? 'Updating…' : 'Update password'}
                    </button>
                </div>
            </form>
        </SettingsPageShell>
    );
}
