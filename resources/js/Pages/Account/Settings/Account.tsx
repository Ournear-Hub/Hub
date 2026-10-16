import { Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import SettingsPageShell from '@/Components/Account/SettingsPageShell';
import FormField from '@/Components/Public/FormField';
import type { AccountLanguageOption, AccountProfile } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type AccountSettingsProps = {
    role: 'candidate' | 'employer';
    profile: AccountProfile;
    languages: AccountLanguageOption[];
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function AccountSettings({
    profile,
    languages,
    role,
}: AccountSettingsProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const form = useForm({
        username: profile.username ?? '',
        mobile: profile.mobile ?? '',
        whatsapp_number: profile.whatsapp_number ?? '',
        preferred_language: profile.preferred_language || 'en',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/account/settings/account', { preserveScroll: true });
    };

    return (
        <SettingsPageShell
            flash={
                flash.success ? (
                    <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success" role="status">
                        {flash.success}
                    </p>
                ) : null
            }
        >
            <form onSubmit={submit} className="space-y-4 rounded-xl border border-line bg-white p-4 sm:p-6">
                <header>
                    <h1 className="text-xl font-semibold text-ink">Account</h1>
                    <p className="mt-1 text-sm text-muted">
                        Username, contact numbers, and account language. Verify email and mobile under{' '}
                        <Link href="/account/settings/verification" className="font-semibold text-brand hover:underline">
                            Verification
                        </Link>
                        .
                    </p>
                </header>

                <FormField
                    id="username"
                    label="Username"
                    value={form.data.username}
                    autoComplete="username"
                    onChange={(event) => form.setData('username', event.target.value)}
                />
                {form.errors.username ? <p className="-mt-2 text-sm text-danger">{form.errors.username}</p> : null}

                <div>
                    <p className="mb-1 text-sm font-medium text-ink">Email</p>
                    <p className="text-sm text-muted">{profile.email}</p>
                    <Link
                        href="/account/settings/verification"
                        className="mt-1 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                    >
                        Manage email verification
                    </Link>
                </div>

                <FormField
                    id="mobile"
                    label="Mobile"
                    value={form.data.mobile}
                    onChange={(event) => form.setData('mobile', event.target.value)}
                />
                {form.errors.mobile ? <p className="-mt-2 text-sm text-danger">{form.errors.mobile}</p> : null}
                <p className="text-xs text-muted">
                    After saving, verify mobile in Verification
                    {role === 'employer' ? ' (required for Hiring activation).' : '.'}
                </p>

                <FormField
                    id="whatsapp_number"
                    label="WhatsApp number (optional)"
                    value={form.data.whatsapp_number}
                    onChange={(event) => form.setData('whatsapp_number', event.target.value)}
                />
                {form.errors.whatsapp_number ? (
                    <p className="-mt-2 text-sm text-danger">{form.errors.whatsapp_number}</p>
                ) : null}
                <p className="text-xs text-muted">Optional. Used for account contact details when provided.</p>

                <div>
                    <label htmlFor="preferred_language" className="mb-1 block text-sm font-medium text-ink">
                        Account language
                    </label>
                    <select
                        id="preferred_language"
                        className={selectClass}
                        value={form.data.preferred_language}
                        onChange={(event) => form.setData('preferred_language', event.target.value)}
                    >
                        {languages.map((language) => (
                            <option key={language.value} value={language.value}>
                                {language.label}
                            </option>
                        ))}
                    </select>
                    {form.errors.preferred_language ? (
                        <p className="mt-1 text-sm text-danger">{form.errors.preferred_language}</p>
                    ) : null}
                </div>

                <div>
                    <p className="mb-1 text-sm font-medium text-ink">Account status</p>
                    <p className="text-sm text-muted">{profile.account_status_label}</p>
                </div>

                <button
                    type="submit"
                    disabled={form.processing}
                    className="inline-flex min-h-11 items-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                >
                    {form.processing ? 'Saving…' : 'Save account'}
                </button>
            </form>

        </SettingsPageShell>
    );
}
