import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
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
import type { AdminApplication } from '@/types/applications';
import type { AdminJob, Paginator } from '@/types/jobs';

type EmployerSummary = {
    id: number;
    business_id: string | null;
    company_name: string;
    contact_person?: string | null;
    email?: string | null;
    mobile_number?: string | null;
    industry?: string;
    workspace_url?: string | null;
};

type SubmissionListItem = {
    id: number;
    business_id: string | null;
    status: string;
    submitted_at: string | null;
    updated_at: string | null;
    employer: EmployerSummary | null;
    job: { id: number; business_id: string | null; title: string; slug?: string } | null;
    candidate: { id: number | null; business_id: string | null; name: string | null };
    application: { id: number; business_id: string | null } | null;
};

type SubmissionDetail = SubmissionListItem & {
    notes: string | null;
    rejection_reason: string | null;
    submitted_by: string | null;
    decided_by: string | null;
    decided_at: string | null;
    can_select: boolean;
    can_reject: boolean;
    can_hold: boolean;
    can_create_placement?: boolean;
    placement?: { id: number; business_id: string | null; status: string } | null;
    has_resume: boolean;
    resume_url: string | null;
    job: AdminJob | null;
    application: AdminApplication | null;
    employer: EmployerSummary | null;
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
    submissions: Paginator<SubmissionListItem>;
    selected: SubmissionDetail | null;
    filters: {
        search: string;
        employer_id: string;
        job_id: string;
        status: string;
        date_from: string;
        date_to: string;
        submission: string;
    };
    options: {
        employers: Array<{ id: number; label: string }>;
        jobs: Array<{ id: number; title: string; business_id: string | null }>;
        statuses: string[];
    };
    statuses: string[];
    rejectionReasons: Array<{ id: number; name: string; selectable?: boolean }>;
};

function activityTitle(activity: SubmissionDetail['activities'][number]): string {
    if (activity.type === 'status_changed' || activity.type === 'submission_status_changed') {
        return `Status: ${activity.from_status ?? '—'} → ${activity.to_status ?? '—'}`;
    }

    return activity.body || activity.type.replace(/_/g, ' ');
}

export default function SubmissionsIndex({ submissions, selected, filters, options, statuses, rejectionReasons }: Props) {
    const [tab, setTab] = useState('overview');
    const [confirm, setConfirm] = useState<'select' | 'hold' | 'place' | null>(null);
    const [rejectOpen, setRejectOpen] = useState(false);
    const [notes, setNotes] = useState('');
    const [rejectionReasonId, setRejectionReasonId] = useState('');

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/submissions', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ submission: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        visit({
            ...Object.fromEntries(new FormData(event.currentTarget).entries()),
            submission: filters.submission,
        } as Record<string, string>);
    };

    const columns: Array<DataTableColumn<SubmissionListItem>> = [
        { key: 'id', header: 'Submission ID', className: 'font-medium text-ink', render: (row) => row.business_id ?? '—' },
        { key: 'employer', header: 'Employer', render: (row) => row.employer?.company_name ?? '—' },
        { key: 'job', header: 'Job', render: (row) => row.job?.title ?? '—' },
        { key: 'candidate', header: 'Candidate', className: 'font-medium text-ink', render: (row) => row.candidate.name ?? '—' },
        { key: 'candidate_id', header: 'Candidate ID', render: (row) => row.candidate.business_id ?? '—' },
        { key: 'application', header: 'Application ID', render: (row) => row.application?.business_id ?? '—' },
        { key: 'submitted', header: 'Submitted', render: (row) => row.submitted_at ?? '—' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'updated', header: 'Updated', render: (row) => row.updated_at ?? '—' },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button variant="ghost" onClick={() => visit({ submission: String(row.id) })}>
                    View
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout title="Employer Submissions">
            <div
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Employer Submissions</h1>
                    <p className="mt-1 text-sm text-muted">Shortlisted candidates presented to an employer for a specific job. This is not placement.</p>
                </div>
                <FilterBar onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Submission, employer, job, candidate, application ID" />
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
                    <Select name="status" defaultValue={filters.status} aria-label="Submission status">
                        <option value="">All statuses</option>
                        {statuses.map((status) => (
                            <option key={status} value={status}>
                                {status}
                            </option>
                        ))}
                    </Select>
                    <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="Submitted from" />
                    <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="Submitted to" />
                    <Button type="submit">Filter</Button>
                </FilterBar>
                <DataTable
                    columns={columns}
                    rows={submissions.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No submissions match these filters"
                    emptyDescription="Submit a shortlisted candidate from the Shortlisted workspace."
                    onRowClick={(row) => visit({ submission: String(row.id) })}
                    selectedKey={selected?.id ?? null}
                />
                <Pagination links={submissions.links} />
            </div>

            <Drawer open={selected !== null} title={selected?.business_id ?? 'Submission'} size="xl" onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-4">
                        <Tabs
                            tabs={[
                                { id: 'overview', label: 'Overview' },
                                { id: 'candidate', label: 'Candidate' },
                                { id: 'job', label: 'Job' },
                                { id: 'resume', label: 'Resume' },
                                { id: 'employer', label: 'Employer' },
                                { id: 'timeline', label: 'Timeline' },
                                { id: 'decision', label: 'Decision' },
                            ]}
                            active={tab}
                            onChange={setTab}
                        />
                        {tab === 'overview' ? (
                            <dl className="grid gap-2 text-sm">
                                {[
                                    ['Submission ID', selected.business_id ?? '—'],
                                    ['Employer', selected.employer?.company_name ?? '—'],
                                    ['Job', selected.job?.title ?? '—'],
                                    ['Candidate', selected.candidate.name ?? '—'],
                                    ['Application', selected.application?.business_id ?? '—'],
                                    ['Submitted', selected.submitted_at ?? '—'],
                                    ['Status', selected.status],
                                ].map(([label, value]) => (
                                    <div key={label} className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">{label}</dt>
                                        <dd className="col-span-2 text-ink">{value}</dd>
                                    </div>
                                ))}
                            </dl>
                        ) : null}
                        {tab === 'candidate' ? (
                            <div className="space-y-3 text-sm">
                                <p className="font-medium text-ink">{selected.candidate.name}</p>
                                <p className="text-muted">{selected.candidate.business_id ?? 'Guest application'}</p>
                                {selected.candidate.id ? (
                                    <Link href={`/admin/candidates?candidate=${selected.candidate.id}`} className="inline-block font-medium text-brand">
                                        Open Candidate Workspace
                                    </Link>
                                ) : (
                                    <p className="text-muted">Guest applications have no candidate account workspace.</p>
                                )}
                            </div>
                        ) : null}
                        {tab === 'job' ? (
                            <div className="space-y-3 text-sm">
                                <p className="font-medium text-ink">{selected.job?.title}</p>
                                <p className="text-muted">{selected.job?.business_id ?? '—'}</p>
                                {selected.job?.slug ? (
                                    <Link href={`/admin/jobs?job=${selected.job.slug}`} className="inline-block font-medium text-brand">
                                        Open Job Workspace
                                    </Link>
                                ) : null}
                            </div>
                        ) : null}
                        {tab === 'resume' ? (
                            selected.has_resume && selected.resume_url ? (
                                <a href={selected.resume_url} className="inline-block text-sm font-medium text-brand">
                                    Download resume
                                </a>
                            ) : (
                                <p className="text-sm text-muted">No resume on file.</p>
                            )
                        ) : null}
                        {tab === 'employer' ? (
                            <div className="space-y-3 text-sm">
                                <p className="font-medium text-ink">{selected.employer?.company_name}</p>
                                <p className="text-muted">
                                    {selected.employer?.business_id ?? '—'} · {selected.employer?.contact_person ?? '—'}
                                </p>
                                {selected.employer?.id ? (
                                    <Link href={`/admin/employers?employer=${selected.employer.id}`} className="inline-block font-medium text-brand">
                                        Open Employer Workspace
                                    </Link>
                                ) : null}
                            </div>
                        ) : null}
                        {tab === 'timeline' ? (
                            <Timeline
                                items={(selected.activities ?? []).map((activity) => ({
                                    id: activity.id,
                                    title: activityTitle(activity),
                                    meta: `${activity.actor_name ?? 'System'} · ${activity.created_at ?? ''}`,
                                }))}
                                empty="No submission activity yet."
                            />
                        ) : null}
                        {tab === 'decision' ? (
                            <div className="space-y-4">
                                <p className="text-sm text-muted">
                                    Current decision: <StatusBadge status={selected.status} />
                                </p>
                                <Textarea
                                    value={notes}
                                    onChange={(event) => setNotes(event.target.value)}
                                    placeholder="Decision notes"
                                />
                                <div className="flex flex-wrap gap-2">
                                    <Button disabled={!selected.can_select} onClick={() => setConfirm('select')}>
                                        Selected
                                    </Button>
                                    <Button variant="danger" disabled={!selected.can_reject} onClick={() => setRejectOpen(true)}>
                                        Rejected
                                    </Button>
                                    <Button variant="secondary" disabled={!selected.can_hold} onClick={() => setConfirm('hold')}>
                                        On hold
                                    </Button>
                                    <Button disabled={!selected.can_create_placement} onClick={() => setConfirm('place')}>
                                        Create placement
                                    </Button>
                                </div>
                                {selected.placement ? (
                                    <Link href={`/admin/placements?placement=${selected.placement.id}`} className="inline-block text-sm font-medium text-brand">
                                        Open placement {selected.placement.business_id}
                                    </Link>
                                ) : (
                                    <p className="text-xs text-muted">Selection does not create a placement. Create placement only after the employer has selected this submission.</p>
                                )}
                            </div>
                        ) : null}
                    </div>
                ) : null}
            </Drawer>

            <ConfirmDialog
                open={confirm !== null}
                title={
                    confirm === 'place'
                        ? 'Create a placement from this selection?'
                        : confirm === 'select'
                          ? 'Record employer selection?'
                          : 'Put this submission on hold?'
                }
                description={
                    confirm === 'place'
                        ? 'Placement starts from this employer, job, and application only. It does not globally select the candidate.'
                        : confirm === 'select'
                          ? 'Selection is for this employer and job only. Placement is a later step.'
                          : 'The employer has not made a final decision.'
                }
                confirmLabel={confirm === 'place' ? 'Create placement' : confirm === 'select' ? 'Select' : 'On hold'}
                onConfirm={() => {
                    if (!selected || confirm === null) {
                        return;
                    }
                    if (confirm === 'place') {
                        router.post(`/admin/submissions/${selected.id}/place`, { notes });
                    } else {
                        router.post(`/admin/submissions/${selected.id}/${confirm === 'select' ? 'select' : 'hold'}`, { notes });
                    }
                    setConfirm(null);
                }}
                onClose={() => setConfirm(null)}
            />

            <Modal
                open={rejectOpen}
                title="Record employer rejection"
                onClose={() => setRejectOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setRejectOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            variant="danger"
                            onClick={() => {
                                if (!selected) {
                                    return;
                                }
                                router.post(`/admin/submissions/${selected.id}/reject`, {
                                    notes,
                                    rejection_reason_id: rejectionReasonId,
                                });
                                setRejectOpen(false);
                            }}
                        >
                            Reject
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left">
                    <p>The application and shortlist stay on file. This does not automatically set the application to Rejected.</p>
                    <Select value={rejectionReasonId} onChange={(event) => setRejectionReasonId(event.target.value)} aria-label="Rejection reason">
                        <option value="">No reason selected</option>
                        {rejectionReasons
                            .filter((reason) => reason.selectable !== false)
                            .map((reason) => (
                                <option key={reason.id} value={reason.id}>
                                    {reason.name}
                                </option>
                            ))}
                    </Select>
                </div>
            </Modal>
        </AdminLayout>
    );
}
