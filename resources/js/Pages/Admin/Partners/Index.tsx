import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    Button,
    DataTable,
    Drawer,
    FilterBar,
    Input,
    SearchBar,
    Select,
    StatusBadge,
    Tabs,
    Textarea,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { LabeledOption } from '@/types/applications';
import type { Paginator } from '@/types/jobs';

type Partner = {
    id: number;
    business_id: string | null;
    display_name: string;
    contact_person: string | null;
    email: string | null;
    mobile: string | null;
    status: string;
    status_label: string;
    agreement_count: number;
    attribution_count: number;
    user?: { email: string; account_type: string } | null;
};

type Agreement = {
    id: number;
    business_id: string | null;
    channel_partner_id: number;
    status: string;
    status_label: string;
    commission_percent: string;
    duration_months: number;
    starts_at: string | null;
    ends_at: string | null;
    partner?: { id: number; display_name: string } | null;
};

type Attribution = {
    id: number;
    status: string;
    status_label: string;
    channel_partner_id: number;
    employer_profile_id: number;
    partner?: { id: number; display_name: string } | null;
    employer?: { id: number; company_name: string | null; workspace_url?: string } | null;
    lead?: { id: number; company_name: string | null } | null;
};

type PartnerOption = { id: number; label: string };
type EmployerOption = { id: number; label: string };

type Props = {
    partners: Paginator<Partner>;
    agreements: Paginator<Agreement>;
    attributions: Paginator<Attribution>;
    selected: Partner | null;
    filters: {
        search: string;
        status: string;
        tab: string;
        partner: string;
        agreement_status: string;
        attribution_status: string;
        channel_partner_id: string;
    };
    statuses: LabeledOption[];
    agreement_statuses: LabeledOption[];
    attribution_statuses: LabeledOption[];
    employers: EmployerOption[];
    partner_options: PartnerOption[];
    can_manage: boolean;
};

export default function PartnersIndex({
    partners,
    agreements,
    attributions,
    selected,
    filters,
    statuses,
    agreement_statuses,
    attribution_statuses,
    employers,
    partner_options,
    can_manage,
}: Props) {
    const tab = filters.tab || 'partners';
    const createForm = useForm({
        display_name: '',
        contact_person: '',
        email: '',
        mobile: '',
        notes: '',
    });
    const agreementForm = useForm({
        commission_percent: '30',
        duration_months: '3',
        terms: '',
    });
    const attributionForm = useForm({
        channel_partner_id: filters.channel_partner_id || '',
        employer_profile_id: '',
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
        router.get('/admin/partners', params, { preserveState: true, preserveScroll: true });
    };

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        visit(Object.fromEntries(form.entries()) as Record<string, string>);
    };

    const partnerColumns: DataTableColumn<Partner>[] = [
        { key: 'id', header: 'ID', render: (row) => row.business_id ?? row.id },
        { key: 'name', header: 'Partner', render: (row) => row.display_name },
        { key: 'contact', header: 'Contact', render: (row) => row.contact_person ?? '—' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status_label} /> },
        { key: 'counts', header: 'Agreements / Attr.', render: (row) => `${row.agreement_count} / ${row.attribution_count}` },
    ];

    const agreementColumns: DataTableColumn<Agreement>[] = [
        { key: 'id', header: 'ID', render: (row) => row.business_id ?? row.id },
        { key: 'partner', header: 'Partner', render: (row) => row.partner?.display_name ?? '—' },
        { key: 'rate', header: 'Rate', render: (row) => `${row.commission_percent}%` },
        { key: 'window', header: 'Window', render: (row) => `${row.starts_at ?? '—'} → ${row.ends_at ?? '—'}` },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status_label} /> },
    ];

    const attributionColumns: DataTableColumn<Attribution>[] = [
        { key: 'partner', header: 'Partner', render: (row) => row.partner?.display_name ?? '—' },
        { key: 'employer', header: 'Employer', render: (row) => row.employer?.company_name ?? '—' },
        { key: 'lead', header: 'Lead', render: (row) => row.lead?.company_name ?? '—' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status_label} /> },
    ];

    return (
        <AdminLayout title="Partners">
            <div className="space-y-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Channel partners</h1>
                    <p className="mt-1 text-sm text-muted">
                        Admin-created partners, agreements, and employer attribution. Commission money lives under Finance.
                    </p>
                </div>

                <Tabs
                    tabs={[
                        { id: 'partners', label: 'Partners' },
                        { id: 'agreements', label: 'Agreements' },
                        { id: 'attributions', label: 'Attributions' },
                    ]}
                    active={tab}
                    onChange={(next) => visit({ tab: next })}
                />

                <FilterBar onSubmit={filter}>
                    <input type="hidden" name="tab" value={tab} />
                    {tab === 'partners' ? (
                        <>
                            <SearchBar name="search" defaultValue={filters.search} placeholder="Search partners" />
                            <Select name="status" defaultValue={filters.status} aria-label="Partner status">
                                <option value="">All statuses</option>
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </Select>
                        </>
                    ) : null}
                    {tab === 'agreements' ? (
                        <Select name="agreement_status" defaultValue={filters.agreement_status} aria-label="Agreement status">
                            <option value="">All agreements</option>
                            {agreement_statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </Select>
                    ) : null}
                    {tab === 'attributions' ? (
                        <Select name="attribution_status" defaultValue={filters.attribution_status} aria-label="Attribution status">
                            <option value="">All attributions</option>
                            {attribution_statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </Select>
                    ) : null}
                    <Button type="submit">Filter</Button>
                </FilterBar>

                {can_manage && tab === 'partners' ? (
                    <form
                        className="grid gap-2 rounded-xl border border-line bg-white p-4 md:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            createForm.post('/admin/partners', { onSuccess: () => createForm.reset() });
                        }}
                    >
                        <Input
                            placeholder="Partner / firm name"
                            value={createForm.data.display_name}
                            onChange={(event) => createForm.setData('display_name', event.target.value)}
                            required
                        />
                        <Input
                            placeholder="Contact person"
                            value={createForm.data.contact_person}
                            onChange={(event) => createForm.setData('contact_person', event.target.value)}
                        />
                        <Input
                            type="email"
                            placeholder="Email"
                            value={createForm.data.email}
                            onChange={(event) => createForm.setData('email', event.target.value)}
                            required
                        />
                        <Input
                            placeholder="Mobile"
                            value={createForm.data.mobile}
                            onChange={(event) => createForm.setData('mobile', event.target.value)}
                        />
                        <Textarea
                            className="md:col-span-2"
                            placeholder="Notes"
                            value={createForm.data.notes}
                            onChange={(event) => createForm.setData('notes', event.target.value)}
                        />
                        {createForm.errors.email ? <p className="text-sm text-danger">{createForm.errors.email}</p> : null}
                        <div>
                            <Button type="submit" disabled={createForm.processing}>
                                Create partner
                            </Button>
                        </div>
                    </form>
                ) : null}

                {can_manage && tab === 'agreements' && selected ? (
                    <form
                        className="grid gap-2 rounded-xl border border-line bg-white p-4 md:grid-cols-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            agreementForm.post(`/admin/partners/${selected.id}/agreements`, { onSuccess: () => agreementForm.reset() });
                        }}
                    >
                        <Input
                            placeholder="Commission %"
                            value={agreementForm.data.commission_percent}
                            onChange={(event) => agreementForm.setData('commission_percent', event.target.value)}
                        />
                        <Input
                            placeholder="Duration months"
                            value={agreementForm.data.duration_months}
                            onChange={(event) => agreementForm.setData('duration_months', event.target.value)}
                        />
                        <Input
                            placeholder="Terms"
                            value={agreementForm.data.terms}
                            onChange={(event) => agreementForm.setData('terms', event.target.value)}
                        />
                        <div className="md:col-span-3">
                            <Button type="submit" disabled={agreementForm.processing}>
                                Add agreement for {selected.display_name}
                            </Button>
                        </div>
                    </form>
                ) : null}

                {can_manage && tab === 'attributions' ? (
                    <form
                        className="grid gap-2 rounded-xl border border-line bg-white p-4 md:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            attributionForm.transform((data) => ({
                                ...data,
                                channel_partner_id: data.channel_partner_id || null,
                                employer_profile_id: data.employer_profile_id || null,
                            }));
                            attributionForm.post('/admin/attributions', { onSuccess: () => attributionForm.reset() });
                        }}
                    >
                        <Select
                            value={attributionForm.data.channel_partner_id}
                            onChange={(event) => attributionForm.setData('channel_partner_id', event.target.value)}
                            aria-label="Partner"
                            required
                        >
                            <option value="">Select partner</option>
                            {partner_options.map((partner) => (
                                <option key={partner.id} value={partner.id}>
                                    {partner.label}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={attributionForm.data.employer_profile_id}
                            onChange={(event) => attributionForm.setData('employer_profile_id', event.target.value)}
                            aria-label="Employer"
                            required
                        >
                            <option value="">Select employer</option>
                            {employers.map((employer) => (
                                <option key={employer.id} value={employer.id}>
                                    {employer.label}
                                </option>
                            ))}
                        </Select>
                        {(attributionForm.errors as Record<string, string>).employer_profile_id ? (
                            <p className="text-sm text-danger md:col-span-2">
                                {(attributionForm.errors as Record<string, string>).employer_profile_id}
                            </p>
                        ) : null}
                        <div>
                            <Button type="submit" disabled={attributionForm.processing}>
                                Record attribution
                            </Button>
                        </div>
                    </form>
                ) : null}

                {tab === 'partners' ? (
                    <>
                        <DataTable
                            columns={partnerColumns}
                            rows={partners.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No channel partners yet."
                            emptyDescription="Create a partner from this page. Public registration is not available."
                            selectedKey={selected?.id ?? null}
                            onRowClick={(row) => visit({ tab: 'partners', partner: String(row.id) })}
                        />
                        <Pagination links={partners.links} />
                    </>
                ) : null}

                {tab === 'agreements' ? (
                    <>
                        <DataTable
                            columns={agreementColumns}
                            rows={agreements.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No agreements yet."
                            emptyDescription="Open a partner first, then add an agreement."
                        />
                        <Pagination links={agreements.links} />
                    </>
                ) : null}

                {tab === 'attributions' ? (
                    <>
                        <DataTable
                            columns={attributionColumns}
                            rows={attributions.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No employer attributions yet."
                            emptyDescription="Convert a partner CRM lead, or record attribution here."
                        />
                        <Pagination links={attributions.links} />
                    </>
                ) : null}
            </div>

            <Drawer open={selected !== null} title={selected?.display_name ?? 'Partner'} onClose={() => visit({ partner: '' })}>
                {selected ? (
                    <div className="space-y-4">
                        <p className="text-sm text-muted">
                            {selected.business_id} · {selected.email ?? 'No email'} · {selected.user?.account_type}
                        </p>
                        <StatusBadge status={selected.status_label} />
                        {can_manage ? (
                            <div className="flex flex-wrap gap-2">
                                {selected.status !== 'active' ? (
                                    <Button type="button" onClick={() => router.post(`/admin/partners/${selected.id}/approve`)}>
                                        Approve
                                    </Button>
                                ) : null}
                                {selected.status === 'active' ? (
                                    <Button type="button" onClick={() => router.post(`/admin/partners/${selected.id}/suspend`)}>
                                        Suspend
                                    </Button>
                                ) : null}
                            </div>
                        ) : null}
                        <Link href={`/admin/crm?channel_partner_id=${selected.id}`} className="block text-sm font-medium text-brand">
                            View CRM leads
                        </Link>
                    </div>
                ) : null}
            </Drawer>

            {tab === 'agreements' && can_manage ? (
                <div className="mt-2 space-y-2">
                    {agreements.data
                        .filter((agreement) => agreement.status !== 'active')
                        .map((agreement) => (
                            <div key={agreement.id} className="flex items-center justify-between rounded-md border border-line bg-white px-3 py-2 text-sm">
                                <span>
                                    {agreement.business_id} · {agreement.partner?.display_name} · {agreement.status_label}
                                </span>
                                <Button type="button" onClick={() => router.post(`/admin/agreements/${agreement.id}/activate`)}>
                                    Activate
                                </Button>
                            </div>
                        ))}
                </div>
            ) : null}

            {tab === 'attributions' && can_manage ? (
                <div className="mt-2 space-y-2">
                    {attributions.data.map((attribution) => (
                        <div key={attribution.id} className="flex flex-wrap items-center justify-between gap-2 rounded-md border border-line bg-white px-3 py-2 text-sm">
                            <span>
                                {attribution.partner?.display_name} → {attribution.employer?.company_name} · {attribution.status_label}
                            </span>
                            <div className="flex gap-2">
                                {attribution.status === 'pending' ? (
                                    <Button type="button" onClick={() => router.post(`/admin/attributions/${attribution.id}/confirm`)}>
                                        Confirm
                                    </Button>
                                ) : null}
                                {attribution.status === 'confirmed' ? (
                                    <Button type="button" onClick={() => router.post(`/admin/attributions/${attribution.id}/dispute`)}>
                                        Dispute
                                    </Button>
                                ) : null}
                                {attribution.status !== 'void' ? (
                                    <Button type="button" onClick={() => router.post(`/admin/attributions/${attribution.id}/void`)}>
                                        Void
                                    </Button>
                                ) : null}
                            </div>
                        </div>
                    ))}
                </div>
            ) : null}
        </AdminLayout>
    );
}
