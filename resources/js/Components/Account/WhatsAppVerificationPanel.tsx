import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import FormField from '@/Components/Public/FormField';
import type { AccountProfile } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type Props = {
    profile: AccountProfile;
    retryAfter: number;
};

export default function WhatsAppVerificationPanel({ profile, retryAfter }: Props) {
    const flash = usePage<SharedPageProps>().props.flash;
    const errors = usePage<SharedPageProps>().props.errors ?? {};
    const sendForm = useForm({});
    const verifyForm = useForm({ otp: '' });
    const [secondsLeft, setSecondsLeft] = useState(Math.max(0, retryAfter));
    const [showOtp, setShowOtp] = useState(retryAfter > 0);

    useEffect(() => {
        setSecondsLeft(Math.max(0, retryAfter));
        if (retryAfter > 0) {
            setShowOtp(true);
        }
    }, [retryAfter]);

    useEffect(() => {
        const fromFlash = flash.whatsapp_otp_retry_after;
        if (typeof fromFlash === 'number' && fromFlash > 0) {
            setSecondsLeft(fromFlash);
            setShowOtp(true);
        }
    }, [flash.whatsapp_otp_retry_after, flash.status]);

    useEffect(() => {
        if (secondsLeft <= 0) {
            return;
        }

        const timer = window.setTimeout(() => setSecondsLeft((value) => Math.max(0, value - 1)), 1000);

        return () => window.clearTimeout(timer);
    }, [secondsLeft]);

    const requestCode = () => {
        sendForm.post('/account/settings/whatsapp/send-otp', {
            preserveScroll: true,
            onSuccess: () => {
                setShowOtp(true);
                verifyForm.reset();
            },
        });
    };

    const submitOtp = (event: FormEvent) => {
        event.preventDefault();
        verifyForm.post('/account/settings/whatsapp/verify', {
            preserveScroll: true,
            onSuccess: () => {
                setShowOtp(false);
                verifyForm.reset();
            },
        });
    };

    return (
        <div className="space-y-3 rounded-md border border-line px-3 py-3">
            <p className="text-sm font-medium text-ink">
                {profile.whatsapp_verified ? 'Verified' : 'Not verified'}
            </p>
            <p className="text-xs text-muted">WhatsApp verification is separate from SMS mobile verification.</p>
            {!profile.whatsapp_number ? (
                <p className="text-sm text-muted">Save a WhatsApp number before requesting a verification code.</p>
            ) : null}
            {profile.whatsapp_number && !profile.whatsapp_verified ? (
                <>
                    <button
                        type="button"
                        disabled={sendForm.processing || secondsLeft > 0}
                        onClick={requestCode}
                        className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                    >
                        {showOtp ? 'Resend' : 'Send verification code'}
                    </button>
                    {secondsLeft > 0 ? (
                        <p className="text-xs text-muted">You can resend in {secondsLeft} seconds.</p>
                    ) : null}
                    {errors.whatsapp_number ? <p className="text-sm text-danger">{errors.whatsapp_number}</p> : null}
                    {showOtp ? (
                        <form onSubmit={submitOtp} className="space-y-3">
                            <p className="text-sm text-muted">Enter the 6-digit code sent to your WhatsApp number.</p>
                            <FormField
                                id="whatsapp_otp"
                                label="WhatsApp code"
                                value={verifyForm.data.otp}
                                autoComplete="one-time-code"
                                onChange={(event) => verifyForm.setData('otp', event.target.value)}
                            />
                            {verifyForm.errors.otp && <p className="-mt-2 text-sm text-danger">{verifyForm.errors.otp}</p>}
                            {errors.otp && !verifyForm.errors.otp ? (
                                <p className="text-sm text-danger">{errors.otp}</p>
                            ) : null}
                            <button
                                type="submit"
                                disabled={verifyForm.processing}
                                className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                            >
                                Verify WhatsApp
                            </button>
                        </form>
                    ) : null}
                </>
            ) : null}
        </div>
    );
}
