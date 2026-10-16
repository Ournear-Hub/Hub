import type { ComponentCatalogEntry } from '@/ui-lab/catalog';

type Props = {
    entry: ComponentCatalogEntry;
};

function ListBlock({ title, items }: { title: string; items: string[] }) {
    return (
        <div>
            <h4 className="text-xs font-semibold uppercase tracking-wide text-muted">{title}</h4>
            <ul className="mt-2 space-y-1 text-sm text-ink">
                {items.map((item) => (
                    <li key={item}>{item}</li>
                ))}
            </ul>
        </div>
    );
}

export default function InfoPanel({ entry }: Props) {
    return (
        <aside className="rounded-xl border border-line bg-white p-5 shadow-sm lg:sticky lg:top-6">
            <p className="text-xs font-semibold uppercase tracking-wide text-brand">Component information</p>
            <h3 className="mt-1 text-lg font-semibold text-ink">{entry.name}</h3>
            <dl className="mt-4 space-y-3 text-sm">
                <div>
                    <dt className="text-muted">Category</dt>
                    <dd className="font-medium text-ink">{entry.category}</dd>
                </div>
                <div>
                    <dt className="text-muted">Status</dt>
                    <dd className="font-medium capitalize text-ink">{entry.status.replace('-', ' ')}</dd>
                </div>
                <div>
                    <dt className="text-muted">Source</dt>
                    <dd className="font-mono text-xs text-ink">{entry.source}</dd>
                </div>
            </dl>
            <div className="mt-5 space-y-4">
                <ListBlock title="Variants" items={entry.variants} />
                <ListBlock title="Sizes" items={entry.sizes} />
                <ListBlock title="Dependencies" items={entry.dependencies} />
                <ListBlock title="Accessibility" items={entry.accessibility} />
                <ListBlock title="Compatibility" items={entry.compatibility} />
            </div>
        </aside>
    );
}
