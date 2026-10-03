import { Link } from '@inertiajs/react';

type PaginationProps = {
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function Pagination({ links }: PaginationProps) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav className="mt-2 flex flex-wrap gap-1 lg:mt-0" aria-label="Pagination">
            {links.map((link, index) => {
                const label = link.label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»');

                if (!link.url) {
                    return (
                        <span
                            key={`${label}-${index}`}
                            className="rounded-md px-3 py-1.5 text-sm text-disabled"
                            dangerouslySetInnerHTML={{ __html: label }}
                        />
                    );
                }

                return (
                    <Link
                        key={`${label}-${index}`}
                        href={link.url}
                        className={`rounded-md px-3 py-1.5 text-sm ${
                            link.active ? 'bg-brand text-white' : 'border border-line bg-white text-ink hover:bg-surface'
                        }`}
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                );
            })}
        </nav>
    );
}
