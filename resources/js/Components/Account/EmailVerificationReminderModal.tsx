import { router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Button from '@/Components/atoms/Button';
import OurNearIcon from '@/icons/OurNearIcon';
import type { SharedPageProps } from '@/types/page';

type ReminderProps = {
    email: string;
};

export default function EmailVerificationReminderModal() {
    const { emailVerificationReminder } = usePage<SharedPageProps>().props;
    const [open, setOpen] = useState(true);
    const resendForm = useForm({});

    if (!emailVerificationReminder || !open) {
        return null;
    }

    const { email } = emailVerificationReminder as ReminderProps;

    const dismiss = () => {
        setOpen(false);
        router.post('/account/email-verification-reminder/dismiss', {}, { preserveScroll: true });
    };

    const resend = () => {
        resendForm.post('/account/settings/verification', {
            preserveScroll: true,
            onSuccess: () => {
                resendForm.clearErrors();
            },
        });
    };

    return (
        <div className="fixed inset-0 z-[100] flex items-end justify-center bg-black/40 p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="email-verify-reminder-title">
            <div className="relative w-full max-w-md rounded-xl border border-line bg-white p-5 shadow-lg">
                <button
                    type="button"
                    className="absolute right-3 top-3 rounded-md p-1 text-muted hover:bg-surface hover:text-ink"
                    aria-label="Close"
                    onClick={dismiss}
                >
                    <OurNearIcon name="close" size="sm" decorative />
                </button>
                <h2 id="email-verify-reminder-title" className="pr-8 text-lg font-semibold text-ink">
                    Verify your email address
                </h2>
                <p className="mt-2 text-sm text-muted">
                    Your account is not verified yet. We sent a link to <span className="font-medium text-ink">{email}</span>.
                    Open the link in your inbox to unlock the full candidate experience.
                </p>
                {resendForm.recentlySuccessful ? (
                    <p className="mt-3 text-sm text-success" role="status">
                        Verification email sent. Check your inbox.
                    </p>
                ) : null}
                {resendForm.errors && Object.keys(resendForm.errors).length > 0 ? (
                    <p className="mt-3 text-sm text-danger" role="alert">
                        Could not send the verification email. Try again shortly.
                    </p>
                ) : null}
                <div className="mt-4 flex flex-wrap gap-2">
                    <Button surface="public" variant="primary" type="button" loading={resendForm.processing} onClick={resend}>
                        Resend verification email
                    </Button>
                    <Button surface="public" variant="secondary" type="button" href="/account/settings/verification">
                        Verification settings
                    </Button>
                </div>
            </div>
        </div>
    );
}
