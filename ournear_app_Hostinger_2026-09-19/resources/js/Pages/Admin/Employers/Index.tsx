import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    ActivityFeed,
    Button,
    DataTable,
    Drawer,
    FilterBar,
    Input,
    SearchBar,
    Select,
    Tabs,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { Paginator } from '@/types/jobs';

type EmployerRow = {
    id: number;
    business_id: string | null;
    company_name: string;
    contact_person: string | null;
    email: string | null;
    mobile_number: string | null;
    industry: string;
    jobs_count: number;
    submissions_count: number;
    created_at: string | null;
    updated_at: string | null;
};

type ActivityItem = { id: number; title: string; meta: string };

type JobRow = {
    id: number;
    business_id: string | null;
    title: string;
    slug: string;
    status_label: string;
};

type SubmissionRow = {
    id: number;
    business_id: string | null;
    status: string;
    submitted_at: string | null;
    job: { title: string } | null;
    candidate: { name: string | null; business_id: string | null } | null;
    application: { business_id: string | null } | null;
};

type EnquiryRow = {
    id: number;
    company_name: string;
    status: string;
    created_at: string | null;
};

type Props = {
    employers: Paginator<EmployerRow>;
    selected: EmployerRow | null;
    jobs: JobRow[];
    submissions: SubmissionRow[];
    enquiries: EnquiryRow[];
    activities: ActivityItem[];
    filters: {
        search: string;
        industry_id: string;
        date_from: string;
        date_to: string;
        employer: string;
    };
    industries: Array<{ id: number; name: string }>;
};

export default function EmployersIndex({
    employers,
    selected,
    jobs,
    submissions,
    enquiries,
    activities,
    filters,
    industries,
}: Props) {
    const [tab, setTab] = useState('overview');

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/employers', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ employer: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        visit({
            ...Object.fromEntries(new FormData(event.currentTarget).entries()),
            employer: filters.employer,
        } as Record<string, string>);
    };

    const columns: Array<DataTableColumn<EmployerRow>> = [
        { key: 'id', header: 'Employer ID', className: 'font-medium text-ink', render: (row) => row.business_id ?? '—' },
        { key: 'company', header: 'Company', className: 'font-medium text-ink', render: (row) => row.company_name },
        { key: 'contact', header: 'Contact', render: (row) => row.contact_person ?? '—' },
        { key: 'mobile', header: 'Mobile', render: (row) => row.mobile_number ?? '—' },
        { key: 'email', header: 'Email', render: (row) => row.email ?? '—' },
        { key: 'industry', header: 'Industry', render: (row) => row.industry || '—' },
        { key: 'jobs', header: 'Jobs', render: (row) => row.jobs_count },
        { key: 'submissions', header: 'Submissions', render: (row) => row.submissions_count },
        { key: 'created', header: 'Created', render: (row) => row.created_at ?? '—' },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button variant="ghost" onClick={() => visit({ employer: String(row.id) })}>
                    View
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout title="Employers">
            <div
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Employers</h1>
                    <p className="mt-1 text-sm text-muted">Registered employer accounts. Historical enquiries stay in Employer Enquiries until linked by account.</p>
                </div>
                <FilterBar onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Employer ID, company, contact, email, mobile" />
                    <Select name="industry_id" defaultValue={filters.industry_id} aria-label="Industry">
                        <option value="">All industries</option>
                        {industries.map((industry) => (
                            <option key={industry.id} value={industry.id}>
                                {industry.name}
                            </option>
                        ))}
                    </Select>
                    <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="Created from" />
                    <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="Created to" />
                    <Button type="submit">Filter</Button>
                </FilterBar>
                <DataTable
                    columns={columns}
                    rows={employers.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No employers match these filters"
                    emptyDescription="Employer accounts appear here after registration."
                    onRowClick={(row) => visit({ employer: String(row.id) })}
                    selectedKey={selected?.id ?? null}
                />
                <Pagination links={employers.links} />
            </div>

            <Drawer open={selected !== null} title={selected?.business_id ?? 'Employer'} size="xl" onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-4">
                        <Tabs
                            tabs={[
                                { id: 'overview', label: 'Overview' },
                                { id: 'jobs', label: `Jobs (${selected.jobs_count})` },
                                { id: 'submissions', label: `Submissions (${selected.submissions_count})` },
                                { id: 'enquiries', label: `Enquiries (${enquiries.length})` },
                                { id: 'activity', label: 'Activity' },
                            ]}
                            active={tab}
                            onChange={setTab}
                        />
                        {tab === 'overview' ? (
                            <dl className="grid gap-2 text-sm">
                                {[
                                    ['Employer ID', selected.business_id ?? '—'],
                                    ['Company', selected.company_name],
                                    ['Contact person', selected.contact_person ?? '—'],
                                    ['Email', selected.email ?? '—'],
                                    ['Mobile', selected.mobile_number ?? '—'],
                                    ['Industry', selected.industry || '—'],
                                    ['Created', selected.created_at ?? '—'],
                                    ['Updated', selected.updated_at ?? '—'],
                                ].map(([label, value]) => (
                                    <div key={label} className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">{label}</dt>
                                        <dd className="col-span-2 text-ink">{value}</dd>
                                    </div>
                                ))}
                            </dl>
                        ) : null}
                        {tab === 'jobs' ? (
                            <div className="space-y-3">
                                {jobs.length === 0 ? (
                                    <p className="text-sm text-muted">No jobs are assigned to this employer yet. Assign an employer from the Job workspace.</p>
                                ) : (
                                    <ul className="space-y-2">
                                        {jobs.map((job) => (
                                            <li key={job.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                                <p className="font-medium text-ink">{job.title}</p>
                                                <p className="text-muted">{job.business_id ?? '—'} · {job.status_label}</p>
                                                <Link href={`/admin/jobs?job=${job.slug}`} className="mt-1 inline-block font-medium text-brand">
                                                    Open job
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        ) : null}
                        {tab === 'submissions' ? (
                            <div className="space-y-3">
                                {submissions.length === 0 ? (
                                    <p className="text-sm text-muted">No employer submissions yet.</p>
                                ) : (
                                    <ul className="space-y-2">
                                        {submissions.map((submission) => (
                                            <li key={submission.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                                <p className="font-medium text-ink">{submission.candidate?.name ?? 'Candidate'}</p>
                                                <p className="text-muted">
                                                    {submission.business_id ?? '—'} · {submission.job?.title ?? 'Job'} · {submission.status}
                                                </p>
                                                <Link href={`/admin/submissions?submission=${submission.id}`} className="mt-1 inline-block font-medium text-brand">
                                                    Open submission
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        ) : null}
                        {tab === 'enquiries' ? (
                            <div className="space-y-3">
                                {enquiries.length === 0 ? (
                                    <p className="text-sm text-muted">No account-linked employer enquiries. Guest enquiries remain in Employer Enquiries.</p>
                                ) : (
                                    <ul className="space-y-2">
                                        {enquiries.map((enquiry) => (
                                            <li key={enquiry.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                                <p className="font-medium text-ink">{enquiry.company_name}</p>
                                                <p className="text-muted">{enquiry.status} · {enquiry.created_at}</p>
                                                <Link href={`/admin/employer-enquiries?enquiry=${enquiry.id}`} className="mt-1 inline-block font-medium text-brand">
                                                    Open enquiry
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        ) : null}
                        {tab === 'activity' ? (
                            <ActivityFeed items={activities} empty="No recruitment activity is linked to this employer yet." />
                        ) : null}
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}
