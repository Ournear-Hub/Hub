import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import SettingsNav from '@/Components/Account/SettingsNav';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountCommunicationChannel } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type CommunicationSettingsProps = {
    role: 'candidate' | 'employer';
    channels: AccountCommunicationChannel[];
};

export default function CommunicationSettings({ channels }: CommunicationSettingsProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const form = useForm({
        channels: Object.fromEntries(channels.map((channel) => [channel.value, channel.opted_in])),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/account/settings/communication');
    };

    return (
        <AccountLayout
            title="Communication preferences"
            breadcrumbs={[
                { label: 'Settings', href: '/account/settings' },
                { label: 'Communication' },
            ]}
        >
            <SettingsNav />
            {flash.success ? (
                <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success" role="status">
                    {flash.success}
                </p>
            ) : null}

            <form onSubmit={submit} className="mx-auto max-w-3xl space-y-4 rounded-xl border border-line bg-white p-4 sm:p-6 lg:max-w-none">
                <div>
                    <h1 className="text-lg font-semibold text-ink">Communication preferences</h1>
                    <p className="mt-2 text-sm text-muted">
                        Each toggle maps to a stored preference in your account. Push notifications control in-app recruitment
                        updates; product/system notifications control operational email. Marketing channels are separate.
                    </p>
                </div>

                <ul className="divide-y divide-line">
                    {channels.map((channel) => (
                        <li key={channel.value} className="flex items-center justify-between gap-3 py-4 first:pt-0 last:pb-0">
                            <div>
                                <p className="text-sm font-medium text-ink">{channel.label}</p>
                                {channel.value === 'push' ? (
                                    <p className="text-xs text-muted">In-app notifications in your account notification center.</p>
                                ) : null}
                                {channel.value === 'system' ? (
                                    <p className="text-xs text-muted">Operational email about applications and interviews.</p>
                                ) : null}
                            </div>
                            <label className="inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm">
                                <span className="sr-only">{channel.label}</span>
                                <input
                                    type="checkbox"
                                    checked={Boolean(form.data.channels[channel.value])}
                                    onChange={(event) =>
                                        form.setData('channels', {
                                            ...form.data.channels,
                                            [channel.value]: event.target.checked,
                                        })
                                    }
                                    className="h-5 w-5 rounded border-line text-brand focus:ring-brand"
                                />
                            </label>
                        </li>
                    ))}
                </ul>

                <button
                    type="submit"
                    disabled={form.processing}
                    className="inline-flex min-h-11 items-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                >
                    Save preferences
                </button>
            </form>
        </AccountLayout>
    );
}
