import { Link } from '@inertiajs/react';
import type { AccountCompletionItem } from '@/types/account';

type MissingInformationListProps = {
    items: AccountCompletionItem[];
    completion: number;
    href?: string;
    hrefLabel?: string;
};

export default function MissingInformationList({
    items,
    completion,
    href = '/account/profile',
    hrefLabel = 'Complete profile',
}: MissingInformationListProps) {
    const incomplete = items.filter((item) => !item.complete);

    return (
        <section className="rounded-xl border border-line bg-white p-5 sm:p-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="text-lg font-semibold text-ink">Profile completeness</h2>
                <p className="text-sm font-semibold text-brand">{completion}% complete</p>
            </div>
            <div className="mt-3 h-2 overflow-hidden rounded-full bg-surface" aria-hidden="true">
                <div
                    className="h-full rounded-full bg-brand"
                    style={{ width: `${Math.max(0, Math.min(100, completion))}%` }}
                />
            </div>
            <p className="mt-3 text-sm text-muted">Complete your profile:</p>
            <ul className="mt-2 space-y-1 text-sm text-ink">
                {items.map((item) => (
                    <li key={item.key}>
                        {item.complete ? '✓' : '○'} {item.label}
                    </li>
                ))}
            </ul>
            {incomplete.length > 0 ? (
                <Link href={href} className="mt-3 inline-block text-sm font-medium text-brand hover:underline">
                    {hrefLabel}
                </Link>
            ) : (
                <p className="mt-3 text-sm text-success">Required profile information is complete.</p>
            )}
        </section>
    );
}
