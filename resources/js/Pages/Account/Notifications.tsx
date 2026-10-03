import { router, usePage } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import StatusBadge from '@/Components/Account/StatusBadge';
import EmptyState from '@/Components/UI/EmptyState';
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
    const role = usePage<SharedPageProps>().props.auth.user?.role;
    const hiringUser = role === 'employer' || role === 'candidate';
    const emptyDescription =
        role === 'employer'
            ? 'Hiring and account updates from OurNear will appear here when they are sent to your account.'
            : hiringUser
              ? 'Application and hiring updates will appear here when they are sent to your account.'
              : 'When you apply to jobs or receive account updates, notifications will appear here.';

    const markRead = (notification: AccountNotification, redirect = false) => {
        router.post(`/account/notifications/${notification.id}/read`, redirect ? { redirect: 1 } : {}, {
            preserveScroll: !redirect,
        });
    };

    return (
        <AccountLayout
            title="Notifications"
            description={
                role === 'employer'
                    ? 'Hiring and account updates for your company.'
                    : 'Updates about your applications and account activity.'
            }
            breadcrumbs={
                role === 'employer'
                    ? [
                          { label: 'Hiring', href: '/account/hiring' },
                          { label: 'Notifications' },
                      ]
                    : [
                          { label: 'Account', href: '/account' },
                          { label: 'Settings', href: '/account/settings' },
                          { label: 'Notifications' },
                      ]
            }
        >
            {flash.success ? (
                <p className="mb-4 text-sm text-success" role="status">
                    {flash.success}
                </p>
            ) : null}

            <div className="mx-auto max-w-3xl space-y-4 lg:max-w-none">
                <header className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-ink">Notification center</h1>
                        <p className="mt-1 text-sm text-muted">
                            {unread_count > 0 ? `${unread_count} unread notification${unread_count === 1 ? '' : 's'}` : "You're all caught up"}
                        </p>
                    </div>
                    {unread_count > 0 ? (
                        <button
                            type="button"
                            className="inline-flex min-h-11 items-center rounded-md border border-line px-3 py-2 text-sm font-semibold text-ink hover:bg-surface"
                            onClick={() => router.post('/account/notifications/read-all')}
                        >
                            Mark all read
                        </button>
                    ) : null}
                </header>

                {notifications.data.length === 0 ? (
                    <EmptyState
                        title="You're all caught up"
                        description={emptyDescription}
                    />
                ) : (
                    <>
                        <ul className="space-y-3">
                            {notifications.data.map((notification) => (
                                <li
                                    key={notification.id}
                                    className={`rounded-lg border bg-white p-3 sm:p-4 ${notification.is_unread ? 'border-brand/40' : 'border-line'}`}
                                >
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="text-[11px] font-semibold uppercase tracking-wide text-muted">
                                            {notification.event_label}
                                        </p>
                                        {notification.is_unread ? <StatusBadge status="Unread" /> : null}
                                    </div>
                                    <h2 className="mt-1 text-sm font-semibold text-ink">{notification.title}</h2>
                                    <p className="mt-1 line-clamp-2 text-sm text-muted">{notification.body}</p>
                                    {notification.created_at ? (
                                        <p className="mt-2 text-xs text-muted">{notification.created_at}</p>
                                    ) : null}
                                    <div className="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                                        <button
                                            type="button"
                                            className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                                            onClick={() => markRead(notification, true)}
                                        >
                                            Open related page
                                        </button>
                                        {notification.is_unread ? (
                                            <button
                                                type="button"
                                                className="inline-flex min-h-11 items-center text-sm font-semibold text-ink hover:underline"
                                                onClick={() => markRead(notification)}
                                            >
                                                Mark read
                                            </button>
                                        ) : null}
                                    </div>
                                </li>
                            ))}
                        </ul>
                        <Pagination links={notifications.links} />
                    </>
                )}
            </div>
        </AccountLayout>
    );
}
