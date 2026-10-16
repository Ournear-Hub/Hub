import type { PropsWithChildren, ReactNode } from 'react';

type Props = PropsWithChildren<{
    title: string;
    description?: string;
    actions?: ReactNode;
}>;

export default function LabPanel({ title, description, actions, children }: Props) {
    return (
        <section className="rounded-xl border border-line bg-white p-5 shadow-sm sm:p-6">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 className="text-base font-semibold text-ink">{title}</h3>
                    {description ? <p className="mt-1 text-sm text-muted">{description}</p> : null}
                </div>
                {actions ? <div className="flex flex-wrap gap-2">{actions}</div> : null}
            </div>
            <div className="mt-4">{children}</div>
        </section>
    );
}
