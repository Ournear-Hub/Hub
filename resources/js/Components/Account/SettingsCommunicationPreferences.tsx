import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import type { AccountCommunicationChannel } from '@/types/account';

type Props = {
    channels: AccountCommunicationChannel[];
    id?: string;
};

export default function SettingsCommunicationPreferences({ channels, id = 'communication' }: Props) {
    const form = useForm({
        channels: Object.fromEntries(channels.map((channel) => [channel.value, channel.opted_in])),
    });

    if (channels.length === 0) {
        return null;
    }

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/account/settings/communication', { preserveScroll: true });
    };

    return (
        <section id={id} className="space-y-4 border-t border-line pt-6">
            <div>
                <h2 className="text-base font-semibold text-ink">Communication preferences</h2>
                <p className="mt-1 text-sm text-muted">
                    Control marketing and product notification channels. Preferences are stored on your account and used
                    by notification services.
                </p>
            </div>
            <form onSubmit={submit} className="space-y-4">
            <ul className="divide-y divide-line">
                {channels.map((channel) => (
                    <li key={channel.value} className="flex items-center justify-between gap-3 py-4 first:pt-0 last:pb-0">
                        <div>
                            <p className="text-sm font-medium text-ink">{channel.label}</p>
                            {channel.value === 'push' ? (
                                <p className="text-xs text-muted">In-app notifications in your notification center.</p>
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
            {form.errors.channels ? <p className="text-sm text-danger">{form.errors.channels}</p> : null}
            <button
                type="submit"
                disabled={form.processing}
                className="inline-flex min-h-11 items-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white hover:opacity-95 disabled:opacity-60"
            >
                {form.processing ? 'Saving…' : 'Save communication preferences'}
            </button>
        </form>
        </section>
    );
}
