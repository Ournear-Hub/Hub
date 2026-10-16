import { useMemo, useState } from 'react';
import OurNearIcon from '@/icons/OurNearIcon';
import { iconRegistry } from '@/icons/registry';
import type { IconCategory, IconSize } from '@/icons/types';
import { iconSizePx } from '@/icons/sizes';
import InfoPanel from '@/ui-lab/components/InfoPanel';
import LabPanel from '@/ui-lab/components/LabPanel';
import { ourNearIconCatalog } from '@/ui-lab/catalog';

const sizes: IconSize[] = ['xs', 'sm', 'md', 'lg', 'xl'];

export default function IconsSection() {
    const [query, setQuery] = useState('');
    const [category, setCategory] = useState<IconCategory | 'all'>('all');
    const [previewSize, setPreviewSize] = useState<IconSize>('md');
    const [selectedName, setSelectedName] = useState<string>(iconRegistry[0]?.name ?? 'search');

    const categories = useMemo(() => {
        const set = new Set<IconCategory>();
        for (const entry of iconRegistry) {
            if (entry.status === 'active') {
                set.add(entry.category);
            }
        }
        return [...set].sort();
    }, []);

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        return iconRegistry.filter((entry) => {
            if (entry.status !== 'active') {
                return false;
            }
            if (category !== 'all' && entry.category !== category) {
                return false;
            }
            if (!q) {
                return true;
            }
            if (entry.name.includes(q)) {
                return true;
            }
            return (entry.aliases ?? []).some((alias) => alias.includes(q));
        });
    }, [query, category]);

    const selected = iconRegistry.find((e) => e.name === selectedName) ?? null;

    return (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div className="space-y-6">
                <LabPanel
                    title="Accessibility contract (PR-4M)"
                    description="Default decorative; meaningful icons need aria-label."
                >
                    <div className="flex flex-wrap items-center gap-6">
                        <div className="flex items-center gap-2 text-sm text-ink">
                            <OurNearIcon name="search" size="md" />
                            <span>Decorative (default) beside visible label</span>
                        </div>
                        <div className="flex items-center gap-2 text-sm text-ink">
                            <OurNearIcon
                                name="info"
                                size="md"
                                decorative={false}
                                aria-label="More information"
                            />
                            <span>Meaningful standalone</span>
                        </div>
                    </div>
                    <p className="mt-3 text-sm text-muted">
                        Unknown name: dev console warning + empty{' '}
                        <code className="text-ink">data-missing-icon</code> placeholder (decorative).
                    </p>
                    <div className="mt-2 inline-flex items-center gap-2 rounded-md border border-dashed border-line px-3 py-2">
                        <OurNearIcon name="__not_in_registry__" size="sm" />
                        <span className="text-xs text-muted">__not_in_registry__</span>
                    </div>
                </LabPanel>

                <LabPanel title="Icon registry" description="OurNearIcon + governed registry — Tabler only via tablerComponents.ts.">
                    <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                        <label className="flex min-w-[200px] flex-1 flex-col gap-1 text-sm">
                            <span className="font-medium text-ink">Search</span>
                            <input
                                type="search"
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Name or alias"
                                className="rounded-md border border-line bg-white px-3 py-2 text-sm text-ink"
                            />
                        </label>
                        <label className="flex flex-col gap-1 text-sm">
                            <span className="font-medium text-ink">Category</span>
                            <select
                                value={category}
                                onChange={(e) => setCategory(e.target.value as IconCategory | 'all')}
                                className="rounded-md border border-line bg-white px-3 py-2 text-sm text-ink"
                            >
                                <option value="all">All</option>
                                {categories.map((cat) => (
                                    <option key={cat} value={cat}>
                                        {cat}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label className="flex flex-col gap-1 text-sm">
                            <span className="font-medium text-ink">Preview size</span>
                            <select
                                value={previewSize}
                                onChange={(e) => setPreviewSize(e.target.value as IconSize)}
                                className="rounded-md border border-line bg-white px-3 py-2 text-sm text-ink"
                            >
                                {sizes.map((size) => (
                                    <option key={size} value={size}>
                                        {size} ({iconSizePx[size]}px)
                                    </option>
                                ))}
                            </select>
                        </label>
                    </div>

                    <div className="mt-6 grid gap-6 lg:grid-cols-[1fr_280px]">
                        <div className="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
                            {filtered.map((entry) => (
                                <button
                                    key={entry.name}
                                    type="button"
                                    onClick={() => setSelectedName(entry.name)}
                                    className={`flex flex-col items-center gap-2 rounded-lg border p-3 text-center transition ${
                                        selectedName === entry.name
                                            ? 'border-brand bg-brand/5'
                                            : 'border-line bg-white hover:bg-surface'
                                    }`}
                                >
                                    <OurNearIcon name={entry.name} size={previewSize} />
                                    <span className="truncate text-xs font-medium text-ink">{entry.name}</span>
                                </button>
                            ))}
                        </div>

                        {selected ? (
                            <aside className="rounded-xl border border-line bg-surface/30 p-4">
                                <h4 className="text-sm font-semibold text-ink">Icon detail</h4>
                                <dl className="mt-3 space-y-2 text-sm">
                                    <div>
                                        <dt className="text-muted">Icon</dt>
                                        <dd className="font-mono text-ink">{selected.name}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted">Category</dt>
                                        <dd className="text-ink">{selected.category}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted">Aliases</dt>
                                        <dd className="font-mono text-xs text-ink">
                                            {selected.aliases?.length ? selected.aliases.join(', ') : '—'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted">Source</dt>
                                        <dd className="text-ink">{selected.source}</dd>
                                    </div>
                                </dl>
                                <div className="mt-4 flex items-center justify-center rounded-lg border border-line bg-white p-6">
                                    <OurNearIcon
                                        name={selected.name}
                                        size={previewSize}
                                        decorative={false}
                                        aria-label={selected.name}
                                    />
                                </div>
                                <p className="mt-4 text-xs font-semibold uppercase tracking-wide text-muted">Sizes</p>
                                <div className="mt-2 flex flex-wrap items-end gap-4">
                                    {sizes.map((size) => (
                                        <div key={size} className="flex flex-col items-center gap-1">
                                            <OurNearIcon name={selected.name} size={size} />
                                            <span className="text-xs text-muted">{size}</span>
                                        </div>
                                    ))}
                                </div>
                                <pre className="mt-4 overflow-x-auto rounded-md bg-ink px-3 py-2 text-xs text-white">{`<OurNearIcon name="${selected.name}" size="${previewSize}" />`}</pre>
                            </aside>
                        ) : null}
                    </div>
                </LabPanel>
            </div>

            <InfoPanel entry={ourNearIconCatalog} />
        </div>
    );
}
