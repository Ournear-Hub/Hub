import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import RecruitmentChain from '@/Components/Admin/RecruitmentChain';
import {
    Button,
    ConfirmDialog,
    Drawer,
    Input,
    Modal,
    SearchBar,
    Select,
    StatusBadge,
    Tabs,
    Textarea,
    Timeline,
} from '@/Components/Admin/ui';
import DetailHeader from '@/Components/organisms/DetailHeader';
import FilterBar from '@/Components/molecules/FilterBar';
import DataTable from '@/Components/molecules/DataTable';
import type { DataTableColumn } from '@/Components/molecules/DataTable';
import { recorded } from '@/lib/recorded';
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
    response_status?: string;
    selection_outcome?: string;
    submitted_at: string | null;
    submitted_by?: string | null;
    updated_at: string | null;
    employer: EmployerSummary | null;
    job: { id: number; business_id: string | null; title: string; slug?: string } | null;
    candidate: { id: number | null; business_id: string | null; name: string | null };
    application: { id: number; business_id: string | null } | null;
    links?: Record<string, string | null | undefined>;
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
    can_review?: boolean;
    can_create_placement?: boolean;
    can_request_interview?: boolean;
    interview_requested_available?: boolean;
    placement?: { id: number; business_id: string | null; status: string } | null;
    has_resume: boolean;
    resume_url: string | null;
    package?: Array<{ label: string; available: boolean; href: string | null; note: string | null }>;
    chain?: Array<{ key: string; label: string; value?: string | null; href?: string | null; status?: string | null }>;
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
        candidate_id: string;
        status: string;
        response_status: string;
        date_from: string;
        date_to: string;
        submission: string;
    };
    options: {
        employers: Array<{ id: number; label: string }>;
        jobs: Array<{ id: number; title: string; business_id: string | null }>;
        candidates?: Array<{ id: number; label: string }>;
        statuses: string[];
        response_statuses?: Array<{ value: string; label: string }>;
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
    const [confirm, setConfirm] = useState<'select' | 'hold' | 'place' | 'review' | null>(null);
    const [rejectOpen, setRejectOpen] = useState(false);
    const [notes, setNotes] = useState('');
    const [rejectionReasonId, setRejectionReasonId] = useState('');

    const visit = (overrides: Record<string, string | undefined> = {}, replaceAll = false) => {
        const next = replaceAll ? overrides : { ...filters, ...overrides };
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
        { key: 'id', header: 'Submission ID', className: 'font-medium text-ink', render: (row) => recorded(row.business_id) },
        { key: 'employer', header: 'Employer', render: (row) => recorded(row.employer?.company_name) },
        { key: 'job', header: 'Job', render: (row) => recorded(row.job?.title) },
        { key: 'candidate', header: 'Candidate', className: 'font-medium text-ink', render: (row) => recorded(row.candidate.name) },
        { key: 'candidate_id', header: 'Candidate ID', render: (row) => recorded(row.candidate.business_id) },
        { key: 'application', header: 'Application ID', render: (row) => recorded(row.application?.business_id) },
        { key: 'submitted', header: 'Submitted', render: (row) => recorded(row.submitted_at) },
        { key: 'submitted_by', header: 'Submitted by', render: (row) => recorded(row.submitted_by) },
        { key: 'status', header: 'Submission status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'response', header: 'Response status', render: (row) => <StatusBadge status={row.response_status ?? row.status} /> },
        { key: 'selection', header: 'Selection outcome', render: (row) => recorded(row.selection_outcome) },
        { key: 'updated', header: 'Updated', render: (row) => recorded(row.updated_at) },
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
        <AdminLayout
            title="Employer Submissions"
            breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Recruitment Partnership' }]}
        >
            <div
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6">
                    <DetailHeader
                        headingLevel={2}
                        title="Shortlisted candidates for employer review"
                        description="Shortlisted candidates presented to an employer for a specific job. This is not placement."
                    />
                </div>
                <FilterBar className="md:grid-cols-4 xl:grid-cols-6" onSubmit={filter}>
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
                    <Select name="candidate_id" defaultValue={filters.candidate_id} aria-label="Candidate">
                        <option value="">All candidates</option>
                        {(options.candidates ?? []).map((candidate) => (
                            <option key={candidate.id} value={candidate.id}>
                                {candidate.label}
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
                    <Select name="response_status" defaultValue={filters.response_status} aria-label="Response status">
                        <option value="">All responses</option>
                        {(options.response_statuses ?? []).map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </Select>
                    <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="Submitted from" />
                    <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="Submitted to" />
                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit">Filter</Button>
                        <Button type="button" variant="secondary" onClick={() => visit({}, true)}>
                            Reset
                        </Button>
                    </div>
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
                                { id: 'chain', label: 'Recruitment chain' },
                                { id: 'candidate', label: 'Candidate' },
                                { id: 'job', label: 'Job' },
                                { id: 'resume', label: 'Package' },
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
                                    ['Submission ID', recorded(selected.business_id)],
                                    ['Employer', recorded(selected.employer?.company_name)],
                                    ['Job', recorded(selected.job?.title)],
                                    ['Candidate', recorded(selected.candidate.name)],
                                    ['Application', recorded(selected.application?.business_id)],
                                    ['Submitted', recorded(selected.submitted_at)],
                                    ['Submitted by', recorded(selected.submitted_by)],
                                    ['Response', recorded(selected.response_status)],
                                    ['Selection', recorded(selected.selection_outcome)],
                                    ['Status', selected.status],
                                    ['Remarks', recorded(selected.notes)],
                                ].map(([label, value]) => (
                                    <div key={label} className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">{label}</dt>
                                        <dd className="col-span-2 text-ink">{value}</dd>
                                    </div>
                                ))}
                            </dl>
                        ) : null}
                        {tab === 'chain' ? (
                            <div className="space-y-3">
                                <p className="text-sm text-muted">Existing records only. Application and screening details stay on their own workspaces.</p>
                                <RecruitmentChain steps={selected.chain ?? []} />
                            </div>
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
                            <div className="space-y-3 text-sm">
                                {(selected.package ?? []).map((item) => (
                                    <div key={item.label} className="rounded-md bg-surface px-3 py-2">
                                        <p className="font-medium text-ink">{item.label}</p>
                                        {item.available && item.href ? (
                                            <a href={item.href} className="font-medium text-brand">
                                                Download
                                            </a>
                                        ) : (
                                            <p className="text-muted">{item.note ?? 'Not recorded yet'}</p>
                                        )}
                                    </div>
                                ))}
                            </div>
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
                                    Current decision: <StatusBadge status={selected.status} /> · {recorded(selected.selection_outcome)}
                                </p>
                                <Textarea
                                    value={notes}
                                    onChange={(event) => setNotes(event.target.value)}
                                    placeholder="Decision notes"
                                />
                                <div className="flex flex-wrap gap-2">
                                    <Button variant="secondary" disabled={!selected.can_review} onClick={() => setConfirm('review')}>
                                        Under Review
                                    </Button>
                                    <Button disabled={!selected.can_select} onClick={() => setConfirm('select')}>
                                        Selected
                                    </Button>
                                    <Button variant="danger" disabled={!selected.can_reject} onClick={() => setRejectOpen(true)}>
                                        Rejected
                                    </Button>
                                    <Button variant="secondary" disabled={!selected.can_hold} onClick={() => setConfirm('hold')}>
                                        On hold
                                    </Button>
                                    <Button disabled title="Not available in V1.1">
                                        Interview Requested
                                    </Button>
                                    <Button disabled={!selected.can_create_placement} onClick={() => setConfirm('place')}>
                                        Create placement
                                    </Button>
                                </div>
                                <p className="text-xs text-muted">Interview Requested is not available in V1.1. Compatible statuses: Pending (Submitted), Under Review, Selected, Rejected, On Hold.</p>
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
                          : confirm === 'review'
                            ? 'Mark this submission as under review?'
                            : 'Put this submission on hold?'
                }
                description={
                    confirm === 'place'
                        ? 'Placement starts from this employer, job, and application only. It does not globally select the candidate.'
                        : confirm === 'select'
                          ? 'Selection is for this employer and job only. Placement is a later step. Previous status is kept in activity history.'
                          : confirm === 'review'
                            ? 'The previous status, staff member, date, and remarks are recorded in activity.'
                            : 'The employer has not made a final decision. Previous status is kept in activity history.'
                }
                confirmLabel={
                    confirm === 'place'
                        ? 'Create placement'
                        : confirm === 'select'
                          ? 'Select'
                          : confirm === 'review'
                            ? 'Under Review'
                            : 'On hold'
                }
                onConfirm={() => {
                    if (!selected || confirm === null) {
                        return;
                    }
                    if (confirm === 'place') {
                        router.post(`/admin/submissions/${selected.id}/place`, { notes });
                    } else {
                        const action = confirm === 'select' ? 'select' : confirm === 'review' ? 'review' : 'hold';
                        router.post(`/admin/submissions/${selected.id}/${action}`, { notes });
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
