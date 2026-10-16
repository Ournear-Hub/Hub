import { Link, usePage } from '@inertiajs/react';
import { focusRing } from '@/design-system/classNames';
import type { SharedPageProps } from '@/types/page';

const linkClass = (active: boolean) =>
    `rounded-md px-3 py-2 text-sm font-medium min-h-11 inline-flex items-center ${focusRing} ${active ? 'bg-brand text-white' : 'text-muted hover:bg-surface hover:text-ink'}`;

const tabs = [
    { href: '/account/settings/account', label: 'Account' },
    { href: '/account/settings/security', label: 'Security' },
    { href: '/account/settings/verification', label: 'Verification' },
    { href: '/account/settings/password', label: 'Change Password' },
] as const;

export default function SettingsNav() {
    const { url } = usePage<SharedPageProps>();
    const path = url.split('?')[0] ?? url;
    const activePath =
        path === '/account/settings' || path === '/account/settings/'
            ? '/account/settings/account'
            : path.startsWith('/account/settings/communication')
              ? '/account/settings/account'
              : path;

    return (
        <nav className="mb-6 flex flex-wrap items-center gap-2" aria-label="Settings sections">
            {tabs.map((item) => (
                <Link
                    key={item.href}
                    href={item.href}
                    className={linkClass(activePath === item.href)}
                    aria-current={activePath === item.href ? 'page' : undefined}
                >
                    {item.label}
                </Link>
            ))}
        </nav>
    );
}
