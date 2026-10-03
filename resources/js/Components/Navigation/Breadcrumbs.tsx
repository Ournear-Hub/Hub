import { Link } from '@inertiajs/react';

export type BreadcrumbItem = {
    label: string;
    href?: string;
};

type BreadcrumbsProps = {
    items: BreadcrumbItem[];
};

export default function Breadcrumbs({ items }: BreadcrumbsProps) {
    if (items.length === 0) {
        return null;
    }

    return (
        <nav aria-label="Breadcrumb" className="mb-4 text-sm">
            <ol className="flex flex-wrap items-center gap-1 text-muted">
                {items.map((item, index) => {
                    const last = index === items.length - 1;

                    return (
                        <li key={`${item.label}-${index}`} className="flex min-w-0 items-center gap-1">
                            {index > 0 ? <span aria-hidden="true">/</span> : null}
                            {item.href && !last ? (
                                <Link href={item.href} className="truncate font-medium text-brand hover:underline">
                                    {item.label}
                                </Link>
                            ) : (
                                <span className={`truncate ${last ? 'font-medium text-ink' : ''}`} aria-current={last ? 'page' : undefined}>
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
