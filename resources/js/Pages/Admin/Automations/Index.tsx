import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    Button,
    DataTable,
    Drawer,
    Input,
    Select,
    StatusBadge,
    Textarea,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { Paginator } from '@/types/jobs';

type AutomationRow = {
    id: number;
    name: string;
    description: string | null;
    trigger: string;
    trigger_label: string;
    module: string;
    scope: string;
    scope_label: string;
    job_id: number | null;
    employer_profile_id: number | null;
    is_enabled: boolean;
    conditions: {
        min_match_percent?: number;
        application_status?: string;
        delay_hours?: number;
        allow_insufficient_data?: boolean;
        application_origin?: string;
    };
    actions: string[];
    execution_count: number;
};

type HistoryRow = {
    id: number;
    automation_name?: string | null;
    trigger_label?: string | null;
    status_label: string;
    match_percent: number | null;
    match_state?: string | null;
    match_label?: string | null;
    application_id: number | null;
    job_id?: number | null;
    recommendation?: string | null;
    reason?: string | null;
    matching_factors?: string[];
    potential_gaps?: string[];
    application_origin?: string | null;
    error_message?: string | null;
    executed_at: string | null;
};

type Option = { value: string; label: string };
type SelectOption = { id: number; label: string };

type Props = {
    automations: Paginator<AutomationRow>;
    selected: AutomationRow | null;
    history: HistoryRow[];
    triggers: Array<Option & { module: string; scheduled: boolean }>;
    scopes: Option[];
    actions: Option[];
    statuses: Option[];
    job_options: SelectOption[];
    employer_options: SelectOption[];
    can_manage: boolean;
};

export default function AutomationsIndex({
    automations,
    selected,
    history,
    triggers,
    scopes,
    actions,
    statuses,
    job_options,
    employer_options,
    can_manage,
}: Props) {
    const createForm = useForm({
        name: '',
        description: '',
        trigger: 'application_submitted',
        scope: 'global',
        job_id: '',
        employer_profile_id: '',
        is_enabled: true,
        actions: ['recommend_screening', 'create_task', 'notify_admin'] as string[],
        conditions: {
            min_match_percent: '',
            application_status: '',
            delay_hours: '',
            allow_insufficient_data: false,
            application_origin: '',
        },
    });

    const updateForm = useForm({
        name: selected?.name ?? '',
    });

    const columns: DataTableColumn<AutomationRow>[] = [
        { key: 'name', header: 'Name', render: (row) => row.name },
        { key: 'trigger_label', header: 'Trigger', render: (row) => row.trigger_label },
        { key: 'module', header: 'Module', render: (row) => row.module },
        {
            key: 'is_enabled',
            header: 'Status',
            render: (row) => <StatusBadge status={row.is_enabled ? 'Active' : 'Paused'} />,
        },
        { key: 'execution_count', header: 'Runs', render: (row) => String(row.execution_count) },
    ];

    const toggleAction = (value: string) => {
        const next = createForm.data.actions.includes(value)
            ? createForm.data.actions.filter((item) => item !== value)
            : [...createForm.data.actions, value];
        createForm.setData('actions', next);
    };

    const submitCreate = (event: FormEvent) => {
        event.preventDefault();
        createForm.post('/admin/automations');
    };

    const submitUpdate = (event: FormEvent) => {
        event.preventDefault();
        if (!selected) {
            return;
        }
        updateForm.transform(() => ({
            name: updateForm.data.name,
            description: selected.description,
            trigger: selected.trigger,
            scope: selected.scope,
            job_id: selected.job_id,
            employer_profile_id: selected.employer_profile_id,
            is_enabled: selected.is_enabled,
            actions: selected.actions,
            conditions: selected.conditions,
        }));
        updateForm.put(`/admin/automations/${selected.id}`);
    };

    return (
        <AdminLayout title="Recruitment automation" breadcrumbs={[{ label: 'Automation' }]}>
            <div className="space-y-4">
                {can_manage ? (
                    <form className="grid gap-3 md:grid-cols-2" onSubmit={submitCreate}>
                        <Input
                            placeholder="Rule name"
                            value={createForm.data.name}
                            onChange={(event) => createForm.setData('name', event.target.value)}
                            required
                        />
                        <Select
                            value={createForm.data.trigger}
                            onChange={(event) => createForm.setData('trigger', event.target.value)}
                            aria-label="Trigger"
                        >
                            {triggers.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={createForm.data.scope}
                            onChange={(event) => createForm.setData('scope', event.target.value)}
                            aria-label="Scope"
                        >
                            {scopes.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </Select>
                        {createForm.data.scope === 'job' ? (
                            <Select
                                value={createForm.data.job_id}
                                onChange={(event) => createForm.setData('job_id', event.target.value)}
                                aria-label="Job"
                            >
                                <option value="">Select job</option>
                                {job_options.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.label}
                                    </option>
                                ))}
                            </Select>
                        ) : null}
                        {createForm.data.scope === 'employer' ? (
                            <Select
                                value={createForm.data.employer_profile_id}
                                onChange={(event) => createForm.setData('employer_profile_id', event.target.value)}
                                aria-label="Employer"
                            >
                                <option value="">Select employer</option>
                                {employer_options.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.label}
                                    </option>
                                ))}
                            </Select>
                        ) : null}
                        <Input
                            type="number"
                            placeholder="Minimum match % (optional)"
                            value={createForm.data.conditions.min_match_percent}
                            onChange={(event) => createForm.setData('conditions', {
                                ...createForm.data.conditions,
                                min_match_percent: event.target.value,
                            })}
                        />
                        <Select
                            value={createForm.data.conditions.application_status}
                            onChange={(event) => createForm.setData('conditions', {
                                ...createForm.data.conditions,
                                application_status: event.target.value,
                            })}
                            aria-label="Application status"
                        >
                            <option value="">Any application status</option>
                            {statuses.map((item) => (
                                <option key={item.value} value={item.value}>
                                    {item.label}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={createForm.data.conditions.application_origin}
                            onChange={(event) => createForm.setData('conditions', {
                                ...createForm.data.conditions,
                                application_origin: event.target.value,
                            })}
                            aria-label="Application origin"
                        >
                            <option value="">Any origin (OurNear Jobs guest or account)</option>
                            <option value="guest">OurNear Jobs / guest apply</option>
                            <option value="account">Account application</option>
                        </Select>
                        <Input
                            type="number"
                            placeholder="Delay hours for scheduled rules"
                            value={createForm.data.conditions.delay_hours}
                            onChange={(event) => createForm.setData('conditions', {
                                ...createForm.data.conditions,
                                delay_hours: event.target.value,
                            })}
                        />
                        <Textarea
                            className="md:col-span-2"
                            placeholder="Description"
                            value={createForm.data.description}
                            onChange={(event) => createForm.setData('description', event.target.value)}
                        />
                        <div className="md:col-span-2 space-y-1">
                            {actions.map((action) => (
                                <label key={action.value} className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={createForm.data.actions.includes(action.value)}
                                        onChange={() => toggleAction(action.value)}
                                    />
                                    {action.label}
                                </label>
                            ))}
                            <p className="text-xs text-muted">
                                Uses existing ScreeningMatchService scores. Recruiter review is required before shortlist.
                            </p>
                        </div>
                        <div>
                            <Button type="submit" disabled={createForm.processing}>
                                Create automation
                            </Button>
                        </div>
                    </form>
                ) : null}

                <DataTable
                    columns={columns}
                    rows={automations.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No automations yet."
                    emptyDescription="Create a rule to recommend recruiter review from existing screening scores."
                    selectedKey={selected?.id ?? null}
                    onRowClick={(row) => router.get('/admin/automations', { automation: row.id }, { preserveState: true })}
                />
                <Pagination links={automations.links} />
            </div>

            <Drawer
                open={selected !== null}
                title={selected?.name ?? 'Automation'}
                onClose={() => router.get('/admin/automations', {}, { preserveState: true })}
            >
                {selected ? (
                    <div className="space-y-3">
                        <StatusBadge status={selected.is_enabled ? 'Active' : 'Paused'} />
                        <p className="text-sm text-muted">
                            {selected.trigger_label} · {selected.scope_label} · {selected.module}
                        </p>
                        {can_manage ? (
                            <>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => router.post(`/admin/automations/${selected.id}/toggle`)}
                                >
                                    {selected.is_enabled ? 'Disable' : 'Enable'}
                                </Button>
                                <form className="space-y-2" onSubmit={submitUpdate}>
                                    <Input
                                        value={updateForm.data.name}
                                        onChange={(event) => updateForm.setData('name', event.target.value)}
                                    />
                                    <Button type="submit" disabled={updateForm.processing}>
                                        Save name
                                    </Button>
                                </form>
                            </>
                        ) : null}
                        <div>
                            <h3 className="mb-2 text-sm font-semibold">History</h3>
                            <ul className="space-y-2 text-sm">
                                {history.length === 0 ? <li>No executions yet.</li> : null}
                                {history.map((item) => (
                                    <li key={item.id} className="rounded-md bg-surface px-3 py-2">
                                        <p className="font-medium text-ink">
                                            {item.status_label}
                                            {item.match_label ? ` · ${item.match_label}` : ''}
                                        </p>
                                        <p className="text-xs text-muted">
                                            {item.trigger_label ?? 'Trigger'}
                                            {item.application_id ? ` · application ${item.application_id}` : ''}
                                            {item.job_id ? ` · job ${item.job_id}` : ''}
                                            {item.application_origin ? ` · ${item.application_origin}` : ''}
                                            {item.executed_at ? ` · ${item.executed_at}` : ''}
                                        </p>
                                        {item.recommendation ? <p className="mt-1 text-xs">{item.recommendation}</p> : null}
                                        {item.reason ? <p className="mt-1 text-xs text-muted">Skipped: {item.reason}</p> : null}
                                        {item.error_message ? <p className="mt-1 text-xs text-muted">Failed: {item.error_message}</p> : null}
                                        {(item.matching_factors ?? []).length > 0 ? (
                                            <p className="mt-1 text-xs">✓ {(item.matching_factors ?? []).join(', ')}</p>
                                        ) : null}
                                        {(item.potential_gaps ?? []).length > 0 ? (
                                            <p className="mt-1 text-xs">⚠ {(item.potential_gaps ?? []).join(', ')}</p>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}
