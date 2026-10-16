import { router, useForm } from '@inertiajs/react';
import { useEffect, type FormEvent } from 'react';
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
import DetailHeader from '@/Components/organisms/DetailHeader';
import FilterBar from '@/Components/molecules/FilterBar';
import DataTable from '@/Components/molecules/DataTable';
import type { DataTableColumn } from '@/Components/molecules/DataTable';
import type { LabeledOption, StaffUserOption } from '@/types/applications';
import type { Paginator } from '@/types/jobs';

type AdminTask = {
    id: number;
    title: string;
    body: string | null;
    status: string;
    status_label: string;
    due_at: string | null;
    due_at_label: string | null;
    assigned_to: number | null;
    assignee_name: string | null;
    application_id: number | null;
    job_id: number | null;
    candidate_profile_id: number | null;
    employer_profile_id?: number | null;
    employer_enquiry_id?: number | null;
    crm_lead_id?: number | null;
    crm_opportunity_id?: number | null;
    application?: { id: number; full_name: string; workspace_url?: string | null } | null;
    job?: { id: number; title: string | null } | null;
    employer?: { id: number; company_name: string | null } | null;
    crm_lead?: { id: number; company_name: string | null } | null;
    crm_opportunity?: { id: number; title: string | null } | null;
};

type Props = {
    tasks: Paginator<AdminTask>;
    selected: AdminTask | null;
    filters: {
        search: string;
        status: string;
        assigned_to: string;
        application_id: string;
        job_id: string;
        employer_profile_id?: string;
        crm_lead_id?: string;
        crm_opportunity_id?: string;
        due?: string;
        task: string;
    };
    statuses: LabeledOption[];
    staffUsers: StaffUserOption[];
    can_manage: boolean;
    can_manage_employers?: boolean;
};

export default function TasksIndex({ tasks, selected, filters, statuses, staffUsers, can_manage }: Props) {
    const createForm = useForm({
        title: '',
        body: '',
        due_at: '',
        assigned_to: '',
        application_id: '',
        job_id: '',
        candidate_profile_id: '',
        employer_profile_id: '',
        employer_enquiry_id: '',
        crm_lead_id: '',
        crm_opportunity_id: '',
    });
    const updateForm = useForm({
        title: selected?.title ?? '',
        body: selected?.body ?? '',
        due_at: selected?.due_at ?? '',
        assigned_to: selected?.assigned_to ? String(selected.assigned_to) : '',
    });

    useEffect(() => {
        updateForm.setData({
            title: selected?.title ?? '',
            body: selected?.body ?? '',
            due_at: selected?.due_at ?? '',
            assigned_to: selected?.assigned_to ? String(selected.assigned_to) : '',
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selected?.id]);

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/tasks', params, { preserveState: true, preserveScroll: true });
    };

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        visit(Object.fromEntries(form.entries()) as Record<string, string>);
    };

    const columns: DataTableColumn<AdminTask>[] = [
        { key: 'title', header: 'Task', render: (row) => row.title },
        { key: 'assignee', header: 'Assigned to', render: (row) => row.assignee_name ?? 'Unassigned' },
        { key: 'due', header: 'Due', render: (row) => row.due_at_label ?? '—' },
        {
            key: 'status',
            header: 'Status',
            render: (row) => <StatusBadge status={row.status_label} />,
        },
        {
            key: 'linked',
            header: 'Linked to',
            render: (row) =>
                row.application?.full_name
                ?? row.job?.title
                ?? row.employer?.company_name
                ?? row.crm_lead?.company_name
                ?? row.crm_opportunity?.title
                ?? '—',
        },
    ];

    return (
        <AdminLayout title="Tasks" breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Tasks' }]}>
            <div className="space-y-4">
                <DetailHeader
                    headingLevel={2}
                    title="Staff follow-up worklist"
                    description="Staff assignment only. No teams or job ownership fields."
                />

                <FilterBar onSubmit={filter}>
                    <SearchBar name="search" defaultValue={filters.search} placeholder="Search tasks" />
                    <Select name="status" defaultValue={filters.status} aria-label="Status">
                        <option value="">All statuses</option>
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
                    <Select name="due" defaultValue={filters.due ?? ''} aria-label="Due">
                        <option value="">Any due date</option>
                        <option value="overdue">Overdue</option>
                        <option value="today">Due today</option>
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
                                application_id: data.application_id || null,
                                job_id: data.job_id || null,
                                candidate_profile_id: data.candidate_profile_id || null,
                                employer_profile_id: data.employer_profile_id || null,
                                employer_enquiry_id: data.employer_enquiry_id || null,
                                crm_lead_id: data.crm_lead_id || null,
                                crm_opportunity_id: data.crm_opportunity_id || null,
                                due_at: data.due_at || null,
                            }));
                            createForm.post('/admin/tasks', { onSuccess: () => createForm.reset() });
                        }}
                    >
                        <Input
                            placeholder="Task title"
                            value={createForm.data.title}
                            onChange={(event) => createForm.setData('title', event.target.value)}
                            required
                        />
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
                        <Input
                            type="datetime-local"
                            value={createForm.data.due_at}
                            onChange={(event) => createForm.setData('due_at', event.target.value)}
                            aria-label="Due at"
                        />
                        <Input
                            placeholder="Application ID (optional)"
                            value={createForm.data.application_id}
                            onChange={(event) => createForm.setData('application_id', event.target.value)}
                        />
                        <Input
                            placeholder="Job ID (optional)"
                            value={createForm.data.job_id}
                            onChange={(event) => createForm.setData('job_id', event.target.value)}
                        />
                        <Input
                            placeholder="Employer ID (optional)"
                            value={createForm.data.employer_profile_id}
                            onChange={(event) => createForm.setData('employer_profile_id', event.target.value)}
                        />
                        <Input
                            placeholder="CRM lead ID (optional)"
                            value={createForm.data.crm_lead_id}
                            onChange={(event) => createForm.setData('crm_lead_id', event.target.value)}
                        />
                        <Input
                            placeholder="CRM opportunity ID (optional)"
                            value={createForm.data.crm_opportunity_id}
                            onChange={(event) => createForm.setData('crm_opportunity_id', event.target.value)}
                        />
                        <Textarea
                            className="md:col-span-2"
                            placeholder="Notes"
                            value={createForm.data.body}
                            onChange={(event) => createForm.setData('body', event.target.value)}
                        />
                        <div>
                            <Button type="submit" disabled={createForm.processing}>
                                Create task
                            </Button>
                        </div>
                    </form>
                ) : null}

                <DataTable
                    columns={columns}
                    rows={tasks.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No tasks match these filters."
                    emptyDescription="Create a task and assign it to a staff user."
                    selectedKey={selected?.id ?? null}
                    onRowClick={(row) => visit({ task: String(row.id) })}
                />
                <Pagination links={tasks.links} />
            </div>

            <Drawer open={selected !== null} title={selected?.title ?? 'Task'} onClose={() => visit({ task: '' })}>
                {selected ? (
                    <div className="space-y-3">
                        <StatusBadge status={selected.status_label} />
                        <p className="text-sm text-muted">{selected.body || 'No notes.'}</p>
                        <p className="text-sm text-muted">
                            Assigned to {selected.assignee_name ?? 'nobody'}
                            {selected.due_at_label ? ` · due ${selected.due_at_label}` : ''}
                        </p>
                        {can_manage ? (
                            <form
                                className="space-y-2 rounded-lg border border-line p-3"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    updateForm.transform((data) => ({
                                        ...data,
                                        assigned_to: data.assigned_to || null,
                                        due_at: data.due_at || null,
                                        body: data.body || null,
                                    }));
                                    updateForm.put(`/admin/tasks/${selected.id}`, { preserveScroll: true });
                                }}
                            >
                                <h3 className="text-sm font-semibold text-ink">Update / reassign</h3>
                                <Input
                                    value={updateForm.data.title}
                                    onChange={(event) => updateForm.setData('title', event.target.value)}
                                    required
                                    aria-label="Task title"
                                />
                                <Select
                                    value={updateForm.data.assigned_to}
                                    onChange={(event) => updateForm.setData('assigned_to', event.target.value)}
                                    aria-label="Reassign to"
                                >
                                    <option value="">Unassigned</option>
                                    {staffUsers.map((user) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name}
                                        </option>
                                    ))}
                                </Select>
                                <Input
                                    type="datetime-local"
                                    value={updateForm.data.due_at ?? ''}
                                    onChange={(event) => updateForm.setData('due_at', event.target.value)}
                                    aria-label="Due at"
                                />
                                <Textarea
                                    placeholder="Notes"
                                    value={updateForm.data.body ?? ''}
                                    onChange={(event) => updateForm.setData('body', event.target.value)}
                                />
                                {updateForm.errors.assigned_to ? (
                                    <p className="text-sm text-danger">{updateForm.errors.assigned_to}</p>
                                ) : null}
                                <Button type="submit" disabled={updateForm.processing}>
                                    Save task
                                </Button>
                            </form>
                        ) : null}
                        {selected.status === 'open' ? (
                            <div className="flex flex-wrap gap-2">
                                <Button onClick={() => router.post(`/admin/tasks/${selected.id}/complete`)}>Complete</Button>
                                <Button variant="danger" onClick={() => router.post(`/admin/tasks/${selected.id}/cancel`)}>
                                    Cancel
                                </Button>
                            </div>
                        ) : null}
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}
