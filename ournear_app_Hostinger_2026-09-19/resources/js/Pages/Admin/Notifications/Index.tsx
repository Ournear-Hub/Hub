import { router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    Button,
    DataTable,
    Drawer,
    FilterBar,
    SearchBar,
    Select,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { Paginator } from '@/types/jobs';
import type { AdminAlertItem } from '@/types/communication';

type Props = {
    notifications: Paginator<AdminAlertItem>;
    selected: AdminAlertItem | null;
    unreadCount: number;
    filters: {
        search: string;
        event: string;
        unread: string;
        notification: string;
    };
    events: string[];
};

export default function NotificationsIndex({ notifications, selected, unreadCount, filters, events }: Props) {
    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/notifications', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ notification: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<string, string>;
        visit({
            ...data,
            unread: data.unread ? '1' : '',
            notification: filters.notification,
        });
    };

    const columns: Array<DataTableColumn<AdminAlertItem>> = [
        { key: 'title', header: 'Notification', className: 'font-medium text-ink', render: (row) => row.title },
        { key: 'body', header: 'Detail', render: (row) => row.body },
        { key: 'status', header: 'Status', render: (row) => (row.is_unread ? 'Unread' : 'Read') },
        { key: 'created', header: 'When', render: (row) => row.created_at ?? '—' },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button variant="ghost" onClick={() => visit({ notification: row.id })}>
                    View
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout title="Notifications">
            <div
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-ink">Notifications</h1>
                        <p className="mt-1 text-sm text-muted">
                            System alerts for recruitment and finance events. Reading a notification does not change the
                            underlying record.
                        </p>
                    </div>
                    <Button disabled={unreadCount === 0} onClick={() => router.post('/admin/notifications/read-all')}>
                        Mark all read
                    </Button>
                </div>
                <FilterBar onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Search notifications" />
                    <Select name="event" defaultValue={filters.event} aria-label="Event">
                        <option value="">All events</option>
                        {events.map((item) => (
                            <option key={item} value={item}>
                                {item.replace(/_/g, ' ')}
                            </option>
                        ))}
                    </Select>
                    <label className="flex items-center gap-2 text-sm text-ink">
                        <input type="checkbox" name="unread" value="1" defaultChecked={filters.unread === '1'} />
                        Unread only
                    </label>
                    <Button type="submit">Filter</Button>
                </FilterBar>
                <DataTable
                    columns={columns}
                    rows={notifications.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No notifications match these filters"
                    emptyDescription="Lifecycle events create notifications for Admin users. Candidate and employer portal alerts are later phases."
                    onRowClick={(row) => visit({ notification: row.id })}
                    selectedKey={selected?.id ?? null}
                />
                <Pagination links={notifications.links} />
            </div>

            <Drawer open={selected !== null} title={selected?.title ?? 'Notification'} onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-4 text-sm">
                        <p>{selected.body}</p>
                        <p className="text-muted">{selected.created_at}</p>
                        <p className="text-muted">{selected.is_unread ? 'Unread' : 'Read'}</p>
                        <div className="flex flex-wrap gap-2">
                            {selected.url ? (
                                <Button onClick={() => router.visit(selected.url ?? '/admin/notifications')}>
                                    Open related record
                                </Button>
                            ) : null}
                            {selected.is_unread ? (
                                <Button onClick={() => router.post(`/admin/notifications/${selected.id}/read`)}>
                                    Mark read
                                </Button>
                            ) : (
                                <Button variant="secondary" onClick={() => router.post(`/admin/notifications/${selected.id}/unread`)}>
                                    Mark unread
                                </Button>
                            )}
                        </div>
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}
