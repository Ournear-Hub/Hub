import { Link, router, useForm } from '@inertiajs/react';
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
    Tabs,
    Textarea,
} from '@/Components/Admin/ui';
import FilterBar from '@/Components/molecules/FilterBar';
import DataTable from '@/Components/molecules/DataTable';
import type { DataTableColumn } from '@/Components/molecules/DataTable';
import type { LabeledOption } from '@/types/applications';
import type { Paginator } from '@/types/jobs';

type Investor = {
    id: number;
    business_id: string | null;
    display_name: string;
    contact_person: string | null;
    email: string | null;
    mobile: string | null;
    notes: string | null;
    status: string;
    status_label: string;
    interest_count: number;
    verified_at: string | null;
    user?: { email: string; account_type: string; account_status: string } | null;
};

type Opportunity = {
    id: number;
    business_id: string | null;
    title: string;
    summary: string | null;
    sector: string | null;
    location: string | null;
    indicated_size: string | null;
    currency: string;
    status: string;
    status_label: string;
    interest_count: number;
};

type Interest = {
    id: number;
    business_id: string | null;
    investor_id: number;
    investment_opportunity_id: number;
    status: string;
    status_label: string;
    indicated_amount: string | null;
    currency: string;
    is_recorded_intent: boolean;
    investor?: { id: number; display_name: string } | null;
    opportunity?: { id: number; title: string } | null;
};

type ActivityItem = {
    id: number;
    type: string;
    body: string | null;
    actor_name: string | null;
    created_at: string | null;
};

type WorkItem = {
    id: number;
    title: string;
    status_label: string;
    assignee_name?: string | null;
};

type SelectOption = { id: number; label: string };

type Props = {
    investors: Paginator<Investor>;
    opportunities: Paginator<Opportunity>;
    interests: Paginator<Interest>;
    selected: Investor | null;
    selected_opportunity: Opportunity | null;
    activity: ActivityItem[];
    assigned_work: WorkItem[];
    filters: {
        search: string;
        status: string;
        tab: string;
        investor: string;
        opportunity: string;
        opportunity_status: string;
        interest_status: string;
    };
    statuses: LabeledOption[];
    opportunity_statuses: LabeledOption[];
    interest_statuses: LabeledOption[];
    investor_options: SelectOption[];
    opportunity_options: SelectOption[];
    can_manage: boolean;
};

export default function InvestorsIndex({
    investors,
    opportunities,
    interests,
    selected,
    selected_opportunity,
    activity,
    assigned_work,
    filters,
    statuses,
    opportunity_statuses,
    interest_statuses,
    investor_options,
    opportunity_options,
    can_manage,
}: Props) {
    const tab = filters.tab || 'investors';
    const createForm = useForm({
        display_name: '',
        contact_person: '',
        email: '',
        mobile: '',
        notes: '',
    });
    const opportunityForm = useForm({
        title: '',
        summary: '',
        sector: '',
        location: '',
        indicated_size: '',
        notes: '',
    });
    const interestForm = useForm({
        investor_id: filters.investor || '',
        investment_opportunity_id: filters.opportunity || '',
        indicated_amount: '',
        notes: '',
    });
    const taskForm = useForm({
        title: '',
        investor_id: selected?.id ? String(selected.id) : '',
        investment_opportunity_id: selected_opportunity?.id ? String(selected_opportunity.id) : '',
    });

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/investors', params, { preserveState: true, preserveScroll: true });
    };

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        visit(Object.fromEntries(form.entries()) as Record<string, string>);
    };

    const investorColumns: DataTableColumn<Investor>[] = [
        { key: 'id', header: 'ID', render: (row) => row.business_id ?? row.id },
        { key: 'name', header: 'Investor', render: (row) => row.display_name },
        { key: 'contact', header: 'Contact', render: (row) => row.contact_person ?? '—' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status_label} /> },
        { key: 'interests', header: 'Interests', render: (row) => row.interest_count },
    ];

    const opportunityColumns: DataTableColumn<Opportunity>[] = [
        { key: 'id', header: 'ID', render: (row) => row.business_id ?? row.id },
        { key: 'title', header: 'Opportunity', render: (row) => row.title },
        { key: 'sector', header: 'Sector', render: (row) => row.sector ?? '—' },
        { key: 'status', header: 'Stage', render: (row) => <StatusBadge status={row.status_label} /> },
        { key: 'interests', header: 'Interests', render: (row) => row.interest_count },
    ];

    const interestColumns: DataTableColumn<Interest>[] = [
        { key: 'investor', header: 'Investor', render: (row) => row.investor?.display_name ?? '—' },
        { key: 'opportunity', header: 'Opportunity', render: (row) => row.opportunity?.title ?? '—' },
        { key: 'amount', header: 'Indicated amount', render: (row) => (row.indicated_amount ? `${row.currency} ${row.indicated_amount}` : '—') },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status_label} /> },
        { key: 'intent', header: 'Intent', render: (row) => (row.is_recorded_intent ? 'Non-binding' : '—') },
    ];

    return (
        <AdminLayout title="Investors">
            <div className="space-y-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Investors</h1>
                    <p className="mt-1 text-sm text-muted">
                        Admin-created investors and investment opportunities. Recorded interest is non-binding. No payments or investor portal.
                    </p>
                </div>

                <Tabs
                    tabs={[
                        { id: 'investors', label: 'Investors' },
                        { id: 'opportunities', label: 'Opportunities' },
                        { id: 'interests', label: 'Interests' },
                    ]}
                    active={tab}
                    onChange={(next) => visit({ tab: next })}
                />

                <FilterBar onSubmit={filter}>
                    <input type="hidden" name="tab" value={tab} />
                    {tab === 'investors' ? (
                        <>
                            <SearchBar name="search" defaultValue={filters.search} placeholder="Search investors" />
                            <Select name="status" defaultValue={filters.status} aria-label="Investor status">
                                <option value="">All statuses</option>
                                {statuses.map((status) => (
                                    <option key={status.value} value={status.value}>
                                        {status.label}
                                    </option>
                                ))}
                            </Select>
                        </>
                    ) : null}
                    {tab === 'opportunities' ? (
                        <Select name="opportunity_status" defaultValue={filters.opportunity_status} aria-label="Opportunity stage">
                            <option value="">All stages</option>
                            {opportunity_statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </Select>
                    ) : null}
                    {tab === 'interests' ? (
                        <Select name="interest_status" defaultValue={filters.interest_status} aria-label="Interest status">
                            <option value="">All interests</option>
                            {interest_statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </Select>
                    ) : null}
                    <Button type="submit">Filter</Button>
                </FilterBar>

                {can_manage && tab === 'investors' ? (
                    <form
                        className="grid gap-2 rounded-xl border border-line bg-white p-4 md:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            createForm.post('/admin/investors', { onSuccess: () => createForm.reset() });
                        }}
                    >
                        <Input
                            placeholder="Investor / firm name"
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
                                Create investor
                            </Button>
                        </div>
                    </form>
                ) : null}

                {can_manage && tab === 'opportunities' ? (
                    <form
                        className="grid gap-2 rounded-xl border border-line bg-white p-4 md:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            opportunityForm.post('/admin/investors/opportunities', { onSuccess: () => opportunityForm.reset() });
                        }}
                    >
                        <Input
                            placeholder="Opportunity title"
                            value={opportunityForm.data.title}
                            onChange={(event) => opportunityForm.setData('title', event.target.value)}
                            required
                        />
                        <Input
                            placeholder="Sector"
                            value={opportunityForm.data.sector}
                            onChange={(event) => opportunityForm.setData('sector', event.target.value)}
                        />
                        <Input
                            placeholder="Location"
                            value={opportunityForm.data.location}
                            onChange={(event) => opportunityForm.setData('location', event.target.value)}
                        />
                        <Input
                            placeholder="Indicated size (informational)"
                            value={opportunityForm.data.indicated_size}
                            onChange={(event) => opportunityForm.setData('indicated_size', event.target.value)}
                        />
                        <Textarea
                            className="md:col-span-2"
                            placeholder="Summary"
                            value={opportunityForm.data.summary}
                            onChange={(event) => opportunityForm.setData('summary', event.target.value)}
                        />
                        <div>
                            <Button type="submit" disabled={opportunityForm.processing}>
                                Create opportunity
                            </Button>
                        </div>
                    </form>
                ) : null}

                {can_manage && tab === 'interests' ? (
                    <form
                        className="grid gap-2 rounded-xl border border-line bg-white p-4 md:grid-cols-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            interestForm.transform((data) => ({
                                ...data,
                                investor_id: data.investor_id || null,
                                investment_opportunity_id: data.investment_opportunity_id || null,
                                indicated_amount: data.indicated_amount || null,
                            }));
                            interestForm.post('/admin/investors/interests', { onSuccess: () => interestForm.reset() });
                        }}
                    >
                        <Select
                            value={interestForm.data.investor_id}
                            onChange={(event) => interestForm.setData('investor_id', event.target.value)}
                            aria-label="Investor"
                            required
                        >
                            <option value="">Select investor</option>
                            {investor_options.map((investor) => (
                                <option key={investor.id} value={investor.id}>
                                    {investor.label}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={interestForm.data.investment_opportunity_id}
                            onChange={(event) => interestForm.setData('investment_opportunity_id', event.target.value)}
                            aria-label="Opportunity"
                            required
                        >
                            <option value="">Select opportunity</option>
                            {opportunity_options.map((opportunity) => (
                                <option key={opportunity.id} value={opportunity.id}>
                                    {opportunity.label}
                                </option>
                            ))}
                        </Select>
                        <Input
                            placeholder="Indicated amount (informational)"
                            value={interestForm.data.indicated_amount}
                            onChange={(event) => interestForm.setData('indicated_amount', event.target.value)}
                        />
                        <Textarea
                            placeholder="Notes"
                            value={interestForm.data.notes}
                            onChange={(event) => interestForm.setData('notes', event.target.value)}
                        />
                        {(interestForm.errors as Record<string, string>).investment_opportunity_id ? (
                            <p className="text-sm text-danger md:col-span-2">
                                {(interestForm.errors as Record<string, string>).investment_opportunity_id}
                            </p>
                        ) : null}
                        <div>
                            <Button type="submit" disabled={interestForm.processing}>
                                Record interest
                            </Button>
                        </div>
                    </form>
                ) : null}

                {tab === 'investors' ? (
                    <>
                        <DataTable
                            columns={investorColumns}
                            rows={investors.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No investors yet."
                            emptyDescription="Create an investor from this page. Public registration is not available."
                            selectedKey={selected?.id ?? null}
                            onRowClick={(row) => visit({ tab: 'investors', investor: String(row.id) })}
                        />
                        <Pagination links={investors.links} />
                    </>
                ) : null}

                {tab === 'opportunities' ? (
                    <>
                        <DataTable
                            columns={opportunityColumns}
                            rows={opportunities.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No investment opportunities yet."
                            emptyDescription="Create an opportunity, then open it for interest."
                            selectedKey={selected_opportunity?.id ?? null}
                            onRowClick={(row) => visit({ tab: 'opportunities', opportunity: String(row.id) })}
                        />
                        <Pagination links={opportunities.links} />
                    </>
                ) : null}

                {tab === 'interests' ? (
                    <>
                        <DataTable
                            columns={interestColumns}
                            rows={interests.data}
                            rowKey={(row) => row.id}
                            emptyTitle="No investor interests yet."
                            emptyDescription="Record interest after an opportunity is open."
                        />
                        <Pagination links={interests.links} />
                    </>
                ) : null}
            </div>

            <Drawer
                open={selected !== null}
                title={selected?.display_name ?? 'Investor'}
                onClose={() => visit({ investor: '' })}
            >
                {selected ? (
                    <div className="space-y-4">
                        <p className="text-sm text-muted">
                            {selected.business_id} · {selected.email ?? 'No email'} · {selected.user?.account_type}
                        </p>
                        <StatusBadge status={selected.status_label} />
                        {can_manage ? (
                            <div className="flex flex-wrap gap-2">
                                {selected.status !== 'active' ? (
                                    <Button type="button" onClick={() => router.post(`/admin/investors/${selected.id}/verify`)}>
                                        Verify
                                    </Button>
                                ) : null}
                                {selected.status !== 'suspended' ? (
                                    <Button type="button" onClick={() => router.post(`/admin/investors/${selected.id}/suspend`)}>
                                        Suspend
                                    </Button>
                                ) : null}
                            </div>
                        ) : null}
                        {can_manage ? (
                            <form
                                className="space-y-2"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    taskForm.transform((data) => ({
                                        ...data,
                                        investor_id: selected.id,
                                        investment_opportunity_id: data.investment_opportunity_id || null,
                                    }));
                                    taskForm.post('/admin/tasks', { onSuccess: () => taskForm.reset() });
                                }}
                            >
                                <Input
                                    placeholder="Assign work"
                                    value={taskForm.data.title}
                                    onChange={(event) => taskForm.setData('title', event.target.value)}
                                    required
                                />
                                <Button type="submit" disabled={taskForm.processing}>
                                    Create task
                                </Button>
                            </form>
                        ) : null}
                        <div>
                            <h3 className="text-sm font-semibold text-ink">Assigned work</h3>
                            {assigned_work.length === 0 ? (
                                <p className="mt-1 text-sm text-muted">No assigned work yet.</p>
                            ) : (
                                <ul className="mt-2 space-y-2">
                                    {assigned_work.map((task) => (
                                        <li key={task.id} className="text-sm text-ink">
                                            {task.title} · {task.status_label}
                                            {task.assignee_name ? ` · ${task.assignee_name}` : ''}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                        <div>
                            <h3 className="text-sm font-semibold text-ink">Activity</h3>
                            {activity.length === 0 ? (
                                <p className="mt-1 text-sm text-muted">No activity yet.</p>
                            ) : (
                                <ul className="mt-2 space-y-2">
                                    {activity.map((item) => (
                                        <li key={item.id} className="text-sm text-muted">
                                            {item.body ?? item.type}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                        <Link href={`/admin/work`} className="block text-sm font-medium text-brand">
                            Open My Work
                        </Link>
                    </div>
                ) : null}
            </Drawer>

            <Drawer
                open={tab === 'opportunities' && selected_opportunity !== null}
                title={selected_opportunity?.title ?? 'Opportunity'}
                onClose={() => visit({ opportunity: '' })}
            >
                {selected_opportunity ? (
                    <div className="space-y-4">
                        <p className="text-sm text-muted">
                            {selected_opportunity.business_id} · {selected_opportunity.sector ?? 'No sector'}
                        </p>
                        <StatusBadge status={selected_opportunity.status_label} />
                        {can_manage ? (
                            <div className="flex flex-wrap gap-2">
                                {opportunity_statuses.map((status) => (
                                    <Button
                                        key={status.value}
                                        type="button"
                                        onClick={() =>
                                            router.post(`/admin/investors/opportunities/${selected_opportunity.id}/stage`, {
                                                status: status.value,
                                            })
                                        }
                                    >
                                        {status.label}
                                    </Button>
                                ))}
                            </div>
                        ) : null}
                    </div>
                ) : null}
            </Drawer>

            {tab === 'interests' && can_manage ? (
                <div className="mt-2 space-y-2">
                    {interests.data.map((interest) => (
                        <div key={interest.id} className="flex flex-wrap items-center justify-between gap-2 rounded-md border border-line bg-white px-3 py-2 text-sm">
                            <span>
                                {interest.investor?.display_name} → {interest.opportunity?.title} · {interest.status_label}
                            </span>
                            <div className="flex flex-wrap gap-2">
                                {interest_statuses
                                    .filter((status) => status.value !== interest.status)
                                    .map((status) => (
                                        <Button
                                            key={status.value}
                                            type="button"
                                            onClick={() =>
                                                router.put(`/admin/investors/interests/${interest.id}`, {
                                                    status: status.value,
                                                })
                                            }
                                        >
                                            {status.label}
                                        </Button>
                                    ))}
                            </div>
                        </div>
                    ))}
                </div>
            ) : null}
        </AdminLayout>
    );
}
