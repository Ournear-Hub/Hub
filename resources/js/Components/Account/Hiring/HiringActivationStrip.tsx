import { Link } from '@inertiajs/react';
import Button from '@/Components/atoms/Button';

type Gate = {
    hiring_active: boolean;
    message: string;
    email_verified: boolean;
    mobile_verified: boolean;
    unlimited_publishing?: boolean;
    trial_available?: boolean;
    trial_active?: boolean;
    trial_expired?: boolean;
    trial_expires_at?: string | null;
};

type Props = {
    gate: Gate;
    canPostJob?: boolean;
    jobsCount?: number;
};

function formatExpiry(iso: string): string {
    try {
        return new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}

function trialLabel(gate: Gate): string | null {
    if (gate.unlimited_publishing) {
        return null;
    }
    if (gate.trial_active && gate.trial_expires_at) {
        return `Trial job active — expires at ${formatExpiry(gate.trial_expires_at)}`;
    }
    if (gate.trial_available) {
        return 'Trial job available';
    }
    if (gate.trial_expired || (!gate.trial_available && gate.hiring_active)) {
        return 'Verification required for additional publishing';
    }
    if (gate.trial_expired) {
        return 'Trial job expired';
    }

    return null;
}

export default function HiringActivationStrip({ gate, canPostJob = true, jobsCount = 0 }: Props) {
    if (!gate.hiring_active) {
        return null;
    }

    const trial = trialLabel(gate);

    return (
        <section className="rounded-xl border border-brand/20 bg-brand/5 p-4 sm:p-5" aria-labelledby="hiring-workspace-banner">
            <p id="hiring-workspace-banner" className="text-sm text-ink">
                {gate.message}
            </p>
            {trial ? <p className="mt-2 text-sm font-semibold text-brand">{trial}</p> : null}
            {!gate.unlimited_publishing ? (
                <div className="mt-4 flex flex-wrap gap-3">
                    {!gate.email_verified ? (
                        <Button surface="public" href="/account/settings/verification" variant="primary" className="min-h-11">
                            Verify Email
                        </Button>
                    ) : null}
                    {!gate.mobile_verified ? (
                        <Button surface="public" type="button" variant="primary" className="min-h-11" disabled>
                            Verify Mobile (coming soon)
                        </Button>
                    ) : null}
                    {canPostJob ? (
                        <Button
                            surface="public"
                            href="/account/jobs/create"
                            variant={jobsCount === 0 ? 'primary' : 'secondary'}
                            className="min-h-11"
                        >
                            {jobsCount === 0 ? 'Create your first job' : 'Create Job'}
                        </Button>
                    ) : null}
                    <Link href="/account/jobs?listing=draft" className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline">
                        View Drafts
                    </Link>
                </div>
            ) : null}
        </section>
    );
}
