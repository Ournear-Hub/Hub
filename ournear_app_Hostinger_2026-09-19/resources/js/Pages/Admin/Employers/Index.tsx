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
    StatusBadge,
    Tabs,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import { recorded } from '@/lib/recorded';
import type { Paginator } from '@/types/jobs';

type EmployerRow = {
    id: number;
    business_id: string | null;
    company_name: string;
    contact_person: string | null;
    email: string | null;
    mobile_number: string | null;
    industry: string | null;
    location: string | null;
    verification_status?: string;
    account_status_label?: string | null;
    job_posting_approved?: boolean;
    job_posting_approved_at?: string | null;
    jobs_count: number;
    active_jobs_count?: number;
    applications_count?: number;
    submissions_count: number;
    selections_count?: number;
    placements_count?: number;
    employees_count?: number;
    replacements_count?: number;
    created_at: string | null;
    updated_at: string | null;
    links?: Record<string, string | null | undefined>;
};

type ActivityItem = { id: number; title: string; meta: string };

type JobRow = {
    id: number;
    business_id: string | null;
    title: string;
    slug: string;
    status_label: string;
};

type RelatedRow = {
    id: number;
    business_id?: string | null;
    status?: string;
    name?: string | null;
    title?: string;
    submitted_at?: string | null;
    joined_at?: string | null;
    requested_at?: string | null;
    candidate?: { name: string | null; business_id?: string | null } | null;
    job?: { title?: string; slug?: string; business_id?: string | null } | null;
    application?: { business_id?: string | null } | null;
    original_placement?: { id: number; business_id?: string | null } | null;
    original_candidate?: { name?: string | null } | null;
    replacement_candidate?: { name?: string | null } | null;
    workspace_url?: string | null;
};

type EnquiryRow = {
    id: number;
    company_name: string;
    status: string;
    created_at: string | null;
};

type FinanceSummary = {
    currency: string;
    billed: string;
    received: string;
    outstanding: string;
    overdue: string;
};

type InvoiceRow = {
    id: number;
    business_id: string | null;
    status: string;
    currency: string;
    total_amount: string;
    outstanding_amount: string;
    is_overdue: boolean;
};

type PaymentRow = {
    id: number;
    business_id: string | null;
    amount: string;
    currency: string;
    paid_at: string | null;
    method: string;
    invoice_business_id: string | null;
};

type Props = {
    employers: Paginator<EmployerRow>;
    selected: EmployerRow | null;
    jobs: JobRow[];
    applications: RelatedRow[];
    submissions: RelatedRow[];
    selections: RelatedRow[];
    placements: RelatedRow[];
    employees: RelatedRow[];
    replacements: RelatedRow[];
    enquiries: EnquiryRow[];
    activities: ActivityItem[];
    finance: FinanceSummary | null;
    invoices: InvoiceRow[];
    payments: PaymentRow[];
    filters: {
        search: string;
        employer_id: string;
        company: string;
        industry_id: string;
        location: string;
        status: string;
        verification: string;
        date_from: string;
        date_to: string;
        employer: string;
    };
    industries: Array<{ id: number; name: string }>;
    accountStatuses: Array<{ value: string; label: string }>;
};

function relatedLink(kind: 'job' | 'application' | 'submission' | 'placement' | 'employee' | 'replacement', row: RelatedRow): string {
    if (kind === 'job' && row.job?.slug) {
        return `/admin/jobs?job=${row.job.slug}`;
    }
    if (kind === 'application') {
        return `/admin/applications?application=${row.id}`;
    }

    return `/admin/${kind}s?${kind}=${row.id}`;
}

export default function EmployersIndex({
    employers,
    selected,
    jobs,
    applications,
    submissions,
    selections,
    placements,
    employees,
    replacements,
    enquiries,
    activities,
    finance,
    invoices,
    payments,
    filters,
    industries,
    accountStatuses,
}: Props) {
    const [tab, setTab] = useState('company');

    const visit = (overrides: Record<string, string | undefined> = {}, replaceAll = false) => {
        const next = replaceAll ? overrides : { ...filters, ...overrides };
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
        { key: 'id', header: 'Employer ID', className: 'font-medium text-ink', render: (row) => recorded(row.business_id) },
        { key: 'company', header: 'Company name', className: 'font-medium text-ink', render: (row) => recorded(row.company_name) },
        { key: 'industry', header: 'Industry', render: (row) => recorded(row.industry) },
        { key: 'location', header: 'Location', render: (row) => recorded(row.location) },
        { key: 'contact', header: 'Contact person', render: (row) => recorded(row.contact_person) },
        { key: 'email', header: 'Email', render: (row) => recorded(row.email) },
        { key: 'mobile', header: 'Mobile', render: (row) => recorded(row.mobile_number) },
        {
            key: 'verified',
            header: 'Verification',
            render: (row) => <StatusBadge status={row.verification_status ?? 'Unverified'} />,
        },
        { key: 'active_jobs', header: 'Active jobs', render: (row) => row.active_jobs_count ?? 0 },
        { key: 'applications', header: 'Applications/submissions', render: (row) => `${row.applications_count ?? 0}/${row.submissions_count}` },
        { key: 'selections', header: 'Selections', render: (row) => row.selections_count ?? 0 },
        { key: 'placements', header: 'Placements', render: (row) => row.placements_count ?? 0 },
        { key: 'employees', header: 'Employees', render: (row) => row.employees_count ?? 0 },
        {
            key: 'status',
            header: 'Status',
            render: (row) => (row.account_status_label ? <StatusBadge status={row.account_status_label} /> : recorded(null)),
        },
        { key: 'created', header: 'Created date', render: (row) => recorded(row.created_at) },
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
                    <p className="mt-1 text-sm text-muted">
                        Employer 360 for recruitment operations. Missing company fields stay as not recorded; they are not invented.
                    </p>
                </div>
                <FilterBar className="md:grid-cols-4 xl:grid-cols-6" onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Employer ID, company, contact, email, mobile" />
                    <Input name="employer_id" defaultValue={filters.employer_id} placeholder="Employer ID" aria-label="Employer ID" />
                    <Input name="company" defaultValue={filters.company} placeholder="Company" aria-label="Company" />
                    <Select name="industry_id" defaultValue={filters.industry_id} aria-label="Industry">
                        <option value="">All industries</option>
                        {industries.map((industry) => (
                            <option key={industry.id} value={industry.id}>
                                {industry.name}
                            </option>
                        ))}
                    </Select>
                    <Input name="location" defaultValue={filters.location} placeholder="Location" aria-label="Location" />
                    <Select name="status" defaultValue={filters.status} aria-label="Status">
                        <option value="">Any status</option>
                        {accountStatuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </Select>
                    <Select name="verification" defaultValue={filters.verification} aria-label="Verification">
                        <option value="">Any verification</option>
                        <option value="verified">Verified</option>
                        <option value="unverified">Unverified</option>
                    </Select>
                    <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="Created from" />
                    <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="Created to" />
                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit">Filter</Button>
                        <Button type="button" variant="secondary" onClick={() => visit({}, true)}>
                            Reset
                        </Button>
                    </div>
                </FilterBar>
                <DataTable
                    columns={columns}
                    rows={employers.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No employers match these filters"
                    emptyDescription="Employer accounts appear here after registration. Reset filters to see the full list."
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
                                { id: 'company', label: 'Company Information' },
                                { id: 'contact', label: 'Contact Information' },
                                { id: 'verification', label: 'Verification' },
                                { id: 'jobs', label: `Jobs (${selected.jobs_count})` },
                                { id: 'applications', label: `Applications / Candidates (${selected.applications_count ?? 0})` },
                                { id: 'submissions', label: `Employer Submissions (${selected.submissions_count})` },
                                { id: 'selections', label: `Selections (${selected.selections_count ?? 0})` },
                                { id: 'placements', label: `Placements (${selected.placements_count ?? 0})` },
                                { id: 'employees', label: `Employees (${selected.employees_count ?? 0})` },
                                { id: 'replacements', label: `Replacements (${selected.replacements_count ?? 0})` },
                                { id: 'activity', label: 'Activity' },
                                { id: 'enquiries', label: `Enquiries (${enquiries.length})` },
                                { id: 'finance', label: 'Finance' },
                            ]}
                            active={tab}
                            onChange={setTab}
                        />
                        {tab === 'company' ? (
                            <div className="space-y-4">
                                <dl className="grid gap-2 text-sm">
                                    {[
                                        ['Employer ID', recorded(selected.business_id)],
                                        ['Company name', recorded(selected.company_name)],
                                        ['Industry', recorded(selected.industry)],
                                        ['Location', recorded(selected.location)],
                                        ['Status', recorded(selected.account_status_label)],
                                        ['Created date', recorded(selected.created_at)],
                                        ['Updated', recorded(selected.updated_at)],
                                    ].map(([label, value]) => (
                                        <div key={label} className="grid grid-cols-3 gap-2">
                                            <dt className="text-muted">{label}</dt>
                                            <dd className="col-span-2 text-ink">{value}</dd>
                                        </div>
                                    ))}
                                </dl>
                                <div className="flex flex-wrap gap-3 text-sm">
                                    <Link href={selected.links?.jobs ?? `/admin/jobs?employer_id=${selected.id}`} className="font-medium text-brand">
                                        Jobs
                                    </Link>
                                    <Link href={selected.links?.submissions ?? `/admin/submissions?employer_id=${selected.id}`} className="font-medium text-brand">
                                        Submissions
                                    </Link>
                                    <Link href={selected.links?.selections ?? `/admin/submissions?employer_id=${selected.id}&status=Selected`} className="font-medium text-brand">
                                        Selections
                                    </Link>
                                    <Link href={selected.links?.placements ?? `/admin/placements?employer_id=${selected.id}`} className="font-medium text-brand">
                                        Placements
                                    </Link>
                                    <Link href={selected.links?.employees ?? `/admin/employees?employer_id=${selected.id}`} className="font-medium text-brand">
                                        Employees
                                    </Link>
                                    <Link href={selected.links?.replacements ?? `/admin/replacements?employer_id=${selected.id}`} className="font-medium text-brand">
                                        Replacements
                                    </Link>
                                </div>
                            </div>
                        ) : null}
                        {tab === 'contact' ? (
                            <dl className="grid gap-2 text-sm">
                                {[
                                    ['Contact person', recorded(selected.contact_person)],
                                    ['Email', recorded(selected.email)],
                                    ['Mobile', recorded(selected.mobile_number)],
                                ].map(([label, value]) => (
                                    <div key={label} className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">{label}</dt>
                                        <dd className="col-span-2 text-ink">{value}</dd>
                                    </div>
                                ))}
                            </dl>
                        ) : null}
                        {tab === 'verification' ? (
                            <div className="space-y-4">
                                <dl className="grid gap-2 text-sm">
                                    <div className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">Verification status</dt>
                                        <dd className="col-span-2">
                                            <StatusBadge status={selected.verification_status ?? 'Unverified'} />
                                        </dd>
                                    </div>
                                    <div className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">Job posting approval</dt>
                                        <dd className="col-span-2">
                                            <StatusBadge status={selected.job_posting_approved ? 'Approved' : 'Not approved'} />
                                            {selected.job_posting_approved_at ? <p className="mt-1 text-xs text-muted">{selected.job_posting_approved_at}</p> : null}
                                        </dd>
                                    </div>
                                </dl>
                                <Button
                                    variant="secondary"
                                    onClick={() => router.patch(`/admin/employers/${selected.id}/posting-approval`, {})}
                                >
                                    {selected.job_posting_approved ? 'Revoke posting approval' : 'Approve job posting'}
                                </Button>
                            </div>
                        ) : null}
                        {tab === 'jobs' ? (
                            <RelatedList
                                empty="No jobs are assigned to this employer yet."
                                rows={jobs.map((job) => ({
                                    id: job.id,
                                    title: job.title,
                                    meta: `${recorded(job.business_id)} · ${job.status_label}`,
                                    href: `/admin/jobs?job=${job.slug}`,
                                    label: 'Open job',
                                }))}
                            />
                        ) : null}
                        {tab === 'applications' ? (
                            <RelatedList
                                empty="No applications are linked to this employer's jobs yet."
                                rows={applications.map((application) => ({
                                    id: application.id,
                                    title: application.name ?? 'Candidate',
                                    meta: `${recorded(application.business_id)} · ${recorded(application.job?.title)} · ${recorded(application.status)}`,
                                    href: relatedLink('application', application),
                                    label: 'Open application',
                                }))}
                            />
                        ) : null}
                        {tab === 'submissions' ? (
                            <RelatedList
                                empty="No employer submissions yet."
                                rows={submissions.map((submission) => ({
                                    id: submission.id,
                                    title: submission.candidate?.name ?? 'Candidate',
                                    meta: `${recorded(submission.business_id)} · ${recorded(submission.job?.title)} · ${recorded(submission.status)}`,
                                    href: `/admin/submissions?submission=${submission.id}`,
                                    label: 'Open submission',
                                }))}
                            />
                        ) : null}
                        {tab === 'selections' ? (
                            <RelatedList
                                empty="No selected submissions yet. Selection is the employer response of Selected."
                                rows={selections.map((selection) => ({
                                    id: selection.id,
                                    title: selection.candidate?.name ?? 'Candidate',
                                    meta: `${recorded(selection.business_id)} · ${recorded(selection.job?.title)} · ${recorded(selection.status)}`,
                                    href: `/admin/submissions?submission=${selection.id}`,
                                    label: 'Open selection',
                                }))}
                            />
                        ) : null}
                        {tab === 'placements' ? (
                            <RelatedList
                                empty="No placements yet."
                                rows={placements.map((placement) => ({
                                    id: placement.id,
                                    title: placement.candidate?.name ?? 'Placement',
                                    meta: `${recorded(placement.business_id)} · ${recorded(placement.status)}`,
                                    href: `/admin/placements?placement=${placement.id}`,
                                    label: 'Open placement',
                                }))}
                            />
                        ) : null}
                        {tab === 'employees' ? (
                            <RelatedList
                                empty="No joined employees yet."
                                rows={employees.map((employee) => ({
                                    id: employee.id,
                                    title: employee.candidate?.name ?? 'Employee',
                                    meta: `${recorded(employee.business_id)} · ${recorded(employee.joined_at)}`,
                                    href: `/admin/employees?employee=${employee.id}`,
                                    label: 'Open employee',
                                }))}
                            />
                        ) : null}
                        {tab === 'replacements' ? (
                            <RelatedList
                                empty="No replacement cases for this employer."
                                rows={replacements.map((replacement) => ({
                                    id: replacement.id,
                                    title: replacement.original_candidate?.name ?? replacement.candidate?.name ?? 'Replacement',
                                    meta: `${recorded(replacement.business_id)} · ${recorded(replacement.status)}`,
                                    href: `/admin/replacements?replacement=${replacement.id}`,
                                    label: 'Open replacement',
                                }))}
                            />
                        ) : null}
                        {tab === 'activity' ? (
                            <ActivityFeed items={activities} empty="No recruitment activity is linked to this employer yet." />
                        ) : null}
                        {tab === 'enquiries' ? (
                            <RelatedList
                                empty="No account-linked employer enquiries. Guest enquiries remain in Employer Enquiries."
                                rows={enquiries.map((enquiry) => ({
                                    id: enquiry.id,
                                    title: enquiry.company_name,
                                    meta: `${enquiry.status} · ${recorded(enquiry.created_at)}`,
                                    href: `/admin/employer-enquiries?enquiry=${enquiry.id}`,
                                    label: 'Open enquiry',
                                }))}
                            />
                        ) : null}
                        {tab === 'finance' ? (
                            <div className="space-y-4 text-sm">
                                {finance ? (
                                    <dl className="grid gap-2">
                                        {[
                                            ['Billed', `${finance.currency} ${finance.billed}`],
                                            ['Received', `${finance.currency} ${finance.received}`],
                                            ['Outstanding', `${finance.currency} ${finance.outstanding}`],
                                            ['Overdue', `${finance.currency} ${finance.overdue}`],
                                        ].map(([label, value]) => (
                                            <div key={label} className="grid grid-cols-3 gap-2">
                                                <dt className="text-muted">{label}</dt>
                                                <dd className="col-span-2 text-ink">{value}</dd>
                                            </div>
                                        ))}
                                    </dl>
                                ) : null}
                                <div>
                                    <p className="font-medium text-ink">Invoices</p>
                                    {invoices.length === 0 ? (
                                        <p className="mt-1 text-muted">No invoices yet.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2">
                                            {invoices.map((invoice) => (
                                                <li key={invoice.id} className="rounded-md bg-surface px-3 py-2">
                                                    <Link href={`/admin/finance?invoice=${invoice.id}`} className="font-medium text-brand">
                                                        {invoice.business_id}
                                                    </Link>
                                                    <p className="text-muted">
                                                        {invoice.status} · {invoice.currency} {invoice.total_amount}
                                                        {invoice.is_overdue ? ' · Overdue' : ''}
                                                    </p>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                                <div>
                                    <p className="font-medium text-ink">Recent payments</p>
                                    {payments.length === 0 ? (
                                        <p className="mt-1 text-muted">No payments yet.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2">
                                            {payments.map((payment) => (
                                                <li key={payment.id} className="rounded-md bg-surface px-3 py-2">
                                                    {payment.business_id} · {payment.currency} {payment.amount} · {payment.method}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            </div>
                        ) : null}
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}

function RelatedList({
    empty,
    rows,
}: {
    empty: string;
    rows: Array<{ id: number; title: string; meta: string; href: string; label: string }>;
}) {
    if (rows.length === 0) {
        return <p className="text-sm text-muted">{empty}</p>;
    }

    return (
        <ul className="space-y-2">
            {rows.map((row) => (
                <li key={row.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                    <p className="font-medium text-ink">{row.title}</p>
                    <p className="text-muted">{row.meta}</p>
                    <Link href={row.href} className="mt-1 inline-block font-medium text-brand">
                        {row.label}
                    </Link>
                </li>
            ))}
        </ul>
    );
}
