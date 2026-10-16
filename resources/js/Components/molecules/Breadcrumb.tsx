import Link from '@/Components/atoms/Link';
import {
    breadcrumbCurrent,
    breadcrumbItem,
    breadcrumbLink,
    breadcrumbList,
    breadcrumbNav,
} from '@/design-system/classNames';

export type BreadcrumbItem = {
    label: string;
    href?: string;
};

export type BreadcrumbProps = {
    items: BreadcrumbItem[];
    /** Accessible name for the landmark nav (V2 default: "Breadcrumb"). */
    ariaLabel?: string;
    className?: string;
};

export default function Breadcrumb({ items, ariaLabel = 'Breadcrumb', className = '' }: BreadcrumbProps) {
    if (items.length === 0) {
        return null;
    }

    const navClass = `${breadcrumbNav} ${className}`.trim();

    return (
        <nav aria-label={ariaLabel} className={navClass}>
            <ol className={breadcrumbList}>
                {items.map((item, index) => {
                    const last = index === items.length - 1;

                    return (
                        <li key={`${item.label}-${index}`} className={breadcrumbItem}>
                            {index > 0 ? <span aria-hidden="true">/</span> : null}
                            {item.href && !last ? (
                                <Link href={item.href} underline="hover" className={breadcrumbLink}>
                                    {item.label}
                                </Link>
                            ) : (
                                <span className={last ? breadcrumbCurrent : 'truncate'} aria-current={last ? 'page' : undefined}>
                                    {item.label}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
