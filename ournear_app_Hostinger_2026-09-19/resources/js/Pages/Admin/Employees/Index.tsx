import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import { Button, DataTable, Drawer, FilterBar, Input, SearchBar, Select, Tabs, Timeline } from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { Paginator } from '@/types/jobs';
import type { EmployeeListItem } from '@/types/placements';

type EmployeeDetail = EmployeeListItem & {
    created_by: string | null;
    expected_joining_date: string | null;
    actual_joining_date: string | null;
    joining_notes: string | null;
    activities: Array<{
        id: number;
        type: string;
        body: string | null;
        from_status: string | null;
        to_status: string | null;
        actor_name: string | null;
        created_at: string | null;
    }>;
};

type Props = {
    employees: Paginator<EmployeeListItem>;
    selected: EmployeeDetail | null;
    filters: {
        search: string;
        employer_id: string;
        job_id: string;
        date_from: string;
        date_to: string;
        employee: string;
    };
    options: {
        employers: Array<{ id: number; label: string }>;
        jobs: Array<{ id: number; title: string; business_id: string | null }>;
    };
};

function activityTitle(activity: EmployeeDetail['activities'][number]): string {
    if (activity.from_status && activity.to_status) {
        return `Status: ${activity.from_status} → ${activity.to_status}`;
    }

    return activity.body || activity.type.replace(/_/g, ' ');
}

export default function EmployeesIndex({ employees, selected, filters, options }: Props) {
    const [tab, setTab] = useState('overview');

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/employees', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ employee: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        visit({
            ...Object.fromEntries(new FormData(event.currentTarget).entries()),
            employee: filters.employee,
        } as Record<string, string>);
    };

    const columns: Array<DataTableColumn<EmployeeListItem>> = [
        { key: 'id', header: 'Employee ID', className: 'font-medium text-ink', render: (row) => row.business_id ?? '—' },
        { key: 'candidate', header: 'Candidate', className: 'font-medium text-ink', render: (row) => row.candidate.name ?? '—' },
        { key: 'employer', header: 'Employer', render: (row) => row.employer?.company_name ?? '—' },
        { key: 'job', header: 'Job', render: (row) => row.job?.title ?? '—' },
        { key: 'placement', header: 'Placement', render: (row) => row.placement?.business_id ?? '—' },
        { key: 'joined', header: 'Joined', render: (row) => row.joined_at ?? '—' },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button variant="ghost" onClick={() => visit({ employee: String(row.id) })}>
                    View
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout title="Joined / Employees">
            <div
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Joined / Employees</h1>
                    <p className="mt-1 text-sm text-muted">
                        Employees are created when a candidate joins a specific placement. This does not globally convert the candidate.
                    </p>
                </div>
                <FilterBar onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Employee, placement, employer, job, candidate ID" />
                    <Select name="employer_id" defaultValue={filters.employer_id} aria-label="Employer">
                        <option value="">All employers</option>
                        {options.employers.map((employer) => (
                            <option key={employer.id} value={employer.id}>
                                {employer.label}
                            </option>
                        ))}
                    </Select>
                    <Select name="job_id" defaultValue={filters.job_id} aria-label="Job">
                        <option value="">All jobs</option>
                        {options.jobs.map((job) => (
                            <option key={job.id} value={job.id}>
                                {job.business_id ? `${job.business_id} · ${job.title}` : job.title}
                            </option>
                        ))}
                    </Select>
                    <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="Joined from" />
                    <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="Joined to" />
                    <Button type="submit">Filter</Button>
                </FilterBar>
                <DataTable
                    columns={columns}
                    rows={employees.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No joined employees match these filters"
                    emptyDescription="Employee records appear after a placement is marked joined."
                    onRowClick={(row) => visit({ employee: String(row.id) })}
                    selectedKey={selected?.id ?? null}
                />
                <Pagination links={employees.links} />
            </div>

            <Drawer open={selected !== null} title={selected?.business_id ?? 'Employee'} size="xl" onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-4">
                        <Tabs
                            tabs={[
                                { id: 'overview', label: 'Overview' },
                                { id: 'lineage', label: 'Lineage' },
                                { id: 'timeline', label: 'Timeline' },
                            ]}
                            active={tab}
                            onChange={setTab}
                        />
                        {tab === 'overview' ? (
                            <dl className="grid gap-2 text-sm">
                                {[
                                    ['Employee ID', selected.business_id ?? '—'],
                                    ['Candidate', selected.candidate.name ?? '—'],
                                    ['Employer', selected.employer?.company_name ?? '—'],
                                    ['Job', selected.job?.title ?? '—'],
                                    ['Joined', selected.joined_at ?? '—'],
                                    ['Expected joining', selected.expected_joining_date ?? '—'],
                                    ['Actual joining', selected.actual_joining_date ?? '—'],
                                ].map(([label, value]) => (
                                    <div key={label} className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">{label}</dt>
                                        <dd className="col-span-2 text-ink">{value}</dd>
                                    </div>
                                ))}
                            </dl>
                        ) : null}
                        {tab === 'lineage' ? (
                            <ul className="space-y-2 text-sm">
                                <li>
                                    Employee {selected.business_id}
                                </li>
                                <li>
                                    Joining {selected.actual_joining_date ?? '—'}
                                </li>
                                <li>
                                    Placement{' '}
                                    {selected.placement?.id ? (
                                        <Link href={`/admin/placements?placement=${selected.placement.id}`} className="font-medium text-brand">
                                            {selected.placement.business_id}
                                        </Link>
                                    ) : (
                                        '—'
                                    )}
                                </li>
                                <li>
                                    Submission{' '}
                                    {selected.submission?.id ? (
                                        <Link href={`/admin/submissions?submission=${selected.submission.id}`} className="font-medium text-brand">
                                            {selected.submission.business_id}
                                        </Link>
                                    ) : (
                                        '—'
                                    )}
                                </li>
                                <li>
                                    Application {selected.application?.business_id ?? '—'}
                                </li>
                                <li>
                                    Candidate{' '}
                                    {selected.candidate.id ? (
                                        <Link href={`/admin/candidates?candidate=${selected.candidate.id}`} className="font-medium text-brand">
                                            {selected.candidate.business_id ?? selected.candidate.name}
                                        </Link>
                                    ) : (
                                        selected.candidate.name ?? 'Guest application'
                                    )}
                                </li>
                                <li>
                                    Job{' '}
                                    {selected.job?.slug ? (
                                        <Link href={`/admin/jobs?job=${selected.job.slug}`} className="font-medium text-brand">
                                            {selected.job.business_id ?? selected.job.title}
                                        </Link>
                                    ) : (
                                        selected.job?.title ?? '—'
                                    )}
                                </li>
                                <li>
                                    Employer{' '}
                                    {selected.employer?.id ? (
                                        <Link href={`/admin/employers?employer=${selected.employer.id}`} className="font-medium text-brand">
                                            {selected.employer.business_id ?? selected.employer.company_name}
                                        </Link>
                                    ) : (
                                        '—'
                                    )}
                                </li>
                            </ul>
                        ) : null}
                        {tab === 'timeline' ? (
                            <Timeline
                                items={(selected.activities ?? []).map((activity) => ({
                                    id: activity.id,
                                    title: activityTitle(activity),
                                    meta: `${activity.actor_name ?? 'System'} · ${activity.created_at ?? ''}`,
                                }))}
                                empty="No recruitment activity for this placement yet."
                            />
                        ) : null}
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}
