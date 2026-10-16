import { Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { focusRing } from '@/design-system/classNames';

export type PopularSearchIndustry = {
    id: number;
    name: string;
};

export type PopularSearchDesignation = {
    id: number;
    label: string;
    industry_id: number | null;
    href: string;
};

export type PopularSearchDiscovery = {
    industries: PopularSearchIndustry[];
    designations: PopularSearchDesignation[];
};

type Props = {
    discovery: PopularSearchDiscovery;
    onSelect?: (item: PopularSearchDesignation) => void;
};

export default function HomePopularSearches({ discovery, onSelect }: Props) {
    const [industryId, setIndustryId] = useState<string>('');

    const filtered = useMemo(() => {
        if (!industryId) {
            return discovery.designations.slice(0, 8);
        }

        const id = Number(industryId);

        return discovery.designations.filter((item) => item.industry_id === id).slice(0, 8);
    }, [discovery.designations, industryId]);

    if (discovery.designations.length === 0) {
        return null;
    }

    return (
        <div className="mt-4 space-y-3">
            <div className="flex flex-wrap items-center gap-3">
                <span className="text-xs font-medium text-muted">Popular searches</span>
                {discovery.industries.length > 0 ? (
                    <label className="inline-flex items-center gap-2 text-xs text-muted">
                        <span className="sr-only">Filter by industry</span>
                        <select
                            value={industryId}
                            onChange={(event) => setIndustryId(event.target.value)}
                            className={`rounded-md border border-line bg-white px-2 py-1 text-xs text-ink ${focusRing}`}
                        >
                            <option value="">All industries</option>
                            {discovery.industries.map((industry) => (
                                <option key={industry.id} value={String(industry.id)}>
                                    {industry.name}
                                </option>
                            ))}
                        </select>
                    </label>
                ) : null}
            </div>
            <div className="flex flex-wrap gap-2">
                {filtered.map((item) => (
                    <Link
                        key={`${item.id}-${item.label}`}
                        href={item.href}
                        className={`rounded-full border border-line bg-white px-3 py-1.5 text-xs font-medium text-ink transition hover:border-accent hover:text-brand ${focusRing}`}
                        onClick={() => onSelect?.(item)}
                    >
                        {item.label}
                    </Link>
                ))}
            </div>
        </div>
    );
}
