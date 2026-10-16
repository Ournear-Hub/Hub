import { Link } from '@inertiajs/react';
import Button from '@/Components/atoms/Button';
import StatusBadge from '@/Components/Account/StatusBadge';
import type { AccountProfile } from '@/types/account';

type Props = {
    profile: AccountProfile;
};

export default function CompanyContactVerificationSection({ profile }: Props) {
    const verificationHref = '/account/settings/verification';

    return (
        <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
            <h2 className="text-base font-semibold text-ink">Contact &amp; verification</h2>
            <p className="mt-1 text-sm text-muted">Saved company contact details and verification status from your account.</p>
            <ul className="mt-4 space-y-4">
                <li className="flex flex-col gap-2 border-b border-line pb-4 last:border-b-0 last:pb-0 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-ink">Email</p>
                        <p className="mt-0.5 break-all text-sm text-muted">{profile.email || 'Not saved'}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <StatusBadge status={profile.email_verified ? 'Verified' : 'Action required'} />
                        {!profile.email_verified ? (
                            <Button surface="public" href={verificationHref} variant="outline" className="min-h-9 text-xs">
                                Verify email
                            </Button>
                        ) : null}
                    </div>
                </li>
                <li className="flex flex-col gap-2 border-b border-line pb-4 last:border-b-0 last:pb-0 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-ink">Mobile</p>
                        <p className="mt-0.5 break-all text-sm text-muted">
                            {profile.mobile_number || profile.mobile || 'Not saved'}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <StatusBadge status={profile.mobile_verified ? 'Verified' : 'Not verified'} />
                        <Button surface="public" type="button" variant="outline" className="min-h-9 text-xs" disabled>
                            Verify mobile (coming soon)
                        </Button>
                    </div>
                </li>
                <li className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        <p className="text-sm font-medium text-ink">WhatsApp</p>
                        <p className="mt-0.5 break-all text-sm text-muted">{profile.whatsapp_number || 'Not saved'}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <StatusBadge status={profile.whatsapp_verified ? 'Verified' : 'Not verified'} />
                        <Button surface="public" type="button" variant="outline" className="min-h-9 text-xs" disabled>
                            Verify WhatsApp (coming soon)
                        </Button>
                    </div>
                </li>
            </ul>
            <p className="mt-4 text-xs text-muted">
                Mobile and WhatsApp verification will be enabled in a later release. Update your WhatsApp number on the{' '}
                <Link href={verificationHref} className="font-semibold text-brand hover:underline">
                    Verification
                </Link>{' '}
                page or in Account settings.
            </p>
        </section>
    );
}
