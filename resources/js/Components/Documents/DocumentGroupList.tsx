import type { ReactNode } from 'react';

export type DocumentGroupItem = {
    id: string | number;
    group: string;
    title: string;
    meta?: string | null;
    identity?: string | null;
    actions?: ReactNode;
    extra?: ReactNode;
};

type DocumentGroupListProps = {
    items: DocumentGroupItem[];
    empty: string;
};

export default function DocumentGroupList({ items, empty }: DocumentGroupListProps) {
    if (items.length === 0) {
        return <p className="text-sm text-muted">{empty}</p>;
    }

    const groups = items.reduce<Record<string, DocumentGroupItem[]>>((carry, item) => {
        const key = item.group || 'Other';
        carry[key] = [...(carry[key] ?? []), item];
        return carry;
    }, {});

    return (
        <div className="space-y-6">
            {Object.entries(groups).map(([group, groupItems]) => (
                <section key={group}>
                    <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">{group}</h3>
                    <ul className="mt-3 space-y-3">
                        {groupItems.map((item) => (
                            <li key={item.id} className="rounded-xl border border-line bg-white p-5 sm:p-6">
                                {item.identity ? (
                                    <p className="text-xs font-medium uppercase tracking-wide text-muted">{item.identity}</p>
                                ) : null}
                                <p className="mt-1 text-lg font-semibold text-ink">{item.title}</p>
                                {item.meta ? <p className="mt-1 text-sm text-muted">{item.meta}</p> : null}
                                {item.actions ? (
                                    <div className="mt-3 flex flex-wrap gap-3 text-sm">{item.actions}</div>
                                ) : null}
                                {item.extra}
                            </li>
                        ))}
                    </ul>
                </section>
            ))}
        </div>
    );
}
