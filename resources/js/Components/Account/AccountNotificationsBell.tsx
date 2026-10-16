import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import IconButton from '@/Components/UI/IconButton';
import { BellIcon } from '@/Components/UI/icons';
import type { SharedPageProps } from '@/types/page';

export default function AccountNotificationsBell() {
    const page = usePage<SharedPageProps>();
    const alerts = page.props.accountAlerts;
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onClick = (event: MouseEvent) => {
            if (ref.current && !ref.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        window.addEventListener('mousedown', onClick);
        window.addEventListener('keydown', onKeyDown);

        return () => {
            window.removeEventListener('mousedown', onClick);
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    if (!alerts) {
        return null;
    }

    const recent = alerts.recent ?? [];

    return (
        <div className="relative" ref={ref}>
            <IconButton label="Notifications" aria-expanded={open} onClick={() => setOpen((value) => !value)}>
                <BellIcon />
            </IconButton>
            {alerts.unread > 0 ? (
                <span className="pointer-events-none absolute right-1 top-1 h-2 w-2 rounded-full bg-accent" aria-hidden="true" />
            ) : null}
            {open ? (
                <div
                    role="menu"
                    aria-label="Notifications"
                    className="absolute right-0 z-50 mt-2 w-[min(20rem,calc(100vw-2rem))] rounded-xl border border-line bg-white p-2 shadow-md"
                >
                    <div className="flex items-center justify-between px-2 py-1">
                        <p className="text-sm font-semibold text-ink">Notifications</p>
                        {alerts.unread > 0 ? (
                            <button
                                type="button"
                                className="text-xs font-semibold text-brand hover:underline"
                                onClick={() => {
                                    router.post('/account/notifications/read-all', {}, { preserveScroll: true });
                                }}
                            >
                                Mark all read
                            </button>
                        ) : null}
                    </div>
                    {recent.length === 0 ? (
                        <p className="px-2 py-3 text-sm text-muted">No notifications yet.</p>
                    ) : (
                        <ul className="max-h-80 space-y-1 overflow-y-auto">
                            {recent.map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={item.url || '/account'}
                                        role="menuitem"
                                        className={`block rounded-md px-2 py-2 text-sm hover:bg-surface ${item.is_unread ? 'font-medium text-ink' : 'text-muted'}`}
                                        onClick={() => {
                                            setOpen(false);
                                            if (item.is_unread) {
                                                router.post(`/account/notifications/${item.id}/read`, {}, { preserveScroll: true });
                                            }
                                        }}
                                    >
                                        <span className="block">{item.title}</span>
                                        {item.body ? <span className="mt-0.5 block text-xs text-muted">{item.body}</span> : null}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            ) : null}
        </div>
    );
}
