import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import FormField from '@/Components/Public/FormField';
import type { AccountProfile } from '@/types/account';

type Props = {
    profile: AccountProfile;
};

export default function ChangeEmailPanel({ profile }: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm({ email: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/account/settings/email/change', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    return (
        <div className="space-y-2">
            {profile.pending_email ? (
                <p className="text-sm text-muted">
                    Confirmation pending for <span className="font-medium text-ink">{profile.pending_email}</span>. Your
                    current email stays active until you confirm the link we sent.
                </p>
            ) : null}
            {open ? (
                <form onSubmit={submit} className="space-y-3">
                    <FormField
                        id="new-email"
                        label="New email address"
                        type="email"
                        autoComplete="email"
                        value={form.data.email}
                        onChange={(event) => form.setData('email', event.target.value)}
                    />
                    {form.errors.email ? <p className="text-sm text-danger">{form.errors.email}</p> : null}
                    <div className="flex flex-wrap gap-2">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="inline-flex min-h-11 items-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                        >
                            {form.processing ? 'Sending…' : 'Send confirmation link'}
                        </button>
                        <button
                            type="button"
                            className="inline-flex min-h-11 items-center rounded-md border border-line px-4 py-2 text-sm font-semibold text-ink"
                            onClick={() => {
                                setOpen(false);
                                form.reset();
                            }}
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            ) : (
                <button
                    type="button"
                    className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                    onClick={() => setOpen(true)}
                >
                    Change email
                </button>
            )}
        </div>
    );
}
