import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { SharedPageProps } from '@/types/page';

type TopBarProps = {
    onMenuClick: () => void;
};

export default function TopBar({ onMenuClick }: TopBarProps) {
    const page = usePage<SharedPageProps>();
    const user = page.props.auth.user;
    const alerts = page.props.adminAlerts;
    const [menuOpen, setMenuOpen] = useState(false);
    const [alertsOpen, setAlertsOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);
    const alertsRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!menuOpen && !alertsOpen) {
            return;
        }

        const onClick = (event: MouseEvent) => {
            if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
                setMenuOpen(false);
            }
            if (alertsRef.current && !alertsRef.current.contains(event.target as Node)) {
                setAlertsOpen(false);
            }
        };
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setMenuOpen(false);
                setAlertsOpen(false);
            }
        };

        window.addEventListener('mousedown', onClick);
        window.addEventListener('keydown', onKeyDown);
        return () => {
            window.removeEventListener('mousedown', onClick);
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [menuOpen, alertsOpen]);

    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-line bg-white px-4 sm:px-6">
            <div className="flex min-w-0 items-center gap-3">
                <button
                    type="button"
                    className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-line text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 lg:hidden"
                    onClick={onMenuClick}
                >
                    <span className="sr-only">Open sidebar</span>
                    <span className="flex flex-col gap-1.5">
                        <span className="block h-0.5 w-4 bg-current" />
                        <span className="block h-0.5 w-4 bg-current" />
                        <span className="block h-0.5 w-4 bg-current" />
                    </span>
                </button>
                <p className="truncate text-sm font-medium text-ink">Admin panel</p>
            </div>

            <div className="hidden min-w-0 flex-1 px-6 md:block">
                <input
                    type="search"
                    disabled
                    placeholder="Search candidates, jobs, employers"
                    aria-label="Global search"
                    title="Global search will be available in a later V1.1 phase"
                    className="w-full max-w-xl rounded-md border border-line bg-surface px-3 py-2 text-sm text-disabled"
                />
            </div>

            <div className="flex items-center gap-2">
                {alerts ? (
                    <div className="relative" ref={alertsRef}>
                        <button
                            type="button"
                            className="relative inline-flex h-10 w-10 items-center justify-center rounded-md border border-line text-ink hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40"
                            aria-haspopup="menu"
                            aria-expanded={alertsOpen}
                            aria-label="Notifications"
                            onClick={() => setAlertsOpen((open) => !open)}
                        >
                            <span aria-hidden="true">🔔</span>
                            {alerts.unread > 0 ? (
                                <span className="absolute -right-1 -top-1 min-w-4 rounded-full bg-danger px-1 text-[10px] font-semibold leading-4 text-white">
                                    {alerts.unread > 9 ? '9+' : alerts.unread}
                                </span>
                            ) : null}
                        </button>
                        {alertsOpen ? (
                            <div role="menu" className="absolute right-0 z-30 mt-2 w-80 rounded-md border border-line bg-white p-2">
                                <div className="flex items-center justify-between px-2 py-1">
                                    <p className="text-sm font-medium text-ink">Notifications</p>
                                    {alerts.unread > 0 ? (
                                        <button
                                            type="button"
                                            className="text-xs text-brand"
                                            onClick={() => router.post('/admin/notifications/read-all')}
                                        >
                                            Mark all read
                                        </button>
                                    ) : null}
                                </div>
                                {alerts.recent.length === 0 ? (
                                    <p className="px-2 py-3 text-sm text-muted">No notifications yet.</p>
                                ) : (
                                    <ul className="max-h-80 space-y-1 overflow-y-auto">
                                        {alerts.recent.map((item) => (
                                            <li key={item.id}>
                                                <Link
                                                    href={item.url || '/admin/notifications'}
                                                    className={`block rounded-md px-2 py-2 text-sm hover:bg-surface ${item.is_unread ? 'font-medium text-ink' : 'text-muted'}`}
                                                    onClick={() => setAlertsOpen(false)}
                                                >
                                                    <span className="block">{item.title}</span>
                                                    <span className="block text-xs text-muted">{item.body}</span>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                <Link
                                    href="/admin/notifications"
                                    className="mt-1 block rounded-md px-2 py-2 text-sm text-brand hover:bg-surface"
                                    onClick={() => setAlertsOpen(false)}
                                >
                                    View all notifications
                                </Link>
                            </div>
                        ) : null}
                    </div>
                ) : null}

                <div className="relative" ref={menuRef}>
                    {user && (
                        <button
                            type="button"
                            className="flex min-h-10 min-w-0 items-center gap-2 rounded-md border border-line px-3 py-1.5 text-left hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40"
                            aria-haspopup="menu"
                            aria-expanded={menuOpen}
                            onClick={() => setMenuOpen((open) => !open)}
                        >
                            <span className="hidden min-w-0 sm:block">
                                <span className="block truncate text-sm font-medium text-ink">{user.name}</span>
                                <span className="block truncate text-xs capitalize text-muted">{user.role ?? 'admin'}</span>
                            </span>
                            <span className="text-xs text-muted sm:hidden">Account</span>
                        </button>
                    )}
                    {menuOpen && user && (
                        <div
                            role="menu"
                            className="absolute right-0 z-30 mt-2 w-56 rounded-md border border-line bg-white p-2"
                        >
                            <p className="truncate px-2 py-1 text-sm font-medium text-ink">{user.name}</p>
                            <p className="truncate px-2 pb-2 text-xs text-muted">{user.email}</p>
                            <Link
                                href="/admin/profile"
                                className="block rounded-md px-2 py-2 text-sm text-ink hover:bg-surface"
                                onClick={() => setMenuOpen(false)}
                            >
                                Profile
                            </Link>
                            <Link
                                href="/admin/logout"
                                method="post"
                                as="button"
                                className="block w-full rounded-md px-2 py-2 text-left text-sm text-ink hover:bg-surface"
                            >
                                Logout
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}
