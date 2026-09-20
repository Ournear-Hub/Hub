import { router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import { Button, DataTable, Drawer, FilterBar, Input, SearchBar, Select, StatusBadge } from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { Paginator } from '@/types/jobs';
import type { LoginLogItem } from '@/types/security';

type Props = {
    logs: Paginator<LoginLogItem>;
    selected: LoginLogItem | null;
    filters: {
        search: string;
        event: string;
        outcome: string;
        date_from: string;
        date_to: string;
        log: string;
    };
    events: Array<{ value: string; label: string }>;
};

export default function LoginLogsIndex({ logs, selected, filters, events }: Props) {
    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/login-logs', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ log: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<string, string>;
        visit({ ...data, log: filters.log });
    };

    const columns: Array<DataTableColumn<LoginLogItem>> = [
        { key: 'created', header: 'When', render: (row) => row.created_at ?? '—' },
        { key: 'email', header: 'Email', className: 'font-medium text-ink', render: (row) => row.email },
        { key: 'event', header: 'Event', render: (row) => row.event_label },
        {
            key: 'result',
            header: 'Result',
            render: (row) => <StatusBadge status={row.succeeded ? 'Success' : 'Failure'} />,
        },
        { key: 'ip', header: 'IP', render: (row) => row.ip_address ?? '—' },
    ];

    return (
        <AdminLayout title="Login Logs">
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight text-ink">Login Logs</h1>
                <p className="mt-1 text-sm text-muted">
                    Authentication events only. Passwords, hashes, and reset tokens are never stored here.
                </p>
            </div>

            <FilterBar onSubmit={filter}>
                <SearchBar defaultValue={filters.search} placeholder="Search email, name, or IP" />
                <Select name="event" defaultValue={filters.event} aria-label="Event">
                    <option value="">All events</option>
                    {events.map((item) => (
                        <option key={item.value} value={item.value}>
                            {item.label}
                        </option>
                    ))}
                </Select>
                <Select name="outcome" defaultValue={filters.outcome} aria-label="Outcome">
                    <option value="">All results</option>
                    <option value="success">Success</option>
                    <option value="failure">Failure</option>
                </Select>
                <Input type="date" name="date_from" defaultValue={filters.date_from} aria-label="From date" />
                <Input type="date" name="date_to" defaultValue={filters.date_to} aria-label="To date" />
                <Button type="submit">Filter</Button>
            </FilterBar>

            <DataTable
                columns={columns}
                rows={logs.data}
                rowKey={(row) => row.id}
                emptyTitle="No login events match these filters"
                onRowClick={(row) => visit({ log: String(row.id) })}
                selectedKey={selected?.id ?? null}
            />
            <Pagination links={logs.links} />

            <Drawer open={selected !== null} title={selected?.event_label ?? 'Login event'} onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-3 text-sm">
                        <p>
                            <span className="text-muted">Email:</span> {selected.email}
                        </p>
                        <p>
                            <span className="text-muted">User:</span> {selected.user_name ?? '—'}
                        </p>
                        <p>
                            <span className="text-muted">When:</span> {selected.created_at ?? '—'}
                        </p>
                        <p>
                            <span className="text-muted">IP:</span> {selected.ip_address ?? '—'}
                        </p>
                        <p className="break-all">
                            <span className="text-muted">User agent:</span> {selected.user_agent ?? '—'}
                        </p>
                        <StatusBadge status={selected.succeeded ? 'Success' : 'Failure'} />
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}
