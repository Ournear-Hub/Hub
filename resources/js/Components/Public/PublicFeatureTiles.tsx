import OurNearIcon from '@/icons/OurNearIcon';

export type PublicFeatureTile = {
    title: string;
    body: string;
    icon: string;
};

type Props = {
    items: PublicFeatureTile[];
    className?: string;
};

export default function PublicFeatureTiles({ items, className = '' }: Props) {
    return (
        <div className={`grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-4 ${className}`}>
            {items.map((item) => (
                <article key={item.title} className="rounded-xl border border-line bg-white p-4">
                    <span className="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-brand/10 text-brand">
                        <OurNearIcon name={item.icon} size="sm" />
                    </span>
                    <h3 className="mt-2 text-sm font-semibold text-ink">{item.title}</h3>
                    <p className="mt-1 text-xs leading-relaxed text-muted">{item.body}</p>
                </article>
            ))}
        </div>
    );
}
