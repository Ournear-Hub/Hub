import Link from '@/Components/atoms/Link';
import {
    paginationItemBase,
    paginationItemCurrent,
    paginationItemDisabled,
    paginationItemLink,
    paginationNav,
} from '@/design-system/classNames';

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginationProps = {
    /** Caller-owned link model (e.g. Laravel paginator `links` array). */
    links: PaginationLink[];
    /** Accessible name for the landmark nav (V2 default: "Pagination"). */
    ariaLabel?: string;
    className?: string;
};

function normalizeLabel(label: string): string {
    return label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»');
}

function itemAriaLabel(normalized: string): string | undefined {
    if (normalized === '«' || normalized === '‹') {
        return 'Previous page';
    }
    if (normalized === '»' || normalized === '›') {
        return 'Next page';
    }

    return undefined;
}

export default function Pagination({ links, ariaLabel = 'Pagination', className = '' }: PaginationProps) {
    if (links.length <= 3) {
        return null;
    }

    const navClass = `${paginationNav} ${className}`.trim();

    return (
        <nav aria-label={ariaLabel} className={navClass}>
            {links.map((link, index) => {
                const label = normalizeLabel(link.label);
                const key = `${label}-${index}`;
                const supplementalLabel = itemAriaLabel(label);

                if (link.active) {
                    return (
                        <span key={key} className={`${paginationItemBase} ${paginationItemCurrent}`} aria-current="page">
                            {label}
                        </span>
                    );
                }

                if (!link.url) {
                    return (
                        <span
                            key={key}
                            className={`${paginationItemBase} ${paginationItemDisabled}`}
                            aria-hidden={label === '...' ? true : undefined}
                        >
                            {label}
                        </span>
                    );
                }

                return (
                    <Link
                        key={key}
                        href={link.url}
                        underline="none"
                        className={`${paginationItemBase} ${paginationItemLink}`}
                        aria-label={supplementalLabel}
                    >
                        {label}
                    </Link>
                );
            })}
        </nav>
    );
}
