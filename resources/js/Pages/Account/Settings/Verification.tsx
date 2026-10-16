import { usePage } from '@inertiajs/react';
import VerificationCenter, { type HiringActivationGate } from '@/Components/Account/Verification/VerificationCenter';
import SettingsPageShell from '@/Components/Account/SettingsPageShell';
import Alert from '@/Components/molecules/Alert';
import type { AccountProfile } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type VerificationProps = {
    role: 'candidate' | 'employer';
    profile: AccountProfile;
    mobile_otp_retry_after?: number;
    hiring_gate?: HiringActivationGate | null;
};

export default function VerificationSettings({
    profile,
    role,
    mobile_otp_retry_after = 0,
    hiring_gate = null,
}: VerificationProps) {
    const flash = usePage<SharedPageProps>().props.flash;

    return (
        <SettingsPageShell
            flash={
                flash.success || flash.status ? (
                    <Alert variant="success" className="mb-4">
                        {flash.success ?? flash.status}
                    </Alert>
                ) : null
            }
        >
            <header className="mb-6">
                <h1 className="text-xl font-semibold text-ink">Verification</h1>
                <p className="mt-1 text-sm text-muted">
                    Email and mobile verification use your saved contact details. Status reflects verified flags from
                    the server, not contact presence alone.
                </p>
            </header>
            <VerificationCenter
                profile={profile}
                role={role}
                mobileOtpRetryAfter={mobile_otp_retry_after}
                hiringGate={hiring_gate}
            />
        </SettingsPageShell>
    );
}
