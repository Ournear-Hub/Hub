import { Link } from '@inertiajs/react';
import { recorded } from '@/lib/recorded';

export type RecruitmentChainStep = {
    key: string;
    label: string;
    value?: string | null;
    href?: string | null;
    status?: string | null;
};

type Props = {
    steps: RecruitmentChainStep[];
};

export default function RecruitmentChain({ steps }: Props) {
    return (
        <ol className="space-y-1 text-sm">
            {steps.map((step, index) => (
                <li key={step.key}>
                    {index > 0 ? <div className="pl-1 text-muted">↓</div> : null}
                    <div className="rounded-md bg-surface px-3 py-2">
                        <p className="text-xs uppercase tracking-wide text-muted">{step.label}</p>
                        {step.href ? (
                            <Link href={step.href} className="font-medium text-brand">
                                {recorded(step.value ?? step.status)}
                            </Link>
                        ) : (
                            <p className="font-medium text-ink">{recorded(step.value ?? step.status)}</p>
                        )}
                        {step.status && step.value && step.status !== step.value ? (
                            <p className="text-xs text-muted">{step.status}</p>
                        ) : null}
                    </div>
                </li>
            ))}
        </ol>
    );
}
