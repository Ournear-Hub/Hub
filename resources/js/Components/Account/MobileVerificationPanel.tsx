import { useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import OtpSixInput from '@/Components/Account/Verification/OtpSixInput';
import type { AccountProfile } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type Props = {
    profile: AccountProfile;
    retryAfter: number;
};

export default function MobileVerificationPanel({ profile, retryAfter }: Props) {
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
        const fromFlash = flash.mobile_otp_retry_after;
        if (typeof fromFlash === 'number' && fromFlash > 0) {
            setSecondsLeft(fromFlash);
            setShowOtp(true);
        }
    }, [flash.mobile_otp_retry_after, flash.status]);

    useEffect(() => {
        if (secondsLeft <= 0) {
            return;
        }

        const timer = window.setTimeout(() => setSecondsLeft((value) => Math.max(0, value - 1)), 1000);

        return () => window.clearTimeout(timer);
    }, [secondsLeft]);

    const requestCode = () => {
        sendForm.post('/account/settings/mobile/send-otp', {
            preserveScroll: true,
            onSuccess: () => {
                setShowOtp(true);
                verifyForm.reset();
            },
        });
    };

    const submitOtp = (event: FormEvent) => {
        event.preventDefault();
        verifyForm.post('/account/settings/mobile/verify', {
            preserveScroll: true,
            onSuccess: () => {
                setShowOtp(false);
                verifyForm.reset();
            },
        });
    };

    if (profile.mobile_verified) {
        return <p className="text-sm text-muted">Your mobile number is verified.</p>;
    }

    return (
        <div className="space-y-3" aria-live="polite">
            <button
                type="button"
                disabled={sendForm.processing || secondsLeft > 0}
                onClick={requestCode}
                className="inline-flex min-h-11 items-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
            >
                {showOtp ? 'Resend code' : 'Send verification code'}
            </button>
            {secondsLeft > 0 ? (
                <p className="text-xs text-muted">You can request another code in {secondsLeft} seconds.</p>
            ) : null}
            {errors.mobile ? <p className="text-sm text-danger">{errors.mobile}</p> : null}
            {showOtp ? (
                <form onSubmit={submitOtp} className="space-y-3">
                    <p className="text-sm text-muted">Enter the 6-digit code sent to your mobile number.</p>
                    <OtpSixInput
                        idPrefix="mobile-otp"
                        value={verifyForm.data.otp}
                        onChange={(otp) => verifyForm.setData('otp', otp)}
                        disabled={verifyForm.processing}
                        error={verifyForm.errors.otp ?? (errors.otp as string | undefined) ?? null}
                    />
                    <button
                        type="submit"
                        disabled={verifyForm.processing || verifyForm.data.otp.length < 6}
                        className="inline-flex min-h-11 items-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                    >
                        {verifyForm.processing ? 'Verifying…' : 'Verify mobile'}
                    </button>
                </form>
            ) : null}
            {flash.success ? (
                <p className="text-sm text-success" role="status">
                    {flash.success}
                </p>
            ) : null}
        </div>
    );
}
