import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import { Button, DataTable, FilterBar, SearchBar, Select } from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { Paginator } from '@/types/jobs';
import type { UserActivityItem } from '@/types/security';

type Props = {
    activities: Paginator<UserActivityItem>;
    filters: {
        search: string;
        type: string;
    };
    options: {
        types: Array<{ value: string; label: string }>;
    };
};

export default function UsersActivity({ activities, filters, options }: Props) {
    const visit = (overrides: Record<string, string | undefined> = {}, replaceAll = false) => {
        const next = replaceAll ? overrides : { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/users/activity', params, { preserveState: true, preserveScroll: true });
    };

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<string, string>;
        visit(data);
    };

    const columns: Array<DataTableColumn<UserActivityItem>> = [
        { key: 'created', header: 'When', render: (row) => row.created_at || '—' },
        { key: 'user', header: 'User', className: 'font-medium text-ink', render: (row) => row.user_name || '—' },
        { key: 'type', header: 'Activity', render: (row) => row.type_label },
        { key: 'description', header: 'Detail', render: (row) => row.description },
        { key: 'actor', header: 'By', render: (row) => row.actor_name || 'System' },
    ];

    return (
        <AdminLayout title="User Activity">
            <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">User Activity</h1>
                    <p className="mt-1 text-sm text-muted">
                        Meaningful platform events only. This is separate from security audit logs.
                    </p>
                </div>
                <Link href="/admin/users">
                    <Button variant="secondary">All Users</Button>
                </Link>
            </div>

            <FilterBar onSubmit={filter}>
                <SearchBar defaultValue={filters.search} placeholder="Search user, OurNear ID, or detail" />
                <Select name="type" defaultValue={filters.type} aria-label="Activity type">
                    <option value="">All activity</option>
                    {options.types.map((item) => (
                        <option key={item.value} value={item.value}>
                            {item.label}
                        </option>
                    ))}
                </Select>
                <div className="flex flex-wrap gap-2">
                    <Button type="submit">Apply filters</Button>
                    <Button type="button" variant="secondary" onClick={() => visit({}, true)}>
                        Reset filters
                    </Button>
                </div>
            </FilterBar>

            <DataTable
                columns={columns}
                rows={activities.data}
                rowKey={(row) => row.id}
                emptyTitle="No user activity matches these filters"
                emptyDescription="Registration, verification, logins, applications, and security events are recorded here."
                onRowClick={(row) => router.get(`/admin/users/${row.user_id}`)}
            />
            <Pagination links={activities.links} />
        </AdminLayout>
    );
}
