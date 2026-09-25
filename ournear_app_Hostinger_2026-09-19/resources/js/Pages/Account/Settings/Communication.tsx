import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import SettingsNav from '@/Components/Account/SettingsNav';
import PageContainer from '@/Components/Public/PageContainer';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountCommunicationChannel } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type CommunicationSettingsProps = {
    role: 'candidate';
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
        <AccountLayout title="Communication preferences">
            
                    <SettingsNav />
                    {flash.success ? (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.success}
                        </p>
                    ) : null}
                    <form onSubmit={submit} className="space-y-4 rounded-xl border border-line bg-white p-6">
                        {channels.map((channel) => (
                            <label key={channel.value} className="flex items-center justify-between gap-3 text-sm text-ink">
                                <span>{channel.label}</span>
                                <input
                                    type="checkbox"
                                    checked={Boolean(form.data.channels[channel.value])}
                                    onChange={(event) =>
                                        form.setData('channels', {
                                            ...form.data.channels,
                                            [channel.value]: event.target.checked,
                                        })
                                    }
                                />
                            </label>
                        ))}
                        <p className="text-xs text-muted">
                            Application updates are still recorded in your account even if you turn off a channel.
                        </p>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-brand px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
                        >
                            Save preferences
                        </button>
                    </form>
                </AccountLayout>
    );
}
