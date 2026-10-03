import { Link, router, usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types/page';

const linkClass = (active: boolean) =>
    `block rounded-md px-3 py-2.5 text-sm font-medium lg:py-2 ${active ? 'bg-brand text-white' : 'text-muted hover:bg-surface hover:text-ink'}`;

type AccountNavProps = {
    onNavigate?: () => void;
};

export default function AccountNav({ onNavigate }: AccountNavProps) {
    const { url, props } = usePage<SharedPageProps>();
    const role = props.auth.user?.role;
    const path = url.split('?')[0] ?? url;
    const candidateItems = props.candidateNav ?? [];
    const employerItems = props.employerNav ?? [];

    const matchHref = (href: string, value: string) => {
        if (href.includes('#')) {
            return false;
        }
        const [path, query] = href.split('?');
        if (query) {
            const expected = new URLSearchParams(query);
            const actual = new URLSearchParams(value.includes('?') ? value.split('?')[1] : '');
            if (value.split('?')[0] !== path && !value.startsWith(`${path}/`)) {
                return false;
            }
            for (const [key, expectedValue] of expected.entries()) {
                if (actual.get(key) !== expectedValue) {
                    return false;
                }
            }

            return value.startsWith(path);
        }
        if (href === '/account') {
            return value === '/account' || value === '/account/';
        }
        if (href === '/account/hiring') {
            return value === '/account/hiring';
        }

        return value === href || value.startsWith(`${href}/`);
    };

    const items =
        role === 'candidate'
            ? candidateItems.map((item) => ({
                  ...item,
                  group: item.group,
                  match: (value: string) => matchHref(item.href, value),
              }))
            : employerItems.length > 0
              ? employerItems.map((item) => ({
                    ...item,
                    group: item.group,
                    match: (value: string) => matchHref(item.href, value),
                }))
              : [
                    { href: '/account', label: 'Dashboard', match: (value: string) => value === '/account' || value === '/account/' },
                    { href: '/account/profile', label: 'Profile', match: (value: string) => value === '/account/profile' },
                    { href: '/account/settings', label: 'Settings', match: (value: string) => value.startsWith('/account/settings') && !value.startsWith('/account/settings/security') },
                    { href: '/account/settings/security', label: 'Verification', match: (value: string) => value.startsWith('/account/settings/security') },
                ];

    return (
        <nav className="rounded-xl border border-line bg-white p-2" aria-label="Account">
            {items.map((item, index) => {
                const group = 'group' in item ? item.group : undefined;
                const previousItem = items[index - 1];
                const previous = previousItem !== undefined && 'group' in previousItem ? previousItem.group : undefined;

                return (
                    <div key={item.href}>
                        {group && group !== previous ? (
                            <p className="px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wide text-muted">{group}</p>
                        ) : null}
                        <Link href={item.href} className={linkClass(item.match(path))} onClick={onNavigate}>
                            {item.label}
                        </Link>
                    </div>
                );
            })}
            <button
                type="button"
                className="mt-1 w-full rounded-md px-3 py-2.5 text-left text-sm font-medium text-muted hover:bg-surface hover:text-ink lg:py-2"
                onClick={() => {
                    onNavigate?.();
                    router.post('/logout');
                }}
            >
                Log out
            </button>
        </nav>
    );
}
