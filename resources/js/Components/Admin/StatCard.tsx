import { Link } from '@inertiajs/react';

type StatCardProps = {
    label: string;
    value: number | string | null;
    href?: string | null;
    hint?: string | null;
    emptyHint?: string;
};

export default function StatCard({ label, value, href, hint = null, emptyHint }: StatCardProps) {
    const content = (
        <>
            <p className="text-sm text-muted">{label}</p>
            <p className="mt-2 text-2xl font-semibold text-ink">{value === null ? '—' : value}</p>
            {value === null && (
                <p className="mt-1 text-xs text-disabled">{emptyHint ?? 'Available when domain data is added'}</p>
            )}
            {hint ? <p className="mt-1 text-xs text-muted">{hint}</p> : null}
        </>
    );

    const className = 'block rounded-xl border border-line bg-white p-4 sm:p-5';

    if (href) {
        return (
            <Link href={href} className={`${className} transition hover:border-brand/40 hover:shadow-sm`}>
                {content}
            </Link>
        );
    }

    return <article className={className}>{content}</article>;
}