import { useForm } from '@inertiajs/react';
import Button from '@/Components/Public/Button';
import MobileVerificationPanel from '@/Components/Account/MobileVerificationPanel';
import ChangeEmailPanel from '@/Components/Account/Verification/ChangeEmailPanel';
import ChangeMobilePanel from '@/Components/Account/Verification/ChangeMobilePanel';
import VerificationStatusRow from '@/Components/Account/Verification/VerificationStatusRow';
import WhatsAppOptionalPanel from '@/Components/Account/Verification/WhatsAppOptionalPanel';
import type { AccountProfile } from '@/types/account';

export type HiringActivationGate = {
    email_verified: boolean;
    mobile_verified: boolean;
    whatsapp_required: boolean;
    is_employer: boolean;
    has_company: boolean;
    company_name?: string | null;
    hiring_email?: string | null;
    hiring_email_verified?: boolean;
    uses_account_email?: boolean;
    hiring_active: boolean;
    message: string;
};

type Props = {
    profile: AccountProfile;
    role: 'candidate' | 'employer';
    mobileOtpRetryAfter: number;
    hiringGate?: HiringActivationGate | null;
};

export default function VerificationCenter({ profile, role, mobileOtpRetryAfter, hiringGate }: Props) {
    const verificationForm = useForm({});

    const resendVerification = () => {
        verificationForm.post('/account/settings/verification', { preserveScroll: true });
    };

    const showHiringBlock = role === 'employer' && hiringGate && !hiringGate.hiring_active;

    return (
        <div className="space-y-4">
            {showHiringBlock ? (
                <section className="rounded-xl border-2 border-accent/30 bg-accent/5 p-4 sm:p-5" aria-live="polite">
                    <h2 className="text-base font-semibold text-ink">Hiring activation</h2>
                    <p className="mt-2 text-sm text-muted">{hiringGate.message}</p>
                    <ul className="mt-3 space-y-1 text-sm text-ink">
                        <li>
                            {hiringGate.email_verified ? 'Complete' : 'Pending'} — Account email verified
                        </li>
                        <li>
                            {hiringGate.mobile_verified ? 'Complete' : 'Pending'} — Mobile verified (required for Hiring)
                        </li>
                        {hiringGate.hiring_email ? (
                            <li>
                                {hiringGate.hiring_email_verified ? 'Complete' : 'Pending'} — Recruiter email (
                                {hiringGate.hiring_email})
                            </li>
                        ) : null}
                        <li>{hiringGate.has_company ? 'Complete' : 'Pending'} — Company profile on file</li>
                    </ul>
                    {!hiringGate.mobile_verified ? (
                        <p className="mt-3 text-sm font-medium text-ink">Verify mobile below to activate Hiring.</p>
                    ) : null}
                    <Button href="/account/hiring" variant="secondary" className="mt-4 min-h-11 w-full sm:w-auto">
                        Open Hiring workspace
                    </Button>
                </section>
            ) : null}

            <VerificationStatusRow
                label="Email"
                detail={profile.email}
                statusLabel={profile.email_verified ? 'Verified' : 'Action required'}
            >
                {profile.email_verified ? (
                    <div className="space-y-2">
                        <p className="text-sm text-muted">Your email address is verified.</p>
                        <ChangeEmailPanel profile={profile} />
                    </div>
                ) : (
                    <div className="space-y-2">
                        <p className="text-sm text-muted">We will send a verification link to your inbox.</p>
                        <button
                            type="button"
                            disabled={verificationForm.processing}
                            onClick={resendVerification}
                            className="inline-flex min-h-11 items-center rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                        >
                            {verificationForm.processing ? 'Sending…' : 'Send verification email'}
                        </button>
                        <ChangeEmailPanel profile={profile} />
                    </div>
                )}
            </VerificationStatusRow>

            <VerificationStatusRow
                label="Mobile"
                detail={profile.mobile || 'No mobile number saved'}
                statusLabel={profile.mobile_verified ? 'Verified' : profile.mobile ? 'Action required' : 'Optional'}
            >
                {role === 'employer' && !profile.mobile_verified ? (
                    <p className="mb-3 text-sm text-muted">
                        Mobile verification is required to activate the Hiring workspace and post jobs as an employer.
                    </p>
                ) : (
                    <p className="mb-3 text-sm text-muted">Optional for candidates; recommended for account recovery.</p>
                )}
                {!profile.mobile ? (
                    <ChangeMobilePanel profile={profile} />
                ) : (
                    <>
                        <ChangeMobilePanel profile={profile} />
                        <MobileVerificationPanel profile={profile} retryAfter={mobileOtpRetryAfter} />
                    </>
                )}
            </VerificationStatusRow>

            <VerificationStatusRow label="WhatsApp" statusLabel="Optional" detail={profile.whatsapp_number || undefined}>
                <WhatsAppOptionalPanel profile={profile} />
            </VerificationStatusRow>
        </div>
    );
}
