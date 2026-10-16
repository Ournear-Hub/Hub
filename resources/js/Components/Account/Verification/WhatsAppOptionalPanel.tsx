import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Button from '@/Components/atoms/Button';
import FormField from '@/Components/Public/FormField';
import type { AccountProfile } from '@/types/account';

type Props = {
    profile: AccountProfile;
    employerMode?: boolean;
};

export default function WhatsAppOptionalPanel({ profile, employerMode = false }: Props) {
    const savedNumber = profile.whatsapp_number?.trim() ?? '';
    const [editing, setEditing] = useState(savedNumber === '');

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
            only: ['profile'],
            onSuccess: () => setEditing(false),
        });
    };

    const cancelEdit = () => {
        form.setData('whatsapp_number', profile.whatsapp_number ?? '');
        form.clearErrors();
        setEditing(false);
    };

    return (
        <div className="space-y-3 text-sm">
            <p className="text-muted">
                {employerMode
                    ? 'WhatsApp verification is coming soon. Saving a number here updates your account and company contact views; it does not verify the number.'
                    : 'WhatsApp is optional and not required for account verification or job applications.'}
            </p>

            {editing ? (
                <form onSubmit={submit} className="space-y-3">
                    <FormField
                        id="verification_whatsapp_number"
                        label="WhatsApp number"
                        value={form.data.whatsapp_number}
                        onChange={(event) => form.setData('whatsapp_number', event.currentTarget.value)}
                    />
                    {form.errors.whatsapp_number ? (
                        <p className="text-sm text-danger">{form.errors.whatsapp_number}</p>
                    ) : null}
                    <div className="flex flex-wrap gap-2">
                        <Button
                            surface="public"
                            type="submit"
                            variant="primary"
                            className="min-h-11"
                            disabled={form.processing}
                        >
                            {form.processing ? 'Saving…' : 'Save'}
                        </Button>
                        {savedNumber !== '' ? (
                            <Button surface="public" type="button" variant="secondary" className="min-h-11" onClick={cancelEdit}>
                                Cancel
                            </Button>
                        ) : null}
                    </div>
                </form>
            ) : (
                <div className="space-y-2">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-muted">WhatsApp number</p>
                        <p className="mt-1 font-medium text-ink">{savedNumber || 'Not set'}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                            onClick={() => setEditing(true)}
                        >
                            Change
                        </button>
                        <Button surface="public" type="button" variant="secondary" className="min-h-11" disabled>
                            Verify WhatsApp (coming soon)
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
