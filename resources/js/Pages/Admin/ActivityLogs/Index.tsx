import { router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import { Button, DataTable, Drawer, FilterBar, Input, SearchBar, Select } from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { Paginator } from '@/types/jobs';
import type { ActivityLogItem } from '@/types/security';

type Props = {
    activities: Paginator<ActivityLogItem>;
    selected: ActivityLogItem | null;
    filters: {
        search: string;
        action: string;
        date_from: string;
        date_to: string;
        activity: string;
    };
    actions: Array<{ value: string; label: string }>;
};

export default function ActivityLogsIndex({ activities, selected, filters, actions }: Props) {
    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/activity-logs', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ activity: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<string, string>;
        visit({ ...data, activity: filters.activity });
    };

    const columns: Array<DataTableColumn<ActivityLogItem>> = [
        { key: 'created', header: 'When', render: (row) => row.created_at ?? '—' },
        { key: 'action', header: 'Action', className: 'font-medium text-ink', render: (row) => row.action_label },
        { key: 'actor', header: 'Actor', render: (row) => row.actor_name ?? '—' },
        { key: 'description', header: 'Detail', render: (row) => row.description },
    ];

    return (
        <AdminLayout title="Activity Logs">
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight text-ink">Activity Logs</h1>
                <p className="mt-1 text-sm text-muted">
                    Security and administrative actions. This is separate from recruitment activity history.
                </p>
            </div>

            <FilterBar onSubmit={filter}>
                <SearchBar defaultValue={filters.search} placeholder="Search description or actor" />
                <Select name="action" defaultValue={filters.action} aria-label="Action">
                    <option value="">All actions</option>
                    {actions.map((item) => (
                        <option key={item.value} value={item.value}>
                            {item.label}
                        </option>
                    ))}
                </Select>
                <Input type="date" name="date_from" defaultValue={filters.date_from} aria-label="From date" />
                <Input type="date" name="date_to" defaultValue={filters.date_to} aria-label="To date" />
                <Button type="submit">Filter</Button>
            </FilterBar>

            <DataTable
                columns={columns}
                rows={activities.data}
                rowKey={(row) => row.id}
                emptyTitle="No administrative activity yet"
                emptyDescription="User, role, and sensitive finance changes are recorded here."
                onRowClick={(row) => visit({ activity: String(row.id) })}
                selectedKey={selected?.id ?? null}
            />
            <Pagination links={activities.links} />

            <Drawer open={selected !== null} title={selected?.action_label ?? 'Activity'} onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-3 text-sm">
                        <p>{selected.description}</p>
                        <p className="text-muted">Actor: {selected.actor_name ?? '—'}</p>
                        <p className="text-muted">When: {selected.created_at ?? '—'}</p>
                        <p className="text-muted">IP: {selected.ip_address ?? '—'}</p>
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}
