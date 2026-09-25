import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import SettingsNav from '@/Components/Account/SettingsNav';
import FormField from '@/Components/Public/FormField';
import PageContainer from '@/Components/Public/PageContainer';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountLanguageOption, AccountProfile } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type AccountSettingsProps = {
    role: 'candidate' | 'employer';
    profile: AccountProfile;
    languages: AccountLanguageOption[];
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function AccountSettings({ profile, languages }: AccountSettingsProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const form = useForm({
        username: profile.username ?? '',
        mobile: profile.mobile ?? '',
        preferred_language: profile.preferred_language || 'en',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/account/settings/account');
    };

    return (
        <AccountLayout title="Account settings">
            
                    <SettingsNav />
                    {flash.success && (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.success}
                        </p>
                    )}
                    <form onSubmit={submit} className="space-y-4 rounded-xl border border-line bg-white p-6">
                        <FormField
                            id="username"
                            label="Username"
                            value={form.data.username}
                            autoComplete="username"
                            onChange={(event) => form.setData('username', event.target.value)}
                        />
                        {form.errors.username && <p className="-mt-2 text-sm text-danger">{form.errors.username}</p>}
                        <div>
                            <p className="mb-1 text-sm font-medium text-ink">Email</p>
                            <p className="text-sm text-muted">{profile.email}</p>
                            <p className="mt-1 text-xs text-muted">
                                {profile.email_verified ? 'Verified' : 'Not verified'}
                            </p>
                        </div>
                        <FormField
                            id="mobile"
                            label="Mobile"
                            value={form.data.mobile}
                            onChange={(event) => form.setData('mobile', event.target.value)}
                        />
                        {form.errors.mobile && <p className="-mt-2 text-sm text-danger">{form.errors.mobile}</p>}
                        <div>
                            <label htmlFor="preferred_language" className="mb-1 block text-sm font-medium text-ink">
                                Language
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
                            {form.errors.preferred_language && (
                                <p className="mt-1 text-sm text-danger">{form.errors.preferred_language}</p>
                            )}
                        </div>
                        <div>
                            <p className="mb-1 text-sm font-medium text-ink">Account status</p>
                            <p className="text-sm text-muted">{profile.account_status_label}</p>
                            {profile.business_id ? (
                                <p className="mt-1 text-xs text-muted">OurNear ID {profile.business_id}</p>
                            ) : null}
                        </div>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                        >
                            Save account
                        </button>
                    </form>
                </AccountLayout>
    );
}
