import { router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import RecruitmentChain from '@/Components/Admin/RecruitmentChain';
import {
    Button,
    Drawer,
    Input,
    SearchBar,
    Select,
    StatusBadge,
} from '@/Components/Admin/ui';
import FilterBar from '@/Components/molecules/FilterBar';
import DataTable from '@/Components/molecules/DataTable';
import type { DataTableColumn } from '@/Components/molecules/DataTable';
import { recorded } from '@/lib/recorded';
import type { Paginator } from '@/types/jobs';
import type { AdminAlertItem } from '@/types/communication';

type Props = {
    notifications: Paginator<AdminAlertItem>;
    selected: AdminAlertItem | null;
    unreadCount: number;
    filters: {
        search: string;
        event: string;
        category: string;
        read_state: string;
        unread: string;
        recipient_id: string;
        date_from: string;
        date_to: string;
        notification: string;
    };
    events: string[];
    categories: string[];
    recipients: Array<{ id: number; name: string; email: string; label: string }>;
    can_view_recipients: boolean;
};

export default function NotificationsIndex({
    notifications,
    selected,
    unreadCount,
    filters,
    events,
    categories,
    recipients,
    can_view_recipients,
}: Props) {
    const visit = (overrides: Record<string, string | undefined> = {}, replaceAll = false) => {
        const next = replaceAll ? overrides : { ...filters, ...overrides };
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
            unread: data.read_state === 'unread' ? '1' : '',
            notification: filters.notification,
        });
    };

    const columns: Array<DataTableColumn<AdminAlertItem>> = [
        { key: 'category', header: 'Type', className: 'font-medium text-ink', render: (row) => recorded(row.category ?? row.event_label ?? row.event) },
        { key: 'title', header: 'Notification', render: (row) => row.title },
        { key: 'recipient', header: 'Recipient', render: (row) => recorded(row.recipient?.name) },
        {
            key: 'related',
            header: 'Related',
            render: (row) =>
                recorded(
                    row.related?.application?.business_id
                        ?? row.related?.submission?.business_id
                        ?? row.related?.placement?.business_id
                        ?? row.related?.invoice?.business_id
                        ?? row.related?.job?.title
                        ?? row.related?.candidate?.name,
                ),
        },
        {
            key: 'status',
            header: 'Read / unread',
            render: (row) => <StatusBadge status={row.is_unread ? 'Unread' : 'Read'} />,
        },
        { key: 'created', header: 'Created date', render: (row) => recorded(row.created_at) },
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
                        <p className="mt-1 text-sm text-ink">Unread: {unreadCount}</p>
                    </div>
                    <Button disabled={unreadCount === 0} onClick={() => router.post('/admin/notifications/read-all')}>
                        Mark all read
                    </Button>
                </div>
                <div className="mb-4 flex flex-wrap gap-2">
                    {[
                        ['all', 'All'],
                        ['unread', 'Unread'],
                        ['read', 'Read'],
                    ].map(([value, label]) => (
                        <Button
                            key={value}
                            variant={filters.read_state === value || (value === 'all' && !filters.read_state) ? 'primary' : 'secondary'}
                            onClick={() => visit({ read_state: value, unread: value === 'unread' ? '1' : '' })}
                        >
                            {label}
                        </Button>
                    ))}
                </div>
                <FilterBar onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Search notifications" />
                    <Select name="category" defaultValue={filters.category} aria-label="Notification category">
                        <option value="">All categories</option>
                        {categories.map((item) => (
                            <option key={item} value={item}>
                                {item}
                            </option>
                        ))}
                    </Select>
                    <Select name="event" defaultValue={filters.event} aria-label="Notification type">
                        <option value="">All events</option>
                        {events.map((item) => (
                            <option key={item} value={item}>
                                {item.replace(/_/g, ' ')}
                            </option>
                        ))}
                    </Select>
                    <Select name="read_state" defaultValue={filters.read_state || 'all'} aria-label="Read state">
                        <option value="all">All</option>
                        <option value="unread">Unread</option>
                        <option value="read">Read</option>
                    </Select>
                    <Select name="recipient_id" defaultValue={filters.recipient_id} aria-label="Recipient">
                        <option value="">{can_view_recipients ? 'My inbox' : 'Current recipient'}</option>
                        {recipients.map((recipient) => (
                            <option key={recipient.id} value={recipient.id}>
                                {recipient.label}
                            </option>
                        ))}
                    </Select>
                    <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="From date" />
                    <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="To date" />
                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit">Filter</Button>
                        <Button type="button" variant="secondary" onClick={() => visit({}, true)}>
                            Reset
                        </Button>
                    </div>
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
                        <dl className="grid gap-2">
                            {[
                                ['Recipient', recorded(selected.recipient?.name)],
                                ['Type', recorded(selected.event_label ?? selected.event)],
                                ['Category', recorded(selected.category)],
                                ['Title', selected.title],
                                ['Message', selected.body],
                                ['Created date', recorded(selected.created_at)],
                                ['Read date', recorded(selected.read_at)],
                            ].map(([label, value]) => (
                                <div key={label} className="grid grid-cols-3 gap-2">
                                    <dt className="text-muted">{label}</dt>
                                    <dd className="col-span-2 text-ink">{value}</dd>
                                </div>
                            ))}
                        </dl>
                        {selected.chain && selected.chain.length > 0 ? (
                            <div>
                                <p className="mb-2 text-xs uppercase tracking-wide text-muted">Related records</p>
                                <RecruitmentChain steps={selected.chain} />
                            </div>
                        ) : (
                            <p className="text-muted">No related recruitment record stored on this notification.</p>
                        )}
                        <div className="flex flex-wrap gap-2">
                            {selected.url ? (
                                <Button onClick={() => router.visit(selected.url ?? '/admin/notifications')}>
                                    Open related record
                                </Button>
                            ) : null}
                            {selected.can_toggle_read !== false && selected.is_unread ? (
                                <Button onClick={() => router.post(`/admin/notifications/${selected.id}/read`)}>
                                    Mark read
                                </Button>
                            ) : null}
                            {selected.can_toggle_read !== false && !selected.is_unread ? (
                                <Button variant="secondary" onClick={() => router.post(`/admin/notifications/${selected.id}/unread`)}>
                                    Mark unread
                                </Button>
                            ) : null}
                        </div>
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}
