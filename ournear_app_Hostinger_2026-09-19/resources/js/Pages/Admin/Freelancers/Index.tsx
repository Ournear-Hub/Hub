import { router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import { Button, DataTable, FilterBar, SearchBar, Select, StatusBadge } from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { Paginator } from '@/types/jobs';

type FreelancerRow = {
    id: number;
    business_id: string;
    name: string;
    mobile: string;
    email: string;
    primary_skill: string;
    industry: string | null;
    location: string | null;
    experience: string | null;
    status: string;
    status_label: string;
    submitted_at: string | null;
};

type Option = {
    value: string | number;
    label: string;
};

type Props = {
    freelancers: Paginator<FreelancerRow>;
    filters: {
        search: string;
        status: string;
        industry_id: string;
        location_id: string;
    };
    options: {
        statuses: Option[];
        industries: Option[];
        locations: Option[];
    };
};

export default function FreelancersIndex({ freelancers, filters, options }: Props) {
    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get('/admin/freelancers', Object.fromEntries(form.entries()), { preserveState: true });
    };

    const columns: Array<DataTableColumn<FreelancerRow>> = [
        { key: 'business_id', header: 'Freelancer ID', className: 'text-ink', render: (row) => row.business_id },
        { key: 'name', header: 'Name', className: 'text-ink', render: (row) => row.name },
        { key: 'mobile', header: 'Mobile', render: (row) => row.mobile },
        { key: 'email', header: 'Email', render: (row) => row.email },
        { key: 'primary_skill', header: 'Service / Expertise', render: (row) => row.primary_skill },
        { key: 'industry', header: 'Industry', render: (row) => row.industry ?? '—' },
        { key: 'location', header: 'Location', render: (row) => row.location ?? '—' },
        { key: 'experience', header: 'Experience', render: (row) => row.experience ?? '—' },
        {
            key: 'status',
            header: 'Status',
            render: (row) => <StatusBadge status={row.status_label} />,
        },
        { key: 'submitted_at', header: 'Submitted Date', render: (row) => row.submitted_at ?? '—' },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button variant="secondary" onClick={() => router.get(`/admin/freelancers/${row.id}`)}>
                    View
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout title="Freelancers">
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight text-ink">Freelancers</h1>
                <p className="mt-1 text-sm text-muted">
                    Freelancer partnership records. These are not job applications or candidate accounts.
                </p>
            </div>

            <FilterBar className="md:grid-cols-4" onSubmit={filter}>
                <SearchBar defaultValue={filters.search} placeholder="Freelancer ID, name, mobile, email, skill" />
                <Select name="status" defaultValue={filters.status} aria-label="Status">
                    <option value="">All statuses</option>
                    {options.statuses.map((item) => (
                        <option key={String(item.value)} value={item.value}>
                            {item.label}
                        </option>
                    ))}
                </Select>
                <Select name="industry_id" defaultValue={filters.industry_id} aria-label="Industry">
                    <option value="">All industries</option>
                    {options.industries.map((item) => (
                        <option key={String(item.value)} value={item.value}>
                            {item.label}
                        </option>
                    ))}
                </Select>
                <Select name="location_id" defaultValue={filters.location_id} aria-label="Location">
                    <option value="">All locations</option>
                    {options.locations.map((item) => (
                        <option key={String(item.value)} value={item.value}>
                            {item.label}
                        </option>
                    ))}
                </Select>
                <Button type="submit">Filter</Button>
            </FilterBar>

            <DataTable
                columns={columns}
                rows={freelancers.data}
                rowKey={(row) => row.id}
                emptyTitle="No freelancer submissions yet."
            />
            <Pagination links={freelancers.links} />
        </AdminLayout>
    );
}
