import type { ReactNode } from 'react';
import StatusBadge from '@/Components/Account/StatusBadge';

type Props = {
    label: string;
    detail?: string | null;
    statusLabel: string;
    tone?: 'verified' | 'action' | 'optional' | 'neutral';
    children?: ReactNode;
};

export default function VerificationStatusRow({ label, detail, statusLabel, children }: Props) {
    return (
        <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <h3 className="text-base font-semibold text-ink">{label}</h3>
                    {detail ? <p className="mt-1 break-all text-sm text-muted">{detail}</p> : null}
                </div>
                <StatusBadge status={statusLabel} />
            </div>
            {children ? <div className="mt-4">{children}</div> : null}
        </section>
    );
}
