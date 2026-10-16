import { Link, router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    Button,
    Drawer,
    Input,
    SearchBar,
    Select,
    StatusBadge,
} from '@/Components/Admin/ui';
import DetailHeader from '@/Components/organisms/DetailHeader';
import FilterBar from '@/Components/molecules/FilterBar';
import DataTable from '@/Components/molecules/DataTable';
import type { DataTableColumn } from '@/Components/molecules/DataTable';
import type { AdminInterview, LabeledOption, ScorecardTemplateOption, StaffUserOption } from '@/types/applications';
import type { Paginator } from '@/types/jobs';

type Props = {
    interviews: Paginator<AdminInterview>;
    selected: AdminInterview | null;
    filters: {
        search: string;
        lifecycle_status: string;
        type: string;
        application_id: string;
        interviewer_user_id: string;
        scope: string;
        interview: string;
    };
    interviewTypes: LabeledOption[];
    lifecycleStatuses: LabeledOption[];
    interviewModes: string[];
    interviewOutcomes: string[];
    staffUsers: StaffUserOption[];
    scorecardTemplates: ScorecardTemplateOption[];
    can_manage: boolean;
};

export default function InterviewsIndex({
    interviews,
    selected,
    filters,
    interviewTypes,
    lifecycleStatuses,
    staffUsers,
    can_manage,
}: Props) {
    const [cancelReason, setCancelReason] = useState('');
    const rescheduleForm = useForm({
        scheduled_at: selected?.scheduled_at ?? '',
        mode: selected?.mode ?? 'phone',
        location_or_link: selected?.location_or_link ?? '',
        interviewer_name: selected?.interviewer_name ?? '',
        interviewer_user_id: selected?.interviewer_user_id ? String(selected.interviewer_user_id) : '',
        type: selected?.type ?? '',
        notes: selected?.notes ?? '',
    });

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/interviews', params, { preserveState: true, preserveScroll: true });
    };

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        visit(Object.fromEntries(form.entries()) as Record<string, string>);
    };

    const columns: DataTableColumn<AdminInterview>[] = [
        {
            key: 'candidate',
            header: 'Candidate',
            render: (row) => row.application?.full_name ?? '—',
        },
        {
            key: 'job',
            header: 'Job',
            render: (row) => row.job?.title ?? '—',
        },
        {
            key: 'when',
            header: 'Scheduled',
            render: (row) => row.scheduled_at_label ?? row.scheduled_at ?? '—',
        },
        {
            key: 'type',
            header: 'Type',
            render: (row) => row.type_label ?? row.type ?? '—',
        },
        {
            key: 'status',
            header: 'Lifecycle',
            render: (row) => <StatusBadge status={row.lifecycle_label ?? row.lifecycle_status ?? 'scheduled'} />,
        },
        {
            key: 'interviewer',
            header: 'Interviewer',
            render: (row) => row.interviewer_name || '—',
        },
    ];

    return (
        <AdminLayout title="Interviews" breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Interviews' }]}>
            <div className="space-y-4">
                <DetailHeader
                    headingLevel={2}
                    title="Application interview schedule"
                    description="All application interviews. Scores never change hiring status."
                    actions={
                        <Link href="/admin/scorecard-templates" className="text-sm font-medium text-brand">
                            Scorecard templates
                        </Link>
                    }
                />

                <FilterBar onSubmit={filter}>
                    <SearchBar name="search" defaultValue={filters.search} placeholder="Search candidate or application ID" />
                    <Select name="lifecycle_status" defaultValue={filters.lifecycle_status} aria-label="Lifecycle">
                        <option value="">All statuses</option>
                        {lifecycleStatuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </Select>
                    <Select name="type" defaultValue={filters.type} aria-label="Type">
                        <option value="">All types</option>
                        {interviewTypes.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </Select>
                    <Select name="scope" defaultValue={filters.scope} aria-label="Scope">
                        <option value="">All dates</option>
                        <option value="mine">Mine</option>
                        <option value="today">Today</option>
                        <option value="upcoming">Upcoming</option>
                    </Select>
                    <Select name="interviewer_user_id" defaultValue={filters.interviewer_user_id} aria-label="Interviewer">
                        <option value="">All interviewers</option>
                        {staffUsers.map((user) => (
                            <option key={user.id} value={user.id}>
                                {user.name}
                            </option>
                        ))}
                    </Select>
                    <Button type="submit">Filter</Button>
                </FilterBar>

                <DataTable
                    columns={columns}
                    rows={interviews.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No interviews match these filters."
                    emptyDescription="Schedule interviews from an application. Existing interview #1 is preserved."
                    selectedKey={selected?.id ?? null}
                    onRowClick={(row) => visit({ interview: String(row.id) })}
                />
                <Pagination links={interviews.links} />
            </div>

            <Drawer
                open={selected !== null}
                title={selected?.application?.full_name ?? 'Interview'}
                onClose={() => visit({ interview: '' })}
            >
                {selected ? (
                    <div className="space-y-4">
                        <p className="text-sm text-muted">
                            Interview #{selected.sort_order} · {selected.lifecycle_label} · {selected.scheduled_at_label}
                        </p>
                        {selected.application?.workspace_url ? (
                            <Link href={selected.application.workspace_url} className="text-sm font-medium text-brand">
                                Open application
                            </Link>
                        ) : null}
                        {selected.scorecard ? (
                            <p className="text-sm text-muted">
                                Scorecard recorded
                                {selected.scorecard.numeric_score !== null ? ` · ${selected.scorecard.numeric_score}` : ''}.
                                This does not change hiring status.
                            </p>
                        ) : null}
                        {can_manage ? (
                            <div className="flex flex-wrap gap-2">
                                {selected.can_confirm ? (
                                    <Button onClick={() => router.post(`/admin/interviews/${selected.id}/confirm`)}>Confirm</Button>
                                ) : null}
                                {selected.can_cancel ? (
                                    <div className="flex gap-2">
                                        <Input
                                            placeholder="Cancel reason"
                                            value={cancelReason}
                                            onChange={(event) => setCancelReason(event.target.value)}
                                        />
                                        <Button
                                            variant="danger"
                                            onClick={() =>
                                                router.post(`/admin/interviews/${selected.id}/cancel`, {
                                                    cancelled_reason: cancelReason,
                                                })
                                            }
                                        >
                                            Cancel
                                        </Button>
                                    </div>
                                ) : null}
                            </div>
                        ) : null}
                        {can_manage && selected.can_reschedule ? (
                            <form
                                className="space-y-2"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    rescheduleForm.transform((data) => ({
                                        ...data,
                                        interviewer_user_id: data.interviewer_user_id || null,
                                        type: data.type || null,
                                    }));
                                    rescheduleForm.post(`/admin/interviews/${selected.id}/reschedule`);
                                }}
                            >
                                <h3 className="text-sm font-semibold text-ink">Reschedule</h3>
                                <Input
                                    type="datetime-local"
                                    value={rescheduleForm.data.scheduled_at}
                                    onChange={(event) => rescheduleForm.setData('scheduled_at', event.target.value)}
                                    required
                                />
                                <Button type="submit" disabled={rescheduleForm.processing}>
                                    Create next interview
                                </Button>
                            </form>
                        ) : null}
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}
