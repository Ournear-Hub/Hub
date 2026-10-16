import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    Button,
    Drawer,
    Input,
    Select,
    StatusBadge,
    Tabs,
} from '@/Components/Admin/ui';
import FilterBar from '@/Components/molecules/FilterBar';
import DataTable from '@/Components/molecules/DataTable';
import type { DataTableColumn } from '@/Components/molecules/DataTable';
import type { LabeledOption } from '@/types/applications';
import type { Paginator } from '@/types/jobs';

type CommissionRow = {
    id: number;
    business_id: string | null;
    status: string;
    status_label: string;
    can_retry?: boolean;
    currency: string;
    basis_amount: string;
    rate: string;
    commission_amount: string;
    channel_partner_id: number;
    partner?: { id: number; display_name: string } | null;
    employer?: { id: number; company_name: string | null } | null;
    payment?: { id: number; business_id: string | null; paid_at: string | null } | null;
};

type PayoutRow = {
    id: number;
    business_id: string | null;
    amount: string;
    currency: string;
    paid_at: string | null;
    method: string;
    partner?: { id: number; display_name: string } | null;
};

type PartnerOption = { id: number; label: string };

type Props = {
    entries: Paginator<CommissionRow>;
    payouts: Paginator<PayoutRow>;
    selected: CommissionRow | null;
    selectedPayout: PayoutRow | null;
    filters: {
        status: string;
        channel_partner_id: string;
        view: string;
        entry: string;
        payout: string;
    };
    statuses: LabeledOption[];
    methods: string[];
    partners: PartnerOption[];
    can_manage: boolean;
};

export default function CommissionIndex({
    entries,
    payouts,
    selected,
    selectedPayout,
    filters,
    statuses,
    methods,
    partners,
    can_manage,
}: Props) {
    const view = filters.view || 'entries';
    const payoutForm = useForm({
        channel_partner_id: selected?.channel_partner_id ? String(selected.channel_partner_id) : '',
        entry_ids: selected ? [selected.id] : ([] as number[]),
        paid_at: new Date().toISOString().slice(0, 10),
        method: methods[0] ?? 'Bank transfer',
        reference: '',
        notes: '',
    });

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/commission', params, { preserveState: true, preserveScroll: true });
    };

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        visit({ ...Object.fromEntries(form.entries()) as Record<string, string>, view });
    };

    const entryColumns: DataTableColumn<CommissionRow>[] = [
        { key: 'id', header: 'ID', render: (row) => row.business_id ?? row.id },
        { key: 'partner', header: 'Partner', render: (row) => row.partner?.display_name ?? '—' },
        { key: 'employer', header: 'Employer', render: (row) => row.employer?.company_name ?? '—' },
        { key: 'basis', header: 'Received', render: (row) => `${row.currency} ${row.basis_amount}` },
        { key: 'amount', header: 'Commission', render: (row) => `${row.currency} ${row.commission_amount}` },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status_label} /> },
    ];

    const payoutColumns: DataTableColumn<PayoutRow>[] = [
        { key: 'id', header: 'ID', render: (row) => row.business_id ?? row.id },
        { key: 'partner', header: 'Partner', render: (row) => row.partner?.display_name ?? '—' },
        { key: 'amount', header: 'Amount', render: (row) => `${row.currency} ${row.amount}` },
        { key: 'paid', header: 'Paid at', render: (row) => row.paid_at ?? '—' },
        { key: 'method', header: 'Method', render: (row) => row.method },
    ];

    return (
        <AdminLayout title="Commission">
            <div className="space-y-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Partner commission</h1>
                    <p className="mt-1 text-sm text-muted">
                        Entries are created only from recorded employer payment receipts. Approve, void, and record payouts here.
                    </p>
                </div>

                <Tabs
                    tabs={[
                        { id: 'entries', label: `Entries (${entries.total})` },
                        { id: 'payouts', label: `Payouts (${payouts.total})` },
                    ]}
                    active={view}
                    onChange={(next) => visit({ view: next, entry: '', payout: '' })}
                />

                <FilterBar onSubmit={filter}>
                    <Select name="status" defaultValue={filters.status} aria-label="Entry status">
                        <option value="">All statuses</option>
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </Select>
                    <Select name="channel_partner_id" defaultValue={filters.channel_partner_id} aria-label="Partner">
                        <option value="">All partners</option>
                        {partners.map((partner) => (
                            <option key={partner.id} value={partner.id}>
                                {partner.label}
                            </option>
                        ))}
                    </Select>
                    <Button type="submit">Filter</Button>
                </FilterBar>

                {view === 'entries' ? (
                    <>
                        <DataTable
                            columns={entryColumns}
                            rows={entries.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No commission entries yet."
                            emptyDescription="Record an employer payment against an attributed employer to create an entry."
                            selectedKey={selected?.id ?? null}
                            onRowClick={(row) => visit({ view: 'entries', entry: String(row.id), payout: '' })}
                        />
                        <Pagination links={entries.links} />
                    </>
                ) : (
                    <>
                        <DataTable
                            columns={payoutColumns}
                            rows={payouts.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No partner payouts yet."
                            emptyDescription="Approve entries first, then record a payout."
                            selectedKey={selectedPayout?.id ?? null}
                            onRowClick={(row) => visit({ view: 'payouts', payout: String(row.id), entry: '' })}
                        />
                        <Pagination links={payouts.links} />
                    </>
                )}
            </div>

            <Drawer
                open={selected !== null}
                title={selected?.business_id ?? 'Commission entry'}
                onClose={() => visit({ entry: '' })}
            >
                {selected ? (
                    <div className="space-y-3 text-sm">
                        <StatusBadge status={selected.status_label} />
                        <p>
                            {selected.partner?.display_name} · {selected.employer?.company_name}
                        </p>
                        <p>
                            Basis {selected.currency} {selected.basis_amount} at {selected.rate}% = {selected.currency}{' '}
                            {selected.commission_amount}
                        </p>
                        {can_manage && selected.status === 'failed' ? (
                            <Button type="button" onClick={() => router.post(`/admin/commission/entries/${selected.id}/retry`)}>
                                Retry
                            </Button>
                        ) : null}
                        {can_manage && selected.status === 'pending' ? (
                            <Button type="button" onClick={() => router.post(`/admin/commission/entries/${selected.id}/approve`)}>
                                Approve
                            </Button>
                        ) : null}
                        {can_manage && (selected.status === 'pending' || selected.status === 'approved') ? (
                            <Button type="button" onClick={() => router.post(`/admin/commission/entries/${selected.id}/void`)}>
                                Void
                            </Button>
                        ) : null}
                        {can_manage && selected.status === 'approved' ? (
                            <form
                                className="space-y-2 rounded-md border border-line p-3"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    payoutForm.setData('channel_partner_id', String(selected.channel_partner_id));
                                    payoutForm.setData('entry_ids', [selected.id]);
                                    payoutForm.post('/admin/commission/payouts');
                                }}
                            >
                                <Input
                                    type="date"
                                    value={payoutForm.data.paid_at}
                                    onChange={(event) => payoutForm.setData('paid_at', event.target.value)}
                                />
                                <Select
                                    value={payoutForm.data.method}
                                    onChange={(event) => payoutForm.setData('method', event.target.value)}
                                    aria-label="Payout method"
                                >
                                    {methods.map((method) => (
                                        <option key={method} value={method}>
                                            {method}
                                        </option>
                                    ))}
                                </Select>
                                <Input
                                    placeholder="Reference"
                                    value={payoutForm.data.reference}
                                    onChange={(event) => payoutForm.setData('reference', event.target.value)}
                                />
                                <Button type="submit" disabled={payoutForm.processing}>
                                    Record payout
                                </Button>
                            </form>
                        ) : null}
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}
