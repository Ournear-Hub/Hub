import { Link, usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types/page';

const linkClass = (active: boolean) =>
    `rounded-md px-3 py-1.5 text-sm font-medium ${active ? 'bg-brand text-white' : 'text-muted hover:bg-surface hover:text-ink'}`;

export default function SettingsNav() {
    const { url, props } = usePage<SharedPageProps>();
    const path = url.split('?')[0] ?? url;
    const role = props.auth.user?.role;

    const items = [
        { href: '/account/settings/account', label: 'Account', active: path === '/account/settings/account' },
        { href: '/account/profile', label: 'Profile', active: path === '/account/profile' },
        { href: '/account/settings/security', label: 'Security', active: path === '/account/settings/security' },
        ...(role === 'candidate'
            ? [{ href: '/account/settings/communication', label: 'Communication', active: path === '/account/settings/communication' }]
            : []),
    ];

    return (
        <nav className="mb-6 flex flex-wrap items-center gap-2" aria-label="Settings">
            {items.map((item) => (
                <Link key={item.href} href={item.href} className={linkClass(item.active)}>
                    {item.label}
                </Link>
            ))}
        </nav>
    );
}
