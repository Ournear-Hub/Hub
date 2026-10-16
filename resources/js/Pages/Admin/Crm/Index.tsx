import { Link, router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    Button,
    Drawer,
    Input,
    SearchBar,
    Select,
    StatusBadge,
    Textarea,
} from '@/Components/Admin/ui';
import FilterBar from '@/Components/molecules/FilterBar';
import DataTable from '@/Components/molecules/DataTable';
import type { DataTableColumn } from '@/Components/molecules/DataTable';
import type { LabeledOption, StaffUserOption } from '@/types/applications';
import type { Paginator } from '@/types/jobs';
import type { SharedPageProps } from '@/types/page';

type DuplicateMatch = {
    type: string;
    id: number;
    company_name: string | null;
    email: string | null;
    workspace_url?: string;
    reasons: string[];
};

type CrmOpportunity = {
    id: number;
    title: string;
    status: string;
    status_label: string;
    assigned_to: number | null;
    employer_profile_id: number | null;
    notes?: string | null;
    assignee_name?: string | null;
};

type CrmLead = {
    id: number;
    company_name: string;
    contact_person: string | null;
    email: string | null;
    mobile: string | null;
    website: string | null;
    source: string;
    source_label: string;
    notes: string | null;
    assigned_to: number | null;
    assignee_name: string | null;
    employer_profile_id: number | null;
    employer: { id: number; company_name: string | null; workspace_url?: string } | null;
    enquiry: { id: number; company_name: string | null; workspace_url?: string } | null;
    partner?: { id: number; display_name: string; workspace_url?: string } | null;
    channel_partner_id?: number | null;
    opportunities: CrmOpportunity[];
    created_at: string | null;
};

type EmployerOption = {
    id: number;
    company_name: string;
    label: string;
};

type PartnerOption = {
    id: number;
    label: string;
};

type Props = {
    leads: Paginator<CrmLead>;
    selected: CrmLead | null;
    selectedOpportunity: (CrmOpportunity & { lead?: CrmLead | null; employer?: { id: number; company_name: string | null } | null }) | null;
    filters: {
        search: string;
        assigned_to: string;
        source: string;
        status: string;
        lead: string;
        opportunity: string;
    };
    sources: LabeledOption[];
    statuses: LabeledOption[];
    staffUsers: StaffUserOption[];
    employers: EmployerOption[];
    partners?: PartnerOption[];
    can_manage: boolean;
    duplicate_matches: DuplicateMatch[];
};

export default function CrmIndex({
    leads,
    selected,
    selectedOpportunity,
    filters,
    sources,
    statuses,
    staffUsers,
    employers,
    partners = [],
    can_manage,
    duplicate_matches = [],
}: Props) {
    const page = usePage<SharedPageProps>();
    const flashedDuplicates = (page.props.flash.duplicate_matches ?? duplicate_matches) as DuplicateMatch[];

    const createForm = useForm({
        company_name: '',
        contact_person: '',
        email: '',
        mobile: '',
        website: '',
        notes: '',
        assigned_to: '',
        channel_partner_id: '',
        confirm_duplicates: false as boolean,
    });

    const convertForm = useForm({
        employer_profile_id: '',
        create_employer: false as boolean,
        email: selected?.email ?? '',
        confirm_duplicates: false as boolean,
    });

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/crm', params, { preserveState: true, preserveScroll: true });
    };

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        visit(Object.fromEntries(form.entries()) as Record<string, string>);
    };

    const columns: DataTableColumn<CrmLead>[] = [
        { key: 'company', header: 'Company', render: (row) => row.company_name },
        { key: 'contact', header: 'Contact', render: (row) => row.contact_person ?? '—' },
        { key: 'source', header: 'Source', render: (row) => row.source_label },
        { key: 'assignee', header: 'Assigned to', render: (row) => row.assignee_name ?? 'Unassigned' },
        {
            key: 'stage',
            header: 'Latest stage',
            render: (row) => row.opportunities[0]?.status_label ?? '—',
        },
        {
            key: 'employer',
            header: 'Employer',
            render: (row) => row.employer?.company_name ?? 'Not converted',
        },
    ];

    return (
        <AdminLayout title="CRM">
            <div className="space-y-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">CRM</h1>
                    <p className="mt-1 text-sm text-muted">
                        Sales leads and opportunities. Converted clients become EmployerProfile records. Enquiries stay a separate source.
                    </p>
                </div>

                {flashedDuplicates.length > 0 ? (
                    <div className="rounded-xl border border-line bg-surface p-4 text-sm">
                        <p className="font-medium text-ink">Possible duplicate companies. Confirm to continue without merging.</p>
                        <ul className="mt-2 space-y-1 text-muted">
                            {flashedDuplicates.map((match) => (
                                <li key={`${match.type}-${match.id}`}>
                                    {match.type} #{match.id} · {match.company_name ?? 'Unnamed'} · {match.reasons.join(', ')}
                                    {match.workspace_url ? (
                                        <Link href={match.workspace_url} className="ml-2 text-brand">
                                            Open
                                        </Link>
                                    ) : null}
                                </li>
                            ))}
                        </ul>
                    </div>
                ) : null}

                <FilterBar onSubmit={filter}>
                    <SearchBar name="search" defaultValue={filters.search} placeholder="Search company, contact, email" />
                    <Select name="source" defaultValue={filters.source} aria-label="Source">
                        <option value="">All sources</option>
                        {sources.map((source) => (
                            <option key={source.value} value={source.value}>
                                {source.label}
                            </option>
                        ))}
                    </Select>
                    <Select name="status" defaultValue={filters.status} aria-label="Opportunity status">
                        <option value="">All stages</option>
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </Select>
                    <Select name="assigned_to" defaultValue={filters.assigned_to} aria-label="Assignee">
                        <option value="">All staff</option>
                        {staffUsers.map((user) => (
                            <option key={user.id} value={user.id}>
                                {user.name}
                            </option>
                        ))}
                    </Select>
                    <Button type="submit">Filter</Button>
                </FilterBar>

                {can_manage ? (
                    <form
                        className="grid gap-2 rounded-xl border border-line bg-white p-4 md:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            createForm.transform((data) => ({
                                ...data,
                                assigned_to: data.assigned_to || null,
                                channel_partner_id: data.channel_partner_id || null,
                                confirm_duplicates: data.confirm_duplicates || flashedDuplicates.length > 0,
                            }));
                            createForm.post('/admin/crm/leads', { onSuccess: () => createForm.reset() });
                        }}
                    >
                        <Input
                            placeholder="Company name"
                            value={createForm.data.company_name}
                            onChange={(event) => createForm.setData('company_name', event.target.value)}
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
                        />
                        <Input
                            placeholder="Mobile"
                            value={createForm.data.mobile}
                            onChange={(event) => createForm.setData('mobile', event.target.value)}
                        />
                        <Input
                            placeholder="Website"
                            value={createForm.data.website}
                            onChange={(event) => createForm.setData('website', event.target.value)}
                        />
                        <Select
                            value={createForm.data.channel_partner_id}
                            onChange={(event) => createForm.setData('channel_partner_id', event.target.value)}
                            aria-label="Channel partner"
                        >
                            <option value="">No partner</option>
                            {partners.map((partner) => (
                                <option key={partner.id} value={partner.id}>
                                    {partner.label}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={createForm.data.assigned_to}
                            onChange={(event) => createForm.setData('assigned_to', event.target.value)}
                            aria-label="Assign to"
                        >
                            <option value="">Unassigned</option>
                            {staffUsers.map((user) => (
                                <option key={user.id} value={user.id}>
                                    {user.name}
                                </option>
                            ))}
                        </Select>
                        <Textarea
                            className="md:col-span-2"
                            placeholder="Notes"
                            value={createForm.data.notes}
                            onChange={(event) => createForm.setData('notes', event.target.value)}
                        />
                        {(createForm.errors as Record<string, string>).duplicates ? (
                            <p className="text-sm text-danger md:col-span-2">{(createForm.errors as Record<string, string>).duplicates}</p>
                        ) : null}
                        <div>
                            <Button type="submit" disabled={createForm.processing}>
                                Create lead
                            </Button>
                        </div>
                    </form>
                ) : null}

                <DataTable
                    columns={columns}
                    rows={leads.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No CRM leads yet."
                    emptyDescription="Create a lead or convert an employer enquiry."
                    selectedKey={selected?.id ?? null}
                    onRowClick={(row) => visit({ lead: String(row.id), opportunity: '' })}
                />
                <Pagination links={leads.links} />
            </div>

            <Drawer open={selected !== null} title={selected?.company_name ?? 'CRM lead'} onClose={() => visit({ lead: '', opportunity: '' })}>
                {selected ? (
                    <div className="space-y-4">
                        <p className="text-sm text-muted">
                            {selected.contact_person ?? 'No contact'} · {selected.email ?? 'No email'} · {selected.source_label}
                        </p>
                        {selected.employer ? (
                            <Link href={selected.employer.workspace_url ?? `/admin/employers?employer=${selected.employer.id}`} className="text-sm font-medium text-brand">
                                Linked employer: {selected.employer.company_name}
                            </Link>
                        ) : (
                            <p className="text-sm text-muted">Not converted to an EmployerProfile yet.</p>
                        )}
                        {selected.enquiry ? (
                            <Link href={selected.enquiry.workspace_url ?? `/admin/employer-enquiries?enquiry=${selected.enquiry.id}`} className="block text-sm font-medium text-brand">
                                Source enquiry #{selected.enquiry.id}
                            </Link>
                        ) : null}
                        {selected.partner ? (
                            <Link href={selected.partner.workspace_url ?? `/admin/partners?partner=${selected.partner.id}`} className="block text-sm font-medium text-brand">
                                Channel partner: {selected.partner.display_name}
                            </Link>
                        ) : (
                            <p className="text-sm text-muted">No channel partner attributed.</p>
                        )}

                        <section>
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Opportunities</h3>
                            <ul className="mt-2 space-y-2">
                                {selected.opportunities.map((opportunity) => (
                                    <li key={opportunity.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                        <div className="flex items-center justify-between gap-2">
                                            <span className="font-medium text-ink">{opportunity.title}</span>
                                            <StatusBadge status={opportunity.status_label} />
                                        </div>
                                        {can_manage ? (
                                            <div className="mt-2 flex flex-wrap gap-2">
                                                <Select
                                                    value={opportunity.status}
                                                    aria-label="Opportunity status"
                                                    onChange={(event) =>
                                                        router.put(`/admin/crm/opportunities/${opportunity.id}`, {
                                                            status: event.target.value,
                                                        })
                                                    }
                                                >
                                                    {statuses.map((status) => (
                                                        <option key={status.value} value={status.value}>
                                                            {status.label}
                                                        </option>
                                                    ))}
                                                </Select>
                                            </div>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        </section>

                        {can_manage && !selected.employer_profile_id && selected.opportunities[0] ? (
                            <form
                                className="space-y-2 rounded-md border border-line p-3"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    convertForm.transform((data) => ({
                                        employer_profile_id: data.employer_profile_id || null,
                                        create_employer: data.create_employer,
                                        email: data.email || selected.email,
                                        confirm_duplicates: data.confirm_duplicates || flashedDuplicates.length > 0,
                                    }));
                                    convertForm.post(`/admin/crm/opportunities/${selectedOpportunity?.id ?? selected.opportunities[0].id}/convert`);
                                }}
                            >
                                <h3 className="text-sm font-semibold text-ink">Convert to employer</h3>
                                <Select
                                    value={convertForm.data.employer_profile_id}
                                    onChange={(event) => convertForm.setData('employer_profile_id', event.target.value)}
                                    aria-label="Existing employer"
                                >
                                    <option value="">Link existing employer</option>
                                    {employers.map((employer) => (
                                        <option key={employer.id} value={employer.id}>
                                            {employer.label}
                                        </option>
                                    ))}
                                </Select>
                                <Input
                                    type="email"
                                    placeholder="Email for new employer account"
                                    value={convertForm.data.email}
                                    onChange={(event) => convertForm.setData('email', event.target.value)}
                                />
                                <label className="flex items-center gap-2 text-sm text-ink">
                                    <input
                                        type="checkbox"
                                        checked={convertForm.data.create_employer}
                                        onChange={(event) => convertForm.setData('create_employer', event.target.checked)}
                                    />
                                    Create new User + EmployerProfile
                                </label>
                                {convertForm.errors.create_employer ? <p className="text-sm text-danger">{convertForm.errors.create_employer}</p> : null}
                                {convertForm.errors.employer_profile_id ? <p className="text-sm text-danger">{convertForm.errors.employer_profile_id}</p> : null}
                                {(convertForm.errors as Record<string, string>).duplicates ? (
                                    <p className="text-sm text-danger">{(convertForm.errors as Record<string, string>).duplicates}</p>
                                ) : null}
                                <Button type="submit" disabled={convertForm.processing}>
                                    Convert
                                </Button>
                            </form>
                        ) : null}
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}
