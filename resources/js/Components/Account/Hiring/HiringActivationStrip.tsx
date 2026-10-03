import { Link } from '@inertiajs/react';
import Button from '@/Components/Public/Button';

type Gate = {
    hiring_active: boolean;
    message: string;
    email_verified: boolean;
    mobile_verified: boolean;
    hiring_email_verified?: boolean;
    has_company?: boolean;
};

type Props = {
    gate: Gate;
};

export default function HiringActivationStrip({ gate }: Props) {
    if (gate.hiring_active) {
        return null;
    }

    return (
        <section className="rounded-xl border border-accent/30 bg-accent/5 p-4 sm:p-5" aria-labelledby="hiring-activation-strip">
            <p id="hiring-activation-strip" className="text-xs font-semibold uppercase tracking-wide text-accent">
                Complete hiring setup
            </p>
            <p className="mt-2 text-sm text-ink">{gate.message}</p>
            <p className="mt-1 text-sm text-muted">
                You can browse the hiring workspace. Posting jobs and other protected actions stay gated until verification
                is complete.
            </p>
            <div className="mt-4 flex flex-wrap gap-3">
                <Button href="/account/hiring#setup" variant="primary" className="min-h-11">
                    Finish setup
                </Button>
                {!gate.email_verified || !gate.mobile_verified ? (
                    <Link href="/account/settings/security" className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                        Verification settings
                    </Link>
                ) : null}
            </div>
        </section>
    );
}
