import { router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import RecruitmentChain from '@/Components/Admin/RecruitmentChain';
import {
    Button,
    ConfirmDialog,
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
import type { FinanceDashboard, FinanceSummary, InvoiceDetail, InvoiceListItem, PaymentItem } from '@/types/finance';

type Props = {
    invoices: Paginator<InvoiceListItem>;
    payments: Paginator<PaymentItem>;
    selected: InvoiceDetail | null;
    selectedPayment: PaymentItem | null;
    summary: FinanceSummary;
    dashboard: FinanceDashboard;
    recentPayments: PaymentItem[];
    filters: {
        search: string;
        employer_id: string;
        job_id: string;
        candidate_id: string;
        status: string;
        invoice_type: string;
        payment_status: string;
        overdue: string;
        awaiting_payment: string;
        date_from: string;
        date_to: string;
        view: string;
        invoice: string;
        payment: string;
    };
    options: {
        employers: Array<{ id: number; label: string }>;
        jobs: Array<{ id: number; title: string; business_id: string | null }>;
        candidates?: Array<{ id: number; label: string }>;
        statuses: string[];
        invoice_types?: string[];
        methods: string[];
        payment_statuses?: Array<{ value: string; available: boolean }>;
    };
    statuses: string[];
    methods: string[];
    can_manage: boolean;
};

function money(currency: string, value: string): string {
    return `${currency} ${value}`;
}

function activityTitle(activity: { from_status: string | null; to_status: string | null; body: string | null; type: string }): string {
    if (activity.from_status && activity.to_status) {
        return `Status: ${activity.from_status} → ${activity.to_status}`;
    }

    return activity.body || activity.type.replace(/_/g, ' ');
}

export default function FinanceIndex({
    invoices,
    payments,
    selected,
    selectedPayment,
    dashboard,
    filters,
    options,
    statuses,
    methods,
    can_manage,
}: Props) {
    const [tab, setTab] = useState('overview');
    const [paymentTab, setPaymentTab] = useState('overview');
    const [paymentOpen, setPaymentOpen] = useState(false);
    const [cancelOpen, setCancelOpen] = useState(false);
    const [issueOpen, setIssueOpen] = useState(false);
    const [amount, setAmount] = useState('');
    const [paidAt, setPaidAt] = useState(new Date().toISOString().slice(0, 10));
    const [method, setMethod] = useState(methods[0] ?? '');
    const [reference, setReference] = useState('');
    const [notes, setNotes] = useState('');
    const view = filters.view === 'payments' ? 'payments' : 'invoices';

    const visit = (overrides: Record<string, string | undefined> = {}, replaceAll = false) => {
        const next = replaceAll ? { view, ...overrides } : { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/finance', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ invoice: '', payment: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget).entries()) as Record<string, string>;
        visit({
            ...data,
            overdue: data.overdue ? '1' : '',
            awaiting_payment: data.awaiting_payment ? '1' : '',
            invoice: filters.invoice,
            payment: filters.payment,
            view,
        });
    };

    const invoiceColumns: Array<DataTableColumn<InvoiceListItem>> = [
        { key: 'id', header: 'Invoice ID', className: 'font-medium text-ink', render: (row) => recorded(row.business_id) },
        { key: 'candidate', header: 'Candidate / customer', render: (row) => recorded(row.candidate?.name ?? row.employee?.name) },
        { key: 'employer', header: 'Employer', render: (row) => recorded(row.employer?.company_name) },
        { key: 'application', header: 'Application', render: (row) => recorded(row.application?.business_id) },
        { key: 'placement', header: 'Placement', render: (row) => recorded(row.placement?.business_id) },
        { key: 'type', header: 'Invoice type', render: (row) => recorded(row.invoice_type) },
        { key: 'total', header: 'Amount', render: (row) => money(row.currency, row.total_amount) },
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
        { key: 'billed', header: 'Invoice date', render: (row) => recorded(row.billed_at) },
        { key: 'due', header: 'Due date', render: (row) => recorded(row.due_at) },
        { key: 'created', header: 'Created date', render: (row) => recorded(row.created_at) },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button variant="ghost" onClick={() => visit({ invoice: String(row.id), payment: '', view: 'invoices' })}>
                    View
                </Button>
            ),
        },
    ];

    const paymentColumns: Array<DataTableColumn<PaymentItem>> = [
        { key: 'id', header: 'Payment ID', className: 'font-medium text-ink', render: (row) => recorded(row.business_id) },
        { key: 'invoice', header: 'Invoice', render: (row) => recorded(row.invoice_business_id) },
        { key: 'candidate', header: 'Candidate / customer', render: (row) => recorded(row.candidate?.name) },
        {
            key: 'context',
            header: 'Application / placement',
            render: (row) => recorded(row.application?.business_id ?? row.placement?.business_id),
        },
        { key: 'amount', header: 'Amount', render: (row) => money(row.currency, row.amount) },
        { key: 'status', header: 'Payment status', render: (row) => <StatusBadge status={row.status ?? 'Recorded'} /> },
        { key: 'method', header: 'Payment method', render: (row) => recorded(row.method) },
        { key: 'reference', header: 'Transaction ID', render: (row) => recorded(row.reference) },
        { key: 'gateway', header: 'Gateway reference', render: () => 'Not available in V1.1' },
        { key: 'paid', header: 'Payment date', render: (row) => recorded(row.paid_at) },
        { key: 'created', header: 'Created date', render: (row) => recorded(row.created_at) },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button variant="ghost" onClick={() => visit({ payment: String(row.id), invoice: '', view: 'payments' })}>
                    View
                </Button>
            ),
        },
    ];

    const cards: Array<{ key: string; label: string; value: string; hint?: string; onClick?: () => void }> = [
        { key: 'total', label: 'Total invoices', value: String(dashboard.total_invoices), onClick: () => visit({ view: 'invoices', awaiting_payment: '', status: '', payment: '', invoice: '' }) },
        { key: 'pending', label: 'Pending', value: String(dashboard.pending), hint: 'Issued + Partially Paid invoices', onClick: () => visit({ view: 'invoices', awaiting_payment: '1', status: '', payment: '', invoice: '' }) },
        { key: 'paid', label: 'Paid', value: String(dashboard.paid), onClick: () => visit({ view: 'invoices', status: 'Paid', awaiting_payment: '', payment: '', invoice: '' }) },
        { key: 'failed', label: 'Failed', value: String(dashboard.failed), hint: dashboard.failed_available ? undefined : 'Not available in V1.1' },
        { key: 'refunded', label: 'Refunded', value: String(dashboard.refunded), hint: dashboard.refunded_available ? undefined : 'Not available in V1.1' },
        { key: 'cancelled', label: 'Cancelled', value: String(dashboard.cancelled), onClick: () => visit({ view: 'invoices', status: 'Cancelled', awaiting_payment: '', payment: '', invoice: '' }) },
        { key: 'invoiced', label: 'Total invoiced amount', value: money(dashboard.currency, dashboard.total_invoiced_amount) },
        { key: 'paid_amount', label: 'Total paid amount', value: money(dashboard.currency, dashboard.total_paid_amount) },
        { key: 'pending_amount', label: 'Total pending amount', value: money(dashboard.currency, dashboard.total_pending_amount) },
    ];

    const drawerOpen = selected !== null || selectedPayment !== null;

    return (
        <AdminLayout title="Payments / Finance">
            <div
                className={drawerOpen ? 'pointer-events-none' : undefined}
                inert={drawerOpen ? true : undefined}
                aria-hidden={drawerOpen ? true : undefined}
            >
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Payments / Finance</h1>
                    <p className="mt-1 text-sm text-muted">
                        Recruitment fees are billed after a candidate joins. Opening or editing a record does not mark a payment as
                        paid. Joining is not the same as payment received.
                    </p>
                    <p className="mt-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-ink">
                        {dashboard.gateway_message}. Recorded payments are administrator entries, not gateway confirmations.
                    </p>
                </div>
                <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {cards.map((card) => (
                        <button
                            key={card.key}
                            type="button"
                            disabled={!card.onClick}
                            onClick={card.onClick}
                            className="rounded-xl border border-line bg-white p-4 text-left disabled:cursor-default"
                        >
                            <p className="text-xs uppercase tracking-wide text-muted">{card.label}</p>
                            <p className="mt-1 text-lg font-semibold text-ink">{card.value}</p>
                            {card.hint ? <p className="mt-1 text-xs text-muted">{card.hint}</p> : null}
                        </button>
                    ))}
                </div>
                <Tabs
                    tabs={[
                        { id: 'invoices', label: `Invoices (${invoices.total})` },
                        { id: 'payments', label: `Payments (${payments.total})` },
                    ]}
                    active={view}
                    onChange={(next) => visit({ view: next, invoice: '', payment: '' })}
                />
                <FilterBar onSubmit={filter}>
                    <SearchBar
                        defaultValue={filters.search}
                        placeholder={view === 'payments' ? 'Payment, invoice, candidate, reference' : 'Invoice, candidate, employer, payment ID'}
                    />
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
                    <Select name="candidate_id" defaultValue={filters.candidate_id} aria-label="Candidate">
                        <option value="">All candidates</option>
                        {(options.candidates ?? []).map((candidate) => (
                            <option key={candidate.id} value={candidate.id}>
                                {candidate.label}
                            </option>
                        ))}
                    </Select>
                    {view === 'invoices' ? (
                        <>
                            <Select name="status" defaultValue={filters.status} aria-label="Invoice status">
                                <option value="">All statuses</option>
                                {statuses.map((status) => (
                                    <option key={status} value={status}>
                                        {status}
                                    </option>
                                ))}
                            </Select>
                            <Select name="invoice_type" defaultValue={filters.invoice_type} aria-label="Invoice type">
                                <option value="">All invoice types</option>
                                {(options.invoice_types ?? []).map((type) => (
                                    <option key={type} value={type}>
                                        {type}
                                    </option>
                                ))}
                            </Select>
                            <label className="flex items-center gap-2 text-sm text-ink">
                                <input type="checkbox" name="awaiting_payment" value="1" defaultChecked={filters.awaiting_payment === '1'} />
                                Pending payments
                            </label>
                            <label className="flex items-center gap-2 text-sm text-ink">
                                <input type="checkbox" name="overdue" value="1" defaultChecked={filters.overdue === '1'} />
                                Overdue only
                            </label>
                        </>
                    ) : (
                        <Select name="payment_status" defaultValue={filters.payment_status} aria-label="Payment status">
                            <option value="">All payment statuses</option>
                            {(options.payment_statuses ?? []).map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.available ? status.value : `${status.value} (not in V1.1)`}
                                </option>
                            ))}
                        </Select>
                    )}
                    <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label={view === 'payments' ? 'Paid from' : 'Invoice from'} />
                    <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label={view === 'payments' ? 'Paid to' : 'Invoice to'} />
                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit">Filter</Button>
                        <Button type="button" variant="secondary" onClick={() => visit({ view }, true)}>
                            Reset
                        </Button>
                    </div>
                </FilterBar>
                {view === 'invoices' ? (
                    <>
                        <DataTable
                            columns={invoiceColumns}
                            rows={invoices.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No invoices match these filters"
                            emptyDescription="Create an invoice from a joined employee. Amounts are stored historically and do not follow later pricing changes."
                            onRowClick={(row) => visit({ invoice: String(row.id), payment: '', view: 'invoices' })}
                            selectedKey={selected?.id ?? null}
                        />
                        <Pagination links={invoices.links} />
                    </>
                ) : (
                    <>
                        <DataTable
                            columns={paymentColumns}
                            rows={payments.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No payments match these filters"
                            emptyDescription={
                                filters.payment_status && filters.payment_status !== 'Recorded'
                                    ? 'Failed, refunded, and cancelled payment statuses are not stored in V1.1.'
                                    : 'Record a payment against an issued invoice. Gateway integration is not configured.'
                            }
                            onRowClick={(row) => visit({ payment: String(row.id), invoice: '', view: 'payments' })}
                            selectedKey={selectedPayment?.id ?? null}
                        />
                        <Pagination links={payments.links} />
                    </>
                )}
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
                                        ['Invoice ID', recorded(selected.business_id)],
                                        ['Status', selected.status + (selected.is_overdue ? ' (overdue)' : '')],
                                        ['Invoice type', recorded(selected.invoice_type)],
                                        ['Fee', money(selected.currency, selected.amount)],
                                        ['GST rate', `${selected.gst_rate}%`],
                                        ['GST amount', money(selected.currency, selected.gst_amount)],
                                        ['Total billed', money(selected.currency, selected.total_amount)],
                                        ['Received', money(selected.currency, selected.received_amount)],
                                        ['Outstanding', money(selected.currency, selected.outstanding_amount)],
                                        ['Invoice date', recorded(selected.billed_at)],
                                        ['Due date', recorded(selected.due_at)],
                                        ['Issued', recorded(selected.issued_at)],
                                        ['Created date', recorded(selected.created_at)],
                                        ['Replacement billing', selected.is_replacement_billing ? 'Yes' : 'No'],
                                        ['Notes', selected.notes || 'Not recorded yet'],
                                    ].map(([label, value]) => (
                                        <div key={label} className="grid grid-cols-3 gap-2">
                                            <dt className="text-muted">{label}</dt>
                                            <dd className="col-span-2 text-ink">{value}</dd>
                                        </div>
                                    ))}
                                </dl>
                                {can_manage ? (
                                    <div className="flex flex-wrap gap-2">
                                        <Button disabled={!selected.can_issue} onClick={() => setIssueOpen(true)}>
                                            Issue invoice
                                        </Button>
                                        <Button disabled={!selected.can_record_payment} onClick={() => setPaymentOpen(true)}>
                                            Record payment
                                        </Button>
                                        <Button variant="danger" disabled={!selected.can_cancel} onClick={() => setCancelOpen(true)}>
                                            Cancel
                                        </Button>
                                    </div>
                                ) : (
                                    <p className="text-muted">Finance view only. Status-changing actions require Finance Manage.</p>
                                )}
                            </div>
                        ) : null}
                        {tab === 'lineage' ? <RecruitmentChain steps={selected.chain ?? []} /> : null}
                        {tab === 'payments' ? (
                            <ul className="space-y-2 text-sm">
                                {selected.payments.length === 0 ? (
                                    <li className="text-muted">No payments recorded against this invoice.</li>
                                ) : (
                                    selected.payments.map((payment) => (
                                        <li key={payment.id} className="rounded-md bg-surface px-3 py-2">
                                            <p className="font-medium text-ink">
                                                <button
                                                    type="button"
                                                    className="text-brand"
                                                    onClick={() => visit({ payment: String(payment.id), invoice: '', view: 'payments' })}
                                                >
                                                    {payment.business_id}
                                                </button>
                                                {' · '}
                                                {money(payment.currency, payment.amount)}
                                            </p>
                                            <p className="text-muted">
                                                {payment.status ?? 'Recorded'} · {payment.method} · {recorded(payment.paid_at)} ·{' '}
                                                {recorded(payment.reference)}
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

            <Drawer open={selectedPayment !== null} title={selectedPayment?.business_id ?? 'Payment'} size="xl" onClose={closeDrawer}>
                {selectedPayment ? (
                    <div className="space-y-4">
                        <Tabs
                            tabs={[
                                { id: 'overview', label: 'Overview' },
                                { id: 'lineage', label: 'Recruitment context' },
                                { id: 'timeline', label: 'History' },
                            ]}
                            active={paymentTab}
                            onChange={setPaymentTab}
                        />
                        {paymentTab === 'overview' ? (
                            <div className="space-y-4 text-sm">
                                <p className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2">
                                    {selectedPayment.gateway_message ?? 'Gateway integration not configured'}.{' '}
                                    {selectedPayment.status_note}
                                </p>
                                <dl className="grid gap-2">
                                    {[
                                        ['Payment ID', recorded(selectedPayment.business_id)],
                                        ['Amount', money(selectedPayment.currency, selectedPayment.amount)],
                                        ['Status', selectedPayment.status ?? 'Recorded'],
                                        ['Transaction reference', recorded(selectedPayment.reference)],
                                        ['Gateway reference', 'Not available in V1.1'],
                                        ['Payment method', recorded(selectedPayment.method)],
                                        ['Payment date', recorded(selectedPayment.paid_at)],
                                        ['Created date', recorded(selectedPayment.created_at)],
                                        ['Recorded by', recorded(selectedPayment.recorded_by)],
                                        ['Remarks', selectedPayment.notes || 'Not recorded yet'],
                                        ['Invoice', recorded(selectedPayment.invoice_business_id)],
                                        ['Invoice status', recorded(selectedPayment.invoice_status)],
                                    ].map(([label, value]) => (
                                        <div key={label} className="grid grid-cols-3 gap-2">
                                            <dt className="text-muted">{label}</dt>
                                            <dd className="col-span-2 text-ink">{value}</dd>
                                        </div>
                                    ))}
                                </dl>
                                {selectedPayment.invoice_id ? (
                                    <Button
                                        variant="secondary"
                                        onClick={() => visit({ invoice: String(selectedPayment.invoice_id), payment: '', view: 'invoices' })}
                                    >
                                        Open invoice
                                    </Button>
                                ) : null}
                            </div>
                        ) : null}
                        {paymentTab === 'lineage' ? <RecruitmentChain steps={selectedPayment.chain ?? []} /> : null}
                        {paymentTab === 'timeline' ? (
                            <Timeline
                                items={(selectedPayment.activities ?? []).map((activity) => ({
                                    id: activity.id,
                                    title: activityTitle(activity),
                                    meta: `${activity.actor_name ?? 'System'} · ${activity.created_at ?? ''}`,
                                }))}
                                empty="No payment history recorded yet."
                            />
                        ) : null}
                    </div>
                ) : null}
            </Drawer>

            <ConfirmDialog
                open={issueOpen}
                title="Issue this invoice?"
                description="Issuing freezes the billed amount. This does not record a payment and does not contact a payment gateway."
                confirmLabel="Issue invoice"
                onClose={() => setIssueOpen(false)}
                onConfirm={() => {
                    if (!selected) {
                        return;
                    }
                    router.post(`/admin/invoices/${selected.id}/issue`);
                    setIssueOpen(false);
                }}
            />

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
                    <p className="text-muted">This records an administrator receipt. Gateway integration is not configured.</p>
                    <Input value={amount} onChange={(event) => setAmount(event.target.value)} placeholder="Amount received" />
                    <Input type="date" value={paidAt} onChange={(event) => setPaidAt(event.target.value)} aria-label="Payment date" />
                    <Select value={method} onChange={(event) => setMethod(event.target.value)} aria-label="Payment method">
                        {methods.map((item) => (
                            <option key={item} value={item}>
                                {item}
                            </option>
                        ))}
                    </Select>
                    <Input value={reference} onChange={(event) => setReference(event.target.value)} placeholder="Transaction reference (optional)" />
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
