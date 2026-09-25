import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import RecruitmentChain from '@/Components/Admin/RecruitmentChain';
import {
    Button,
    DataTable,
    Drawer,
    FilterBar,
    Input,
    Modal,
    SearchBar,
    Select,
    StatusBadge,
    Tabs,
    Textarea,
    Timeline,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import { recorded } from '@/lib/recorded';
import type { Paginator } from '@/types/jobs';
import type { RecruitmentChainStep } from '@/Components/Admin/RecruitmentChain';
import type { EmployeeListItem } from '@/types/placements';

type EmployeeDetail = EmployeeListItem & {
    created_by: string | null;
    expected_joining_date: string | null;
    actual_joining_date: string | null;
    joining_notes: string | null;
    can_request_replacement?: boolean;
    can_create_invoice?: boolean;
    chain?: RecruitmentChainStep[];
    offer?: { offered_at: string | null; salary: string | null; notes: string | null } | null;
    documents?: Array<{ label: string; available: boolean; href: string | null; note: string | null }>;
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
        candidate_id?: string;
        employment_status?: string;
        date_from: string;
        date_to: string;
        employee: string;
    };
    options: {
        employers: Array<{ id: number; label: string }>;
        jobs: Array<{ id: number; title: string; business_id: string | null }>;
        candidates?: Array<{ id: number; label: string }>;
        employment_statuses?: string[];
    };
    replacementReasons: string[];
};

function activityTitle(activity: EmployeeDetail['activities'][number]): string {
    if (activity.from_status && activity.to_status) {
        return `Status: ${activity.from_status} → ${activity.to_status}`;
    }

    return activity.body || activity.type.replace(/_/g, ' ');
}

export default function EmployeesIndex({ employees, selected, filters, options, replacementReasons }: Props) {
    const [tab, setTab] = useState('overview');
    const [requestOpen, setRequestOpen] = useState(false);
    const [invoiceOpen, setInvoiceOpen] = useState(false);
    const [reason, setReason] = useState('');
    const [notes, setNotes] = useState('');
    const [amount, setAmount] = useState('');
    const [gstRate, setGstRate] = useState('0');
    const [billedAt, setBilledAt] = useState(new Date().toISOString().slice(0, 10));
    const [dueAt, setDueAt] = useState(new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10));

    const visit = (overrides: Record<string, string | undefined> = {}, replaceAll = false) => {
        const next = replaceAll ? overrides : { ...filters, ...overrides };
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
        { key: 'id', header: 'Employee ID', className: 'font-medium text-ink', render: (row) => recorded(row.business_id) },
        { key: 'candidate', header: 'Candidate', className: 'font-medium text-ink', render: (row) => recorded(row.candidate.name) },
        { key: 'employer', header: 'Employer', render: (row) => recorded(row.employer?.company_name) },
        { key: 'job', header: 'Job', render: (row) => recorded(row.job?.title) },
        { key: 'placement', header: 'Placement', render: (row) => recorded(row.placement?.business_id) },
        { key: 'joined', header: 'Joining date', render: (row) => recorded(row.joined_at) },
        {
            key: 'status',
            header: 'Employment status',
            render: (row) => <StatusBadge status={row.employment_status ?? 'Joined'} />,
        },
        { key: 'created', header: 'Created date', render: (row) => recorded(row.created_at) },
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
                <FilterBar className="md:grid-cols-4 xl:grid-cols-6" onSubmit={filter}>
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
                    <Select name="candidate_id" defaultValue={filters.candidate_id ?? ''} aria-label="Candidate">
                        <option value="">All candidates</option>
                        {(options.candidates ?? []).map((candidate) => (
                            <option key={candidate.id} value={candidate.id}>
                                {candidate.label}
                            </option>
                        ))}
                    </Select>
                    <Select name="employment_status" defaultValue={filters.employment_status ?? ''} aria-label="Employment status">
                        <option value="">Any employment status</option>
                        {(options.employment_statuses ?? ['Joined']).map((status) => (
                            <option key={status} value={status}>
                                {status}
                            </option>
                        ))}
                    </Select>
                    <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="Joined from" />
                    <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="Joined to" />
                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit">Filter</Button>
                        <Button type="button" variant="secondary" onClick={() => visit({}, true)}>
                            Reset
                        </Button>
                    </div>
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
                                { id: 'identity', label: 'Identity' },
                                { id: 'lineage', label: 'Lineage' },
                                { id: 'documents', label: 'Documents' },
                                { id: 'replacement', label: 'Replacement' },
                                { id: 'finance', label: 'Finance' },
                                { id: 'timeline', label: 'Activity' },
                            ]}
                            active={tab}
                            onChange={setTab}
                        />
                        {tab === 'overview' ? (
                            <dl className="grid gap-2 text-sm">
                                {[
                                    ['Employee ID', recorded(selected.business_id)],
                                    ['Candidate', recorded(selected.candidate.name)],
                                    ['Employer', recorded(selected.employer?.company_name)],
                                    ['Job', recorded(selected.job?.title)],
                                    ['Placement', recorded(selected.placement?.business_id)],
                                    ['Offer', recorded(selected.offer?.offered_at)],
                                    ['Joined', recorded(selected.joined_at)],
                                    ['Expected joining', recorded(selected.expected_joining_date)],
                                    ['Actual joining', recorded(selected.actual_joining_date)],
                                    ['Employment status', recorded(selected.employment_status)],
                                    ['Created', recorded(selected.created_at)],
                                ].map(([label, value]) => (
                                    <div key={label} className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">{label}</dt>
                                        <dd className="col-span-2 text-ink">{value}</dd>
                                    </div>
                                ))}
                            </dl>
                        ) : null}
                        {tab === 'identity' ? (
                            <div className="space-y-3 text-sm">
                                <p>This employee is the joined record of the same candidate/user. It is not a separate person.</p>
                                <RecruitmentChain steps={selected.chain ?? []} />
                            </div>
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
                        {tab === 'documents' ? (
                            <div className="space-y-3 text-sm">
                                {(selected.documents ?? []).map((item) => (
                                    <div key={item.label} className="rounded-md bg-surface px-3 py-2">
                                        <p className="font-medium text-ink">{item.label}</p>
                                        {item.available && item.href ? (
                                            <a href={item.href} className="font-medium text-brand">
                                                Download
                                            </a>
                                        ) : (
                                            <p className="text-muted">{item.note ?? 'Not recorded yet'}</p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        ) : null}
                        {tab === 'replacement' ? (
                            <div className="space-y-3 text-sm">
                                {selected.replacement?.role === 'replacement' ? (
                                    <p>
                                        This employee joined as a replacement for{' '}
                                        {selected.replacement.resulting?.original_employee_name ?? 'the original employee'}.
                                    </p>
                                ) : (
                                    <p>This employee is the original joined placement record.</p>
                                )}
                                {selected.replacement?.originating ? (
                                    <p>
                                        Replacement case{' '}
                                        <Link href={`/admin/replacements?replacement=${selected.replacement.originating.id}`} className="font-medium text-brand">
                                            {selected.replacement.originating.business_id}
                                        </Link>{' '}
                                        ({selected.replacement.originating.status})
                                    </p>
                                ) : null}
                                {selected.replacement?.resulting ? (
                                    <p>
                                        Originating replacement{' '}
                                        <Link href={`/admin/replacements?replacement=${selected.replacement.resulting.id}`} className="font-medium text-brand">
                                            {selected.replacement.resulting.business_id}
                                        </Link>
                                    </p>
                                ) : null}
                                <Button disabled={!selected.can_request_replacement} onClick={() => setRequestOpen(true)}>
                                    Request replacement
                                </Button>
                                {!selected.can_request_replacement ? (
                                    <p className="text-muted">
                                        A replacement can only be requested from a joined employee who does not already have an open or completed replacement case.
                                    </p>
                                ) : null}
                            </div>
                        ) : null}
                        {tab === 'finance' ? (
                            <div className="space-y-3 text-sm">
                                <p>Recruitment fees are billed after joining. This is not employee salary.</p>
                                {selected.invoice ? (
                                    <p>
                                        Invoice{' '}
                                        <Link href={`/admin/finance?invoice=${selected.invoice.id}`} className="font-medium text-brand">
                                            {selected.invoice.business_id}
                                        </Link>{' '}
                                        · {selected.invoice.status} · {selected.invoice.currency} {selected.invoice.total_amount}
                                        {selected.invoice.is_overdue ? ' · Overdue' : ''}
                                    </p>
                                ) : (
                                    <p className="text-muted">No invoice has been created for this joined employee.</p>
                                )}
                                <Button disabled={!selected.can_create_invoice} onClick={() => setInvoiceOpen(true)}>
                                    Create invoice
                                </Button>
                            </div>
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
            <Modal
                open={requestOpen}
                title="Request replacement"
                onClose={() => setRequestOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setRequestOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            disabled={!reason}
                            onClick={() => {
                                if (!selected || !reason) {
                                    return;
                                }
                                router.post(`/admin/employees/${selected.id}/replacements`, { reason, notes });
                                setRequestOpen(false);
                            }}
                        >
                            Request replacement
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left text-sm">
                    <p>The original employee and placement remain historical records. The replacement candidate will follow a new recruitment path.</p>
                    <Select value={reason} onChange={(event) => setReason(event.target.value)} aria-label="Replacement reason">
                        <option value="">Select a reason</option>
                        {replacementReasons.map((item) => (
                            <option key={item} value={item}>
                                {item}
                            </option>
                        ))}
                    </Select>
                    <Textarea value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Notes (optional)" />
                </div>
            </Modal>
            <Modal
                open={invoiceOpen}
                title="Create recruitment invoice"
                onClose={() => setInvoiceOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setInvoiceOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            disabled={!amount}
                            onClick={() => {
                                if (!selected || !amount) {
                                    return;
                                }
                                router.post(`/admin/employees/${selected.id}/invoices`, {
                                    amount,
                                    gst_rate: gstRate,
                                    billed_at: billedAt,
                                    due_at: dueAt,
                                    notes,
                                });
                                setInvoiceOpen(false);
                            }}
                        >
                            Save draft invoice
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left text-sm">
                    <p>The billed amount is stored on the invoice and will not change if pricing changes later. GST is optional.</p>
                    <Input value={amount} onChange={(event) => setAmount(event.target.value)} placeholder="Recruitment fee (INR)" />
                    <Input value={gstRate} onChange={(event) => setGstRate(event.target.value)} placeholder="GST rate % (0 if none)" />
                    <Input type="date" value={billedAt} onChange={(event) => setBilledAt(event.target.value)} aria-label="Billing date" />
                    <Input type="date" value={dueAt} onChange={(event) => setDueAt(event.target.value)} aria-label="Due date" />
                    <Textarea value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Notes (optional)" />
                </div>
            </Modal>
        </AdminLayout>
    );
}
