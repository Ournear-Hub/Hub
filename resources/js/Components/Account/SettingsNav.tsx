import { Link, usePage } from '@inertiajs/react';
import type { SharedPageProps } from '@/types/page';

const linkClass = (active: boolean) =>
    `rounded-md px-3 py-2 text-sm font-medium min-h-11 inline-flex items-center ${active ? 'bg-brand text-white' : 'text-muted hover:bg-surface hover:text-ink'}`;

export default function SettingsNav() {
    const { url, props } = usePage<SharedPageProps>();
    const path = url.split('?')[0] ?? url;
    const role = props.auth.user?.role;

    const items = [
        { href: '/account/settings', label: 'Overview', active: path === '/account/settings' },
        { href: '/account/settings/account', label: 'Account', active: path === '/account/settings/account' },
        {
            href: '/account/settings/security',
            label: 'Verification',
            active: path === '/account/settings/security',
        },
        ...(role === 'candidate'
            ? [{ href: '/account/settings/communication', label: 'Communication', active: path === '/account/settings/communication' }]
            : []),
        { href: '/account/notifications', label: 'Notifications', active: path === '/account/notifications' },
        { href: '/account/profile', label: 'Profile', active: path === '/account/profile' },
    ];

    return (
        <nav className="mb-6 flex flex-wrap items-center gap-2" aria-label="Settings">
            {items.map((item) => (
                <Link key={item.href} href={item.href} className={linkClass(item.active)} aria-current={item.active ? 'page' : undefined}>
                    {item.label}
                </Link>
            ))}
        </nav>
    );
}
