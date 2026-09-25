import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import { Button, DataTable, FilterBar, Input, SearchBar, Select, StatusBadge } from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { Paginator } from '@/types/jobs';
import type { StaffRoleOption, UserListItem, UserSegmentOption } from '@/types/security';

type Props = {
    users: Paginator<UserListItem>;
    filters: {
        search: string;
        account_type: string;
        status: string;
        verification: string;
        location: string;
        language: string;
        registered_from: string;
        registered_to: string;
        last_login_from: string;
        last_login_to: string;
        completion_min: string;
        brand_id: string;
        role_id: string;
        segment: string;
        user: string;
    };
    options: {
        account_types: Array<{ value: string; label: string }>;
        roles: StaffRoleOption[];
        statuses: Array<{ value: string; label: string }>;
        languages: Array<{ value: string; label: string }>;
        brands: Array<{ id: number; name: string }>;
        segments: UserSegmentOption[];
        verifications: Array<{ value: string; label: string }>;
    };
    can_manage: boolean;
};

function accountLabel(type: string): string {
    return type.charAt(0).toUpperCase() + type.slice(1);
}

export default function UsersIndex({ users, filters, options, can_manage }: Props) {
    const visit = (overrides: Record<string, string | undefined> = {}, replaceAll = false) => {
        const next = replaceAll ? overrides : { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/users', params, { preserveState: true, preserveScroll: true });
    };

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<string, string>;
        visit({ ...data, segment: filters.segment, user: '' });
    };

    const navigation = options.segments.filter((segment) => segment.is_navigation);

    const columns: Array<DataTableColumn<UserListItem>> = [
        {
            key: 'identity',
            header: 'User',
            className: 'font-medium text-ink',
            render: (row) => (
                <div>
                    <p>{row.name}</p>
                    <p className="text-xs font-normal text-muted">{row.business_id || '—'}</p>
                </div>
            ),
        },
        { key: 'username', header: 'Username', render: (row) => row.username || '—' },
        { key: 'email', header: 'Email', render: (row) => row.email },
        { key: 'mobile', header: 'Mobile', render: (row) => row.mobile || '—' },
        {
            key: 'account_type',
            header: 'Type',
            render: (row) => <StatusBadge status={accountLabel(row.account_type)} />,
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => <StatusBadge status={row.account_status_label || (row.is_active ? 'Active' : 'Inactive')} />,
        },
        {
            key: 'verified',
            header: 'Verified',
            render: (row) => <StatusBadge status={row.email_verified ? 'Verified' : 'Unverified'} />,
        },
        {
            key: 'completion',
            header: 'Profile',
            render: (row) => `${row.profile_completion ?? 0}%`,
        },
        {
            key: 'last_login_at',
            header: 'Last login',
            render: (row) => row.last_login_at || 'Never',
        },
    ];

    return (
        <AdminLayout title="Users">
            <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Users</h1>
                    <p className="mt-1 text-sm text-muted">
                        One platform identity per person. Candidate and employer records stay attached as profiles.
                    </p>
                </div>
                {can_manage ? (
                    <Link href="/admin/staff">
                        <Button>Admin Users</Button>
                    </Link>
                ) : null}
            </div>

            <div className="mb-4 flex flex-wrap gap-2">
                {navigation.map((segment) => {
                    const active = (filters.segment || 'all') === segment.slug;

                    return (
                        <button
                            key={segment.slug}
                            type="button"
                            onClick={() => visit({ segment: segment.slug === 'all' ? '' : segment.slug, user: '' })}
                            className={`rounded-full px-3 py-1.5 text-sm ${
                                active ? 'bg-brand text-white' : 'border border-line bg-white text-ink hover:bg-surface'
                            }`}
                        >
                            {segment.name}
                        </button>
                    );
                })}
            </div>

            <FilterBar onSubmit={filter}>
                <SearchBar name="search" defaultValue={filters.search} placeholder="Name, username, email, mobile, OurNear ID" />
                <Select name="account_type" defaultValue={filters.account_type} aria-label="User type">
                    <option value="">All types</option>
                    {options.account_types.map((type) => (
                        <option key={type.value} value={type.value}>
                            {type.label}
                        </option>
                    ))}
                </Select>
                <Select name="status" defaultValue={filters.status} aria-label="Account status">
                    <option value="">All statuses</option>
                    {options.statuses.map((status) => (
                        <option key={status.value} value={status.value}>
                            {status.label}
                        </option>
                    ))}
                </Select>
                <Select name="verification" defaultValue={filters.verification} aria-label="Verification">
                    <option value="">Any verification</option>
                    {options.verifications.map((item) => (
                        <option key={item.value} value={item.value}>
                            {item.label}
                        </option>
                    ))}
                </Select>
                <Input name="location" defaultValue={filters.location} placeholder="Location" aria-label="Location" />
                <Select name="language" defaultValue={filters.language} aria-label="Language">
                    <option value="">Any language</option>
                    {options.languages.map((language) => (
                        <option key={language.value} value={language.value}>
                            {language.label}
                        </option>
                    ))}
                </Select>
                <Input name="registered_from" type="date" defaultValue={filters.registered_from} aria-label="Registered from" />
                <Input name="registered_to" type="date" defaultValue={filters.registered_to} aria-label="Registered to" />
                <Input name="last_login_from" type="date" defaultValue={filters.last_login_from} aria-label="Last login from" />
                <Input name="last_login_to" type="date" defaultValue={filters.last_login_to} aria-label="Last login to" />
                <Select name="completion_min" defaultValue={filters.completion_min} aria-label="Profile completion">
                    <option value="">Any completion</option>
                    <option value="25">25% or more</option>
                    <option value="50">50% or more</option>
                    <option value="80">80% or more</option>
                </Select>
                <Select name="brand_id" defaultValue={filters.brand_id} aria-label="Brand">
                    <option value="">All brands</option>
                    {options.brands.map((brand) => (
                        <option key={brand.id} value={brand.id}>
                            {brand.name}
                        </option>
                    ))}
                </Select>
                <div className="flex flex-wrap gap-2 md:col-span-4">
                    <Button type="submit">Apply filters</Button>
                    <Button type="button" variant="secondary" onClick={() => visit({ segment: filters.segment === 'all' ? '' : filters.segment }, true)}>
                        Reset filters
                    </Button>
                </div>
            </FilterBar>

            <DataTable
                columns={columns}
                rows={users.data}
                rowKey={(row) => row.id}
                emptyTitle="No users match these filters"
                emptyDescription="Try another segment or reset filters. Users who have not applied for a job still appear here."
                onRowClick={(row) => router.get(`/admin/users/${row.id}`)}
            />

            <Pagination links={users.links} />
        </AdminLayout>
    );
}
