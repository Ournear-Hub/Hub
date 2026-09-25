import { router, usePage } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import Pagination from '@/Components/Pagination';
import type { AccountNotification } from '@/types/account';
import type { Paginator } from '@/types/jobs';
import type { SharedPageProps } from '@/types/page';

type NotificationsProps = {
    notifications: Paginator<AccountNotification>;
    unread_count: number;
};

export default function AccountNotifications({ notifications, unread_count }: NotificationsProps) {
    const flash = usePage<SharedPageProps>().props.flash;

    const markRead = (notification: AccountNotification, redirect = false) => {
        router.post(`/account/notifications/${notification.id}/read`, redirect ? { redirect: 1 } : {}, {
            preserveScroll: !redirect,
        });
    };

    return (
        <AccountLayout title="Notifications" description="Updates about your applications.">
                    {flash.success ? <p className="mb-4 text-sm text-success">{flash.success}</p> : null}
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <p className="text-sm text-muted">
                            {unread_count > 0 ? `${unread_count} unread` : 'All caught up'}
                        </p>
                        {unread_count > 0 ? (
                            <button
                                type="button"
                                className="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface"
                                onClick={() => router.post('/account/notifications/read-all')}
                            >
                                Mark all read
                            </button>
                        ) : null}
                    </div>
                    {notifications.data.length === 0 ? (
                        <p className="text-sm text-muted">No notifications yet.</p>
                    ) : (
                        <>
                            <ul className="space-y-3">
                                {notifications.data.map((notification) => (
                                    <li
                                        key={notification.id}
                                        className={`rounded-xl border bg-white p-5 ${notification.is_unread ? 'border-brand' : 'border-line'}`}
                                    >
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">
                                            {notification.event_label}
                                        </p>
                                        <h2 className="mt-1 text-lg font-semibold text-ink">{notification.title}</h2>
                                        <p className="mt-1 text-sm text-muted">{notification.body}</p>
                                        {notification.created_at ? (
                                            <p className="mt-2 text-xs text-muted">{notification.created_at}</p>
                                        ) : null}
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                className="text-sm font-medium text-brand"
                                                onClick={() => markRead(notification, true)}
                                            >
                                                View application
                                            </button>
                                            {notification.is_unread ? (
                                                <button
                                                    type="button"
                                                    className="text-sm font-medium text-ink"
                                                    onClick={() => markRead(notification)}
                                                >
                                                    Mark read
                                                </button>
                                            ) : (
                                                <span className="text-sm text-muted">Read</span>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                            <Pagination links={notifications.links} />
                        </>
                    )}
        </AccountLayout>
    );
}
