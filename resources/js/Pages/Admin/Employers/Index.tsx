import { Link, router, useForm } from '@inertiajs/react';
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

type EmployerTag = {
    id: number;
    name: string;
    is_active: boolean;
};

type EmployerRow = {
    id: number;
    business_id: string | null;
    company_name: string | null;
    contact_person: string | null;
    email: string | null;
    mobile_number: string | null;
    industry: string | null;
    location: string | null;
    location_ids?: number[];
    about_company?: string | null;
    company_size?: string | null;
    company_website?: string | null;
    company_details?: string | null;
    benefits?: string[];
    socials?: Record<string, string | null>;
    verification_status?: string;
    email_verified?: boolean;
    mobile_verified?: boolean;
    ournear_verified?: boolean;
    account_status_label?: string | null;
    job_posting_approved?: boolean;
    job_posting_approved_at?: string | null;
    posting_restricted?: boolean;
    posting_restricted_at?: string | null;
    tags?: EmployerTag[];
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

type ActivityItem = { id: string | number; title: string; meta: string };

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

type EmployerDocumentRow = {
    id: number;
    business_id: string | null;
    document_type: string | null;
    document_type_label?: string | null;
    title: string;
    original_filename: string;
    issue_date: string | null;
    expiry_date: string | null;
    expiry_state?: string | null;
    status: string | null;
    status_label?: string | null;
    verification_status: string | null;
    verification_status_label?: string | null;
    verified_by_name?: string | null;
    verified_at?: string | null;
    review_notes?: string | null;
    created_at: string | null;
    download_url: string;
    view_url: string;
};

type FileOption = { value: string; label: string };

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
    documents?: EmployerDocumentRow[];
    document_types?: FileOption[];
    document_statuses?: FileOption[];
    enquiries: EnquiryRow[];
    crm?: Array<{
        id: number;
        company_name: string;
        source_label?: string;
        assignee_name?: string | null;
        opportunities?: Array<{ id: number; title: string; status_label: string }>;
    }>;
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
        location_id: string;
        status: string;
        verification: string;
        ournear_verified: string;
        restricted: string;
        tag_id: string;
        date_from: string;
        date_to: string;
        employer: string;
    };
    industries: Array<{ id: number; name: string }>;
    accountStatuses: Array<{ value: string; label: string }>;
    lookups?: {
        locations?: Array<{ id: number; name: string; is_active?: boolean }>;
        tags?: EmployerTag[];
    };
    can_manage?: boolean;
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
    documents = [],
    document_types = [],
    document_statuses = [],
    enquiries,
    crm = [],
    activities,
    finance,
    invoices,
    payments,
    filters,
    industries,
    accountStatuses,
    lookups = {},
    can_manage = false,
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
        <AdminLayout title="Employers" breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Employers' }]}>
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
                    <Input name="location" defaultValue={filters.location} placeholder="Job location" aria-label="Job location" />
                    <Select name="location_id" defaultValue={filters.location_id} aria-label="Company location">
                        <option value="">Any company location</option>
                        {(lookups.locations ?? []).map((location) => (
                            <option key={location.id} value={location.id}>
                                {location.name}
                            </option>
                        ))}
                    </Select>
                    <Select name="status" defaultValue={filters.status} aria-label="Status">
                        <option value="">Any status</option>
                        {accountStatuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </Select>
                    <Select name="verification" defaultValue={filters.verification} aria-label="Email verification">
                        <option value="">Any email verification</option>
                        <option value="verified">Email verified</option>
                        <option value="unverified">Email unverified</option>
                    </Select>
                    <Select name="ournear_verified" defaultValue={filters.ournear_verified} aria-label="OurNear verification">
                        <option value="">Any OurNear status</option>
                        <option value="1">OurNear verified</option>
                        <option value="0">OurNear not verified</option>
                    </Select>
                    <Select name="restricted" defaultValue={filters.restricted} aria-label="Posting restriction">
                        <option value="">Any posting status</option>
                        <option value="1">Posting restricted</option>
                        <option value="0">Posting not restricted</option>
                    </Select>
                    <Select name="tag_id" defaultValue={filters.tag_id} aria-label="Tag">
                        <option value="">Any tag</option>
                        {(lookups.tags ?? []).map((tag) => (
                            <option key={tag.id} value={tag.id}>
                                {tag.name}
                            </option>
                        ))}
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
                                { id: 'documents', label: `Documents (${documents.length})` },
                                { id: 'jobs', label: `Jobs (${selected.jobs_count})` },
                                { id: 'applications', label: `Applications / Candidates (${selected.applications_count ?? 0})` },
                                { id: 'submissions', label: `Employer Submissions (${selected.submissions_count})` },
                                { id: 'selections', label: `Selections (${selected.selections_count ?? 0})` },
                                { id: 'placements', label: `Placements (${selected.placements_count ?? 0})` },
                                { id: 'employees', label: `Employees (${selected.employees_count ?? 0})` },
                                { id: 'replacements', label: `Replacements (${selected.replacements_count ?? 0})` },
                                { id: 'activity', label: 'Activity' },
                                { id: 'enquiries', label: `Enquiries (${enquiries.length})` },
                                { id: 'crm', label: `CRM (${crm.length})` },
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
                                {(selected.benefits ?? []).length > 0 ? (
                                    <p className="text-sm text-muted">Benefits: {selected.benefits?.join(', ')}</p>
                                ) : null}
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
                                {can_manage ? (
                                    <EmployerCompanyForm key={`company-${selected.id}`} selected={selected} lookups={lookups} />
                                ) : null}
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
                                        <dt className="text-muted">Email verification</dt>
                                        <dd className="col-span-2">
                                            <StatusBadge status={selected.email_verified ? 'Verified' : 'Unverified'} />
                                        </dd>
                                    </div>
                                    <div className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">Mobile verification</dt>
                                        <dd className="col-span-2">
                                            <StatusBadge status={selected.mobile_verified ? 'Verified' : 'Unverified'} />
                                        </dd>
                                    </div>
                                    <div className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">OurNear verification</dt>
                                        <dd className="col-span-2">
                                            <StatusBadge status={selected.ournear_verified ? 'Verified' : 'Not verified'} />
                                            {selected.job_posting_approved_at ? <p className="mt-1 text-xs text-muted">{selected.job_posting_approved_at}</p> : null}
                                        </dd>
                                    </div>
                                    <div className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">Job posting approval</dt>
                                        <dd className="col-span-2">
                                            <StatusBadge status={selected.job_posting_approved ? 'Approved' : 'Not approved'} />
                                        </dd>
                                    </div>
                                    <div className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">Posting restriction</dt>
                                        <dd className="col-span-2">
                                            <StatusBadge status={selected.posting_restricted ? 'Restricted' : 'Not restricted'} />
                                            {selected.posting_restricted_at ? <p className="mt-1 text-xs text-muted">{selected.posting_restricted_at}</p> : null}
                                        </dd>
                                    </div>
                                </dl>
                                {can_manage ? (
                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            variant="secondary"
                                            onClick={() => router.patch(`/admin/employers/${selected.id}/posting-approval`, {})}
                                        >
                                            {selected.job_posting_approved ? 'Revoke posting approval' : 'Approve job posting'}
                                        </Button>
                                        <Button
                                            variant="secondary"
                                            onClick={() =>
                                                router.patch(`/admin/employers/${selected.id}/posting-restriction`, {
                                                    restricted: !selected.posting_restricted,
                                                })
                                            }
                                        >
                                            {selected.posting_restricted ? 'Remove posting restriction' : 'Restrict posting'}
                                        </Button>
                                    </div>
                                ) : null}
                                {can_manage ? <EmployerTagForm key={`tags-${selected.id}`} selected={selected} lookups={lookups} /> : null}
                            </div>
                        ) : null}
                        {tab === 'documents' ? (
                            <EmployerDocumentsPanel
                                employerId={selected.id}
                                documents={documents}
                                types={document_types}
                                statuses={document_statuses}
                                canManage={Boolean(can_manage)}
                            />
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
                            <ActivityFeed items={activities} empty="No account or recruitment activity is recorded for this employer yet." />
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
                        {tab === 'crm' ? (
                            <RelatedList
                                empty="No CRM leads are linked to this employer yet."
                                rows={crm.map((lead) => ({
                                    id: lead.id,
                                    title: lead.company_name,
                                    meta: `${lead.source_label ?? 'CRM'} · ${lead.opportunities?.[0]?.status_label ?? 'No opportunity'} · ${lead.assignee_name ?? 'Unassigned'}`,
                                    href: `/admin/crm?lead=${lead.id}`,
                                    label: 'Open CRM',
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

function EmployerCompanyForm({
    selected,
    lookups,
}: {
    selected: EmployerRow;
    lookups: Props['lookups'];
}) {
    const form = useForm({
        company_name: selected.company_name ?? '',
        contact_person: selected.contact_person ?? '',
        mobile_number: selected.mobile_number ?? '',
        industry: selected.industry ?? '',
        about_company: selected.about_company ?? '',
        company_size: selected.company_size ?? '',
        company_website: selected.company_website ?? '',
        company_details: selected.company_details ?? '',
        location_ids: selected.location_ids ?? [],
        benefits: (selected.benefits ?? []).join('\n'),
        socials: {
            linkedin: selected.socials?.linkedin ?? '',
            facebook: selected.socials?.facebook ?? '',
            twitter: selected.socials?.twitter ?? '',
            instagram: selected.socials?.instagram ?? '',
            youtube: selected.socials?.youtube ?? '',
        },
    });

    return (
        <form
            className="space-y-3 border-t border-line pt-4"
            onSubmit={(event) => {
                event.preventDefault();
                form.transform((data) => ({
                    ...data,
                    benefits: data.benefits
                        .split('\n')
                        .map((item) => item.trim())
                        .filter(Boolean),
                }));
                form.put(`/admin/employers/${selected.id}`, { preserveScroll: true });
            }}
        >
            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Edit company</h3>
            <Input
                value={form.data.company_name}
                onChange={(event) => form.setData('company_name', event.target.value)}
                placeholder="Company name"
                aria-label="Company name"
            />
            <Input
                value={form.data.industry}
                onChange={(event) => form.setData('industry', event.target.value)}
                placeholder="Industry"
                aria-label="Industry"
            />
            <Input
                value={form.data.company_size}
                onChange={(event) => form.setData('company_size', event.target.value)}
                placeholder="Company size"
                aria-label="Company size"
            />
            <Input
                value={form.data.company_website}
                onChange={(event) => form.setData('company_website', event.target.value)}
                placeholder="https://example.com"
                aria-label="Company website"
            />
            <textarea
                className="w-full rounded-md border border-line px-3 py-2 text-sm"
                value={form.data.about_company}
                onChange={(event) => form.setData('about_company', event.target.value)}
                placeholder="About company"
                aria-label="About company"
                rows={3}
            />
            <fieldset>
                <legend className="mb-2 text-sm font-medium text-ink">Company locations</legend>
                <div className="grid gap-2 sm:grid-cols-2">
                    {(lookups?.locations ?? []).map((location) => (
                        <label key={location.id} className="flex items-center gap-2 text-sm text-ink">
                            <input
                                type="checkbox"
                                checked={form.data.location_ids.includes(location.id)}
                                onChange={() => {
                                    const next = form.data.location_ids.includes(location.id)
                                        ? form.data.location_ids.filter((id) => id !== location.id)
                                        : [...form.data.location_ids, location.id];
                                    form.setData('location_ids', next);
                                }}
                            />
                            {location.name}
                        </label>
                    ))}
                </div>
            </fieldset>
            <textarea
                className="w-full rounded-md border border-line px-3 py-2 text-sm"
                value={form.data.benefits}
                onChange={(event) => form.setData('benefits', event.target.value)}
                placeholder="Benefits, one per line"
                aria-label="Benefits"
                rows={3}
            />
            <Input
                value={form.data.socials.linkedin ?? ''}
                onChange={(event) => form.setData('socials', { ...form.data.socials, linkedin: event.target.value })}
                placeholder="LinkedIn URL"
                aria-label="LinkedIn"
            />
            <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Saving…' : 'Save company'}
            </Button>
        </form>
    );
}

function EmployerTagForm({
    selected,
    lookups,
}: {
    selected: EmployerRow;
    lookups: Props['lookups'];
}) {
    const tagForm = useForm({
        tag_ids: (selected.tags ?? []).map((tag) => tag.id),
    });

    return (
        <section className="space-y-3 border-t border-line pt-4">
            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Flags / tags</h3>
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    tagForm.put(`/admin/employers/${selected.id}/tags`, { preserveScroll: true });
                }}
                className="space-y-2"
            >
                <div className="grid gap-2 sm:grid-cols-2">
                    {(lookups?.tags ?? []).map((tag) => (
                        <label key={tag.id} className="flex items-center gap-2 text-sm text-ink">
                            <input
                                type="checkbox"
                                checked={tagForm.data.tag_ids.includes(tag.id)}
                                disabled={!tag.is_active && !tagForm.data.tag_ids.includes(tag.id)}
                                onChange={() => {
                                    const next = tagForm.data.tag_ids.includes(tag.id)
                                        ? tagForm.data.tag_ids.filter((id) => id !== tag.id)
                                        : [...tagForm.data.tag_ids, tag.id];
                                    tagForm.setData('tag_ids', next);
                                }}
                            />
                            {tag.name}
                        </label>
                    ))}
                </div>
                <Button type="submit" variant="secondary" disabled={tagForm.processing}>
                    Save tags
                </Button>
            </form>
        </section>
    );
}

function EmployerDocumentsPanel({
    employerId,
    documents,
    types,
    statuses,
    canManage,
}: {
    employerId: number;
    documents: EmployerDocumentRow[];
    types: FileOption[];
    statuses: FileOption[];
    canManage: boolean;
}) {
    const upload = useForm({
        file: null as File | null,
        document_type: types[0]?.value ?? 'company_document',
        title: '',
        issue_date: '',
        expiry_date: '',
    });

    const submitUpload = (event: FormEvent) => {
        event.preventDefault();
        upload.post(`/admin/employers/${employerId}/documents`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => upload.reset('file', 'title', 'issue_date', 'expiry_date'),
        });
    };

    return (
        <div className="space-y-4">
            <p className="text-xs text-muted">
                Company document review is separate from job posting approval. Private files are not shown on the public
                company page.
            </p>
            {canManage ? (
                <form onSubmit={submitUpload} className="space-y-3 rounded-md border border-line p-3">
                    <p className="text-sm font-medium text-ink">Upload company document</p>
                    <Select
                        value={upload.data.document_type}
                        onChange={(event) => upload.setData('document_type', event.target.value)}
                        aria-label="Document type"
                    >
                        {types.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </Select>
                    <Input
                        value={upload.data.title}
                        onChange={(event) => upload.setData('title', event.target.value)}
                        placeholder="Title"
                        aria-label="Document title"
                    />
                    <input
                        type="file"
                        required
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        onChange={(event) => upload.setData('file', event.target.files?.[0] ?? null)}
                        aria-label="Company document file"
                    />
                    {upload.errors.file ? <p className="text-sm text-danger">{upload.errors.file}</p> : null}
                    <div className="grid gap-2 sm:grid-cols-2">
                        <Input
                            type="date"
                            value={upload.data.issue_date}
                            onChange={(event) => upload.setData('issue_date', event.target.value)}
                            aria-label="Issue date"
                        />
                        <Input
                            type="date"
                            value={upload.data.expiry_date}
                            onChange={(event) => upload.setData('expiry_date', event.target.value)}
                            aria-label="Expiry date"
                        />
                    </div>
                    <Button type="submit" variant="secondary" disabled={upload.processing}>
                        Upload document
                    </Button>
                </form>
            ) : null}
            {documents.length === 0 ? (
                <p className="text-sm text-muted">No company documents have been uploaded for this employer yet.</p>
            ) : (
                <ul className="space-y-3">
                    {documents.map((document) => (
                        <li key={document.id} className="rounded-md border border-line p-3 text-sm">
                            <p className="font-medium text-ink">{document.title}</p>
                            <p className="mt-1 text-xs text-muted">
                                {[
                                    recorded(document.business_id),
                                    document.document_type_label,
                                    document.original_filename,
                                    document.status_label,
                                    document.verification_status_label,
                                    document.expiry_state === 'expired'
                                        ? 'Expired'
                                        : document.expiry_state === 'expiring'
                                          ? 'Expiring soon'
                                          : null,
                                    document.issue_date || document.expiry_date
                                        ? `${document.issue_date ?? 'No issue date'} – ${document.expiry_date ?? 'No expiry'}`
                                        : null,
                                    document.created_at,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </p>
                            {document.review_notes ? (
                                <p className="mt-2 text-xs text-muted">Review notes: {document.review_notes}</p>
                            ) : null}
                            {document.verified_by_name ? (
                                <p className="mt-1 text-xs text-muted">
                                    Reviewer: {document.verified_by_name}
                                    {document.verified_at ? ` · ${document.verified_at}` : ''}
                                </p>
                            ) : null}
                            <div className="mt-3 flex flex-wrap gap-3">
                                <a href={document.view_url} className="font-medium text-brand" target="_blank" rel="noreferrer">
                                    View
                                </a>
                                <a href={document.download_url} className="font-medium text-brand">
                                    Download
                                </a>
                            </div>
                            {canManage ? (
                                <EmployerDocumentActions
                                    employerId={employerId}
                                    document={document}
                                    types={types}
                                    statuses={statuses}
                                />
                            ) : null}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function EmployerDocumentActions({
    employerId,
    document,
    types,
    statuses,
}: {
    employerId: number;
    document: EmployerDocumentRow;
    types: FileOption[];
    statuses: FileOption[];
}) {
    const review = useForm({
        action: 'verify',
        review_notes: document.review_notes ?? '',
    });
    const meta = useForm({
        document_type: document.document_type ?? 'company_document',
        title: document.title,
        status: document.status ?? 'active',
        issue_date: document.issue_date ?? '',
        expiry_date: document.expiry_date ?? '',
    });

    return (
        <div className="mt-3 space-y-3 border-t border-line pt-3">
            <form
                className="grid gap-2 sm:grid-cols-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    meta.put(`/admin/employers/${employerId}/documents/${document.id}`, { preserveScroll: true });
                }}
            >
                <Select
                    value={meta.data.document_type}
                    onChange={(event) => meta.setData('document_type', event.target.value)}
                    aria-label={`Document type ${document.id}`}
                >
                    {types.map((type) => (
                        <option key={type.value} value={type.value}>
                            {type.label}
                        </option>
                    ))}
                </Select>
                <Input
                    value={meta.data.title}
                    onChange={(event) => meta.setData('title', event.target.value)}
                    aria-label={`Document title ${document.id}`}
                />
                <Select
                    value={meta.data.status}
                    onChange={(event) => meta.setData('status', event.target.value)}
                    aria-label={`Document status ${document.id}`}
                >
                    {statuses.map((status) => (
                        <option key={status.value} value={status.value}>
                            {status.label}
                        </option>
                    ))}
                </Select>
                <Button type="submit" variant="secondary" disabled={meta.processing}>
                    Save details
                </Button>
            </form>
            <form
                className="space-y-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    const data = new FormData(event.currentTarget);
                    router.post(`/admin/employers/${employerId}/documents/${document.id}/replace`, data, {
                        forceFormData: true,
                        preserveScroll: true,
                    });
                }}
            >
                <input
                    name="file"
                    type="file"
                    required
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                    aria-label={`Replace company document ${document.id}`}
                />
                <Button type="submit" variant="secondary">
                    Replace file
                </Button>
            </form>
            <form
                className="space-y-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    review.patch(`/admin/employers/${employerId}/documents/${document.id}/verification`, {
                        preserveScroll: true,
                    });
                }}
            >
                <Select
                    value={review.data.action}
                    onChange={(event) => review.setData('action', event.target.value)}
                    aria-label={`Review action ${document.id}`}
                >
                    <option value="verify">Verify</option>
                    <option value="reject">Reject</option>
                </Select>
                <textarea
                    className="w-full rounded-md border border-line px-3 py-2 text-sm"
                    value={review.data.review_notes}
                    onChange={(event) => review.setData('review_notes', event.target.value)}
                    placeholder="Review notes"
                    aria-label={`Review notes ${document.id}`}
                    rows={2}
                />
                <Button type="submit" variant="secondary" disabled={review.processing}>
                    Save review
                </Button>
            </form>
            <Button
                type="button"
                variant="secondary"
                onClick={() => {
                    if (window.confirm('Delete this company document?')) {
                        router.delete(`/admin/employers/${employerId}/documents/${document.id}`, { preserveScroll: true });
                    }
                }}
            >
                Delete
            </Button>
        </div>
    );
}
