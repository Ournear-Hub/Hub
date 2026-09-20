import { Link, usePage } from '@inertiajs/react';
import BrandMark from '@/Components/BrandMark';
import type { NavItem } from '@/types/layout';

const navItems: NavItem[] = [
    { label: 'Dashboard', href: '/admin' },
    { label: 'Jobs', href: '/admin/jobs' },
    { label: 'Applications', href: '/admin/applications' },
    { label: 'Employer Enquiries', href: '/admin/employer-enquiries' },
    { label: 'Website Content', href: '/admin/website-content' },
    { label: 'Media & Social', href: '/admin/media-social' },
    { label: 'Settings', href: '/admin/settings' },
];

type SidebarProps = {
    open: boolean;
    onClose: () => void;
};

function isActive(url: string, href: string): boolean {
    if (href === '/admin') {
        return url === '/admin' || url === '/admin/';
    }

    return url === href || url.startsWith(`${href}/`);
}

export default function Sidebar({ open, onClose }: SidebarProps) {
    const { url } = usePage();

    return (
        <>
            {open && (
                <button
                    type="button"
                    className="fixed inset-0 z-40 bg-ink/40 lg:hidden"
                    aria-label="Close sidebar"
                    onClick={onClose}
                />
            )}

            <aside
                className={`fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-brand text-white transition-transform lg:static lg:translate-x-0 ${
                    open ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-16 items-center border-b border-white/10 px-5">
                    <Link href="/admin" className="min-w-0" onClick={onClose}>
                        <BrandMark inverted className="text-base" />
                        <span className="mt-0.5 block text-xs text-white/70">Admin</span>
                    </Link>
                </div>

                <nav className="flex-1 space-y-1 overflow-y-auto p-3" aria-label="Admin">
                    {navItems.map((item) => {
                        const active = isActive(url, item.href);

                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={onClose}
                                className={`block rounded-md px-3 py-2 text-sm font-medium ${
                                    active
                                        ? 'bg-white/15 text-white'
                                        : 'text-white/80 hover:bg-white/10 hover:text-white'
                                }`}
                            >
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>

                <div className="border-t border-white/10 p-3">
                    <Link
                        href="/admin/logout"
                        method="post"
                        as="button"
                        onClick={onClose}
                        className="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-white/80 hover:bg-white/10 hover:text-white"
                    >
                        Logout
                    </Link>
                </div>
            </aside>
        </>
    );
}
