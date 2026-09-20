import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
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
import type { Paginator } from '@/types/jobs';
import type { FinanceSummary, InvoiceDetail, InvoiceListItem, PaymentItem } from '@/types/finance';

type Props = {
    invoices: Paginator<InvoiceListItem>;
    selected: InvoiceDetail | null;
    summary: FinanceSummary;
    recentPayments: PaymentItem[];
    filters: {
        search: string;
        employer_id: string;
        job_id: string;
        status: string;
        overdue: string;
        date_from: string;
        date_to: string;
        invoice: string;
    };
    options: {
        employers: Array<{ id: number; label: string }>;
        jobs: Array<{ id: number; title: string; business_id: string | null }>;
        statuses: string[];
        methods: string[];
    };
    statuses: string[];
    methods: string[];
};

function money(currency: string, value: string): string {
    return `${currency} ${value}`;
}

function activityTitle(activity: InvoiceDetail['activities'][number]): string {
    if (activity.from_status && activity.to_status) {
        return `Status: ${activity.from_status} → ${activity.to_status}`;
    }

    return activity.body || activity.type.replace(/_/g, ' ');
}

export default function FinanceIndex({
    invoices,
    selected,
    summary,
    recentPayments,
    filters,
    options,
    statuses,
    methods,
}: Props) {
    const [tab, setTab] = useState('overview');
    const [paymentOpen, setPaymentOpen] = useState(false);
    const [cancelOpen, setCancelOpen] = useState(false);
    const [amount, setAmount] = useState('');
    const [paidAt, setPaidAt] = useState(new Date().toISOString().slice(0, 10));
    const [method, setMethod] = useState(methods[0] ?? '');
    const [reference, setReference] = useState('');
    const [notes, setNotes] = useState('');

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/finance', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ invoice: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<string, string>;
        visit({
            ...data,
            overdue: data.overdue ? '1' : '',
            invoice: filters.invoice,
        });
    };

    const columns: Array<DataTableColumn<InvoiceListItem>> = [
        { key: 'id', header: 'Invoice ID', className: 'font-medium text-ink', render: (row) => row.business_id ?? '—' },
        { key: 'employer', header: 'Employer', render: (row) => row.employer?.company_name ?? '—' },
        { key: 'employee', header: 'Employee', render: (row) => row.employee?.name ?? row.employee?.business_id ?? '—' },
        { key: 'total', header: 'Total', render: (row) => money(row.currency, row.total_amount) },
        { key: 'outstanding', header: 'Outstanding', render: (row) => money(row.currency, row.outstanding_amount) },
        {
            key: 'status',
            header: 'Status',
            render: (row) => (
                <span className="inline-flex items-center gap-2">
                    <StatusBadge status={row.status} />
                    {row.is_overdue ? <StatusBadge status="Overdue" /> : null}
                </span>
            ),
        },
        { key: 'due', header: 'Due', render: (row) => row.due_at ?? '—' },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button variant="ghost" onClick={() => visit({ invoice: String(row.id) })}>
                    View
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout title="Payments / Finance">
            <div
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Payments / Finance</h1>
                    <p className="mt-1 text-sm text-muted">
                        Recruitment fees are billed after a candidate joins. Create an invoice from the employee record. Joining
                        is not the same as payment received.
                    </p>
                </div>
                <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {[
                        ['Billed', summary.billed],
                        ['Received', summary.received],
                        ['Outstanding', summary.outstanding],
                        ['Overdue', summary.overdue],
                    ].map(([label, value]) => (
                        <div key={label} className="rounded-xl border border-line bg-white p-4">
                            <p className="text-xs uppercase tracking-wide text-muted">{label}</p>
                            <p className="mt-1 text-lg font-semibold text-ink">{money(summary.currency, value)}</p>
                        </div>
                    ))}
                </div>
                <FilterBar onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Invoice, employer, employee, payment ID" />
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
                    <Select name="status" defaultValue={filters.status} aria-label="Invoice status">
                        <option value="">All statuses</option>
                        {statuses.map((status) => (
                            <option key={status} value={status}>
                                {status}
                            </option>
                        ))}
                    </Select>
                    <label className="flex items-center gap-2 text-sm text-ink">
                        <input type="checkbox" name="overdue" value="1" defaultChecked={filters.overdue === '1'} />
                        Overdue only
                    </label>
                    <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="Billed from" />
                    <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="Billed to" />
                    <Button type="submit">Filter</Button>
                </FilterBar>
                <DataTable
                    columns={columns}
                    rows={invoices.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No invoices match these filters"
                    emptyDescription="Create an invoice from a joined employee. Amounts are stored historically and do not follow later pricing changes."
                    onRowClick={(row) => visit({ invoice: String(row.id) })}
                    selectedKey={selected?.id ?? null}
                />
                <Pagination links={invoices.links} />
                <div className="mt-8">
                    <h2 className="text-base font-semibold text-ink">Recent payments</h2>
                    {recentPayments.length === 0 ? (
                        <p className="mt-2 text-sm text-muted">No payments recorded yet.</p>
                    ) : (
                        <ul className="mt-3 space-y-2">
                            {recentPayments.map((payment) => (
                                <li key={payment.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                    <span className="font-medium text-ink">{payment.business_id}</span>
                                    {' · '}
                                    {money(payment.currency, payment.amount)}
                                    {' · '}
                                    {payment.method}
                                    {' · invoice '}
                                    {payment.invoice_business_id}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>

            <Drawer open={selected !== null} title={selected?.business_id ?? 'Invoice'} size="xl" onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-4">
                        <Tabs
                            tabs={[
                                { id: 'overview', label: 'Overview' },
                                { id: 'lineage', label: 'Lineage' },
                                { id: 'payments', label: 'Payments' },
                                { id: 'timeline', label: 'Timeline' },
                            ]}
                            active={tab}
                            onChange={setTab}
                        />
                        {tab === 'overview' ? (
                            <div className="space-y-4 text-sm">
                                <dl className="grid gap-2">
                                    {[
                                        ['Invoice ID', selected.business_id ?? '—'],
                                        ['Status', selected.status + (selected.is_overdue ? ' (overdue)' : '')],
                                        ['Fee', money(selected.currency, selected.amount)],
                                        ['GST rate', `${selected.gst_rate}%`],
                                        ['GST amount', money(selected.currency, selected.gst_amount)],
                                        ['Total billed', money(selected.currency, selected.total_amount)],
                                        ['Received', money(selected.currency, selected.received_amount)],
                                        ['Outstanding', money(selected.currency, selected.outstanding_amount)],
                                        ['Billed', selected.billed_at ?? '—'],
                                        ['Due', selected.due_at ?? '—'],
                                        ['Issued', selected.issued_at ?? '—'],
                                        ['Replacement billing', selected.is_replacement_billing ? 'Yes' : 'No'],
                                        ['Notes', selected.notes || '—'],
                                    ].map(([label, value]) => (
                                        <div key={label} className="grid grid-cols-3 gap-2">
                                            <dt className="text-muted">{label}</dt>
                                            <dd className="col-span-2 text-ink">{value}</dd>
                                        </div>
                                    ))}
                                </dl>
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        disabled={!selected.can_issue}
                                        onClick={() => router.post(`/admin/invoices/${selected.id}/issue`)}
                                    >
                                        Issue invoice
                                    </Button>
                                    <Button disabled={!selected.can_record_payment} onClick={() => setPaymentOpen(true)}>
                                        Record payment
                                    </Button>
                                    <Button variant="danger" disabled={!selected.can_cancel} onClick={() => setCancelOpen(true)}>
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        ) : null}
                        {tab === 'lineage' ? (
                            <ul className="space-y-2 text-sm">
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
                                    Employee{' '}
                                    {selected.employee?.id ? (
                                        <Link href={`/admin/employees?employee=${selected.employee.id}`} className="font-medium text-brand">
                                            {selected.employee.business_id ?? selected.employee.name}
                                        </Link>
                                    ) : (
                                        '—'
                                    )}
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
                                <li>Application {selected.application?.business_id ?? '—'}</li>
                                <li>Submission {selected.submission?.business_id ?? '—'}</li>
                                <li>
                                    Replacement{' '}
                                    {selected.replacement?.id ? (
                                        <Link href={`/admin/replacements?replacement=${selected.replacement.id}`} className="font-medium text-brand">
                                            {selected.replacement.business_id}
                                        </Link>
                                    ) : (
                                        'Not replacement billing'
                                    )}
                                </li>
                            </ul>
                        ) : null}
                        {tab === 'payments' ? (
                            <ul className="space-y-2 text-sm">
                                {selected.payments.length === 0 ? (
                                    <li className="text-muted">No payments recorded against this invoice.</li>
                                ) : (
                                    selected.payments.map((payment) => (
                                        <li key={payment.id} className="rounded-md bg-surface px-3 py-2">
                                            <p className="font-medium text-ink">
                                                {payment.business_id} · {money(payment.currency, payment.amount)}
                                            </p>
                                            <p className="text-muted">
                                                {payment.method} · {payment.paid_at} · {payment.reference || 'No reference'}
                                            </p>
                                        </li>
                                    ))
                                )}
                            </ul>
                        ) : null}
                        {tab === 'timeline' ? (
                            <Timeline
                                items={(selected.activities ?? []).map((activity) => ({
                                    id: activity.id,
                                    title: activityTitle(activity),
                                    meta: `${activity.actor_name ?? 'System'} · ${activity.created_at ?? ''}`,
                                }))}
                                empty="No finance activity yet."
                            />
                        ) : null}
                    </div>
                ) : null}
            </Drawer>

            <Modal
                open={paymentOpen}
                title="Record payment"
                onClose={() => setPaymentOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setPaymentOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            disabled={!amount}
                            onClick={() => {
                                if (!selected) {
                                    return;
                                }
                                router.post(`/admin/invoices/${selected.id}/payments`, {
                                    amount,
                                    paid_at: paidAt,
                                    method,
                                    reference,
                                    notes,
                                });
                                setPaymentOpen(false);
                            }}
                        >
                            Save payment
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left text-sm">
                    <p>Outstanding {selected ? money(selected.currency, selected.outstanding_amount) : ''}.</p>
                    <Input value={amount} onChange={(event) => setAmount(event.target.value)} placeholder="Amount received" />
                    <Input type="date" value={paidAt} onChange={(event) => setPaidAt(event.target.value)} aria-label="Payment date" />
                    <Select value={method} onChange={(event) => setMethod(event.target.value)} aria-label="Payment method">
                        {methods.map((item) => (
                            <option key={item} value={item}>
                                {item}
                            </option>
                        ))}
                    </Select>
                    <Input value={reference} onChange={(event) => setReference(event.target.value)} placeholder="Reference (optional)" />
                    <Textarea value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Notes" />
                </div>
            </Modal>

            <Modal
                open={cancelOpen}
                title="Cancel this invoice?"
                onClose={() => setCancelOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setCancelOpen(false)}>
                            Keep invoice
                        </Button>
                        <Button
                            variant="danger"
                            onClick={() => {
                                if (!selected) {
                                    return;
                                }
                                router.post(`/admin/invoices/${selected.id}/cancel`, { notes });
                                setCancelOpen(false);
                            }}
                        >
                            Cancel invoice
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left text-sm">
                    <p>Issued amounts remain historically stored. Payments already recorded prevent cancellation.</p>
                    <Textarea value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Cancellation notes" />
                </div>
            </Modal>
        </AdminLayout>
    );
}
