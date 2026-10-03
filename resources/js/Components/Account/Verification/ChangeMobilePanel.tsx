import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import FormField from '@/Components/Public/FormField';
import type { AccountProfile } from '@/types/account';

type Props = {
    profile: AccountProfile;
    onMobileSaved?: () => void;
};

export default function ChangeMobilePanel({ profile, onMobileSaved }: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        username: profile.username ?? '',
        mobile: profile.mobile ?? '',
        whatsapp_number: profile.whatsapp_number ?? '',
        preferred_language: profile.preferred_language || 'en',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/account/settings/account', {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                onMobileSaved?.();
            },
        });
    };

    return (
        <div className="space-y-2">
            {open ? (
                <form onSubmit={submit} className="space-y-3">
                    <FormField
                        id="change-mobile"
                        label="New mobile number"
                        value={form.data.mobile}
                        onChange={(event) => form.setData('mobile', event.target.value)}
                    />
                    {form.errors.mobile ? <p className="text-sm text-danger">{form.errors.mobile}</p> : null}
                    <p className="text-xs text-muted">
                        After saving, verify the new number with the code we send. Your previous number stays verified
                        until the number changes.
                    </p>
                    <div className="flex flex-wrap gap-2">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="inline-flex min-h-11 items-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                        >
                            {form.processing ? 'Saving…' : 'Save new mobile'}
                        </button>
                        <button
                            type="button"
                            className="inline-flex min-h-11 items-center rounded-md border border-line px-4 py-2 text-sm font-semibold text-ink"
                            onClick={() => {
                                setOpen(false);
                                form.setData('mobile', profile.mobile ?? '');
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
                    Change mobile
                </button>
            )}
        </div>
    );
}
