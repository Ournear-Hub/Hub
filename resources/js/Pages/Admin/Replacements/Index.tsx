import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import RecruitmentChain from '@/Components/Admin/RecruitmentChain';
import {
    Button,
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
import type { Paginator } from '@/types/jobs';
import type { EligibleReplacementApplication, ReplacementDetail, ReplacementListItem } from '@/types/replacements';

type Props = {
    replacements: Paginator<ReplacementListItem>;
    selected: ReplacementDetail | null;
    eligibleApplications: EligibleReplacementApplication[];
    filters: {
        search: string;
        employer_id: string;
        job_id: string;
        status: string;
        reason: string;
        date_from: string;
        date_to: string;
        replacement: string;
    };
    options: {
        employers: Array<{ id: number; label: string }>;
        jobs: Array<{ id: number; title: string; business_id: string | null }>;
        statuses: string[];
        reasons: string[];
    };
    statuses: string[];
    reasons: string[];
};

function activityTitle(activity: ReplacementDetail['activities'][number]): string {
    if (activity.from_status && activity.to_status) {
        return `Status: ${activity.from_status} → ${activity.to_status}`;
    }

    return activity.body || activity.type.replace(/_/g, ' ');
}

export default function ReplacementsIndex({
    replacements,
    selected,
    eligibleApplications,
    filters,
    options,
    statuses,
    reasons,
}: Props) {
    const [tab, setTab] = useState('overview');
    const [identifyOpen, setIdentifyOpen] = useState(false);
    const [cancelOpen, setCancelOpen] = useState(false);
    const [applicationId, setApplicationId] = useState('');
    const [notes, setNotes] = useState('');

    const visit = (overrides: Record<string, string | undefined> = {}, replaceAll = false) => {
        const next = replaceAll ? overrides : { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/replacements', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ replacement: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        visit({
            ...Object.fromEntries(new FormData(event.currentTarget).entries()),
            replacement: filters.replacement,
        } as Record<string, string>);
    };

    const columns: Array<DataTableColumn<ReplacementListItem>> = [
        { key: 'id', header: 'Replacement ID', className: 'font-medium text-ink', render: (row) => recorded(row.business_id) },
        { key: 'placement', header: 'Original Placement', render: (row) => recorded(row.original_placement?.business_id) },
        { key: 'employer', header: 'Employer', render: (row) => recorded(row.employer?.company_name) },
        { key: 'job', header: 'Job', render: (row) => recorded(row.job?.title) },
        { key: 'original', header: 'Original Candidate', render: (row) => recorded(row.original_candidate?.name ?? row.original_employee?.name) },
        { key: 'candidate', header: 'Replacement Candidate', render: (row) => recorded(row.replacement_candidate.name) },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'reason', header: 'Reason', render: (row) => recorded(row.reason) },
        { key: 'requested', header: 'Requested date', render: (row) => recorded(row.requested_at) },
        { key: 'eligibility', header: 'Eligibility', render: (row) => recorded(row.eligibility) },
        { key: 'closed', header: 'Closure date', render: (row) => recorded(row.closure_date) },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button variant="ghost" onClick={() => visit({ replacement: String(row.id) })}>
                    View
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout title="Replacements" breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Replacements' }]}>
            <div
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6">
                    <DetailHeader
                        headingLevel={2}
                        title="Employee replacement pipeline"
                        description="A replacement starts from a joined employee. The original employee and placement stay on file; the replacement candidate follows the existing recruitment workflow."
                    />
                </div>
                <FilterBar className="md:grid-cols-4 xl:grid-cols-6" onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Replacement, employee, employer, job, candidate ID" />
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
                    <Select name="status" defaultValue={filters.status} aria-label="Replacement status">
                        <option value="">All statuses</option>
                        {statuses.map((status) => (
                            <option key={status} value={status}>
                                {status}
                            </option>
                        ))}
                    </Select>
                    <Select name="reason" defaultValue={filters.reason} aria-label="Replacement reason">
                        <option value="">All reasons</option>
                        {reasons.map((reason) => (
                            <option key={reason} value={reason}>
                                {reason}
                            </option>
                        ))}
                    </Select>
                    <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="Requested from" />
                    <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="Requested to" />
                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit">Filter</Button>
                        <Button type="button" variant="secondary" onClick={() => visit({}, true)}>
                            Reset
                        </Button>
                    </div>
                </FilterBar>
                <DataTable
                    columns={columns}
                    rows={replacements.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No replacements match these filters"
                    emptyDescription="Request a replacement from a joined employee record."
                    onRowClick={(row) => visit({ replacement: String(row.id) })}
                    selectedKey={selected?.id ?? null}
                />
                <Pagination links={replacements.links} />
            </div>

            <Drawer open={selected !== null} title={selected?.business_id ?? 'Replacement'} size="xl" onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-4">
                        <Tabs
                            tabs={[
                                { id: 'overview', label: 'Overview' },
                                { id: 'chain', label: 'History' },
                                { id: 'original', label: 'Original' },
                                { id: 'candidate', label: 'Replacement candidate' },
                                { id: 'timeline', label: 'Timeline' },
                            ]}
                            active={tab}
                            onChange={setTab}
                        />
                        {tab === 'overview' ? (
                            <div className="space-y-4 text-sm">
                                <dl className="grid gap-2">
                                    {[
                                        ['Replacement ID', recorded(selected.business_id)],
                                        ['Stored status', selected.status],
                                        ['Operational stage', recorded(selected.operational_stage)],
                                        ['Reason', recorded(selected.reason)],
                                        ['Eligibility', recorded(selected.eligibility)],
                                        ['Employer', recorded(selected.employer?.company_name)],
                                        ['Job', recorded(selected.job?.title)],
                                        ['Original Placement', recorded(selected.original_placement?.business_id)],
                                        ['Original Candidate', recorded(selected.original_candidate?.name ?? selected.original_employee?.name)],
                                        ['Requested', recorded(selected.requested_at)],
                                        ['Requested by', recorded(selected.requested_by)],
                                        ['Closure date', recorded(selected.closure_date)],
                                        ['Completed', recorded(selected.completed_at)],
                                        ['Cancelled', recorded(selected.cancelled_at)],
                                        ['Notes', recorded(selected.notes)],
                                    ].map(([label, value]) => (
                                        <div key={label} className="grid grid-cols-3 gap-2">
                                            <dt className="text-muted">{label}</dt>
                                            <dd className="col-span-2 text-ink">{value}</dd>
                                        </div>
                                    ))}
                                </dl>
                                <div className="flex flex-wrap gap-2">
                                    <Button disabled={!selected.can_identify_candidate} onClick={() => setIdentifyOpen(true)}>
                                        Identify candidate
                                    </Button>
                                    <Button variant="danger" disabled={!selected.can_cancel} onClick={() => setCancelOpen(true)}>
                                        Cancel
                                    </Button>
                                </div>
                                <p className="text-xs text-muted">
                                    V1.1 stored statuses: Requested, In Progress, Placed, Completed, Cancelled. Operational labels map onto
                                    those statuses and do not replace the original placement.
                                </p>
                            </div>
                        ) : null}
                        {tab === 'chain' ? (
                            <div className="space-y-4">
                                <RecruitmentChain steps={selected.chain ?? []} />
                                <ul className="space-y-2 text-sm">
                                    {(selected.workflow ?? []).map((step) => (
                                        <li key={step.key} className={`rounded-md px-3 py-2 ${step.current ? 'bg-brand/10' : 'bg-surface'}`}>
                                            <span className="font-medium text-ink">{step.label}</span>
                                            {step.value ? <span className="text-muted"> · {step.value}</span> : null}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ) : null}
                        {tab === 'original' ? (
                            <ul className="space-y-2 text-sm">
                                <li>
                                    Original employee{' '}
                                    {selected.original_employee?.id ? (
                                        <Link href={`/admin/employees?employee=${selected.original_employee.id}`} className="font-medium text-brand">
                                            {selected.original_employee.business_id ?? selected.original_employee.name}
                                        </Link>
                                    ) : (
                                        '—'
                                    )}
                                </li>
                                <li>
                                    Original placement{' '}
                                    {selected.original_placement?.id ? (
                                        <Link href={`/admin/placements?placement=${selected.original_placement.id}`} className="font-medium text-brand">
                                            {selected.original_placement.business_id}
                                        </Link>
                                    ) : (
                                        '—'
                                    )}
                                </li>
                                <li>Original application {selected.original_application?.business_id ?? '—'}</li>
                                <li>
                                    Original submission{' '}
                                    {selected.original_submission?.id ? (
                                        <Link href={`/admin/submissions?submission=${selected.original_submission.id}`} className="font-medium text-brand">
                                            {selected.original_submission.business_id}
                                        </Link>
                                    ) : (
                                        '—'
                                    )}
                                </li>
                                <li>
                                    Employer{' '}
                                    {selected.employer?.id ? (
                                        <Link href={`/admin/employers?employer=${selected.employer.id}`} className="font-medium text-brand">
                                            {selected.employer.business_id ?? selected.employer.company_name}
                                        </Link>
                                    ) : (
                                        '—'
                                    )}
                                </li>
                                <li>
                                    Job{' '}
                                    {selected.job?.slug ? (
                                        <Link href={`/admin/jobs?job=${selected.job.slug}`} className="font-medium text-brand">
                                            {selected.job.business_id ?? selected.job.title}
                                        </Link>
                                    ) : (
                                        selected.job?.title ?? '—'
                                    )}
                                </li>
                            </ul>
                        ) : null}
                        {tab === 'candidate' ? (
                            <ul className="space-y-2 text-sm">
                                <li>Candidate {selected.replacement_application?.name ?? selected.replacement_candidate.name ?? 'Not identified yet'}</li>
                                <li>Application {selected.replacement_application?.business_id ?? '—'}</li>
                                <li>
                                    Submission{' '}
                                    {selected.replacement_submission?.id ? (
                                        <Link href={`/admin/submissions?submission=${selected.replacement_submission.id}`} className="font-medium text-brand">
                                            {selected.replacement_submission.business_id} ({selected.replacement_submission.status})
                                        </Link>
                                    ) : (
                                        '—'
                                    )}
                                </li>
                                <li>
                                    Placement{' '}
                                    {selected.replacement_placement?.id ? (
                                        <Link href={`/admin/placements?placement=${selected.replacement_placement.id}`} className="font-medium text-brand">
                                            {selected.replacement_placement.business_id} ({selected.replacement_placement.status})
                                        </Link>
                                    ) : (
                                        '—'
                                    )}
                                </li>
                                <li>
                                    Replacement employee{' '}
                                    {selected.replacement_employee?.id ? (
                                        <Link href={`/admin/employees?employee=${selected.replacement_employee.id}`} className="font-medium text-brand">
                                            {selected.replacement_employee.business_id ?? selected.replacement_employee.name}
                                        </Link>
                                    ) : (
                                        'Created after joining'
                                    )}
                                </li>
                                <p className="text-muted">
                                    Continue through Screening, Shortlisted, Employer Submission, Placement, Offer, and Joining. Do not
                                    complete this case until the replacement employee has joined.
                                </p>
                            </ul>
                        ) : null}
                        {tab === 'timeline' ? (
                            <Timeline
                                items={(selected.activities ?? []).map((activity) => ({
                                    id: activity.id,
                                    title: activityTitle(activity),
                                    meta: `${activity.actor_name ?? 'System'} · ${activity.created_at ?? ''}`,
                                }))}
                                empty="No replacement activity yet."
                            />
                        ) : null}
                    </div>
                ) : null}
            </Drawer>

            <Modal
                open={identifyOpen}
                title="Identify replacement candidate"
                onClose={() => setIdentifyOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setIdentifyOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            disabled={!applicationId}
                            onClick={() => {
                                if (!selected || !applicationId) {
                                    return;
                                }
                                router.post(`/admin/replacements/${selected.id}/identify`, {
                                    application_id: applicationId,
                                });
                                setIdentifyOpen(false);
                            }}
                        >
                            Identify
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left text-sm">
                    <p>Only a shortlisted application for the original job can be identified. The original employee application cannot be reused.</p>
                    <Select value={applicationId} onChange={(event) => setApplicationId(event.target.value)} aria-label="Replacement application">
                        <option value="">Select a shortlisted application</option>
                        {eligibleApplications.map((application) => (
                            <option key={application.id} value={application.id}>
                                {(application.business_id ?? `#${application.id}`) + ' · ' + (application.name ?? 'Unnamed') + ' · ' + application.status}
                            </option>
                        ))}
                    </Select>
                    {eligibleApplications.length === 0 ? (
                        <p className="text-muted">No eligible shortlisted applications are currently available for this job.</p>
                    ) : null}
                </div>
            </Modal>

            <Modal
                open={cancelOpen}
                title="Cancel this replacement?"
                onClose={() => setCancelOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setCancelOpen(false)}>
                            Keep case
                        </Button>
                        <Button
                            variant="danger"
                            onClick={() => {
                                if (!selected) {
                                    return;
                                }
                                router.post(`/admin/replacements/${selected.id}/cancel`, { notes });
                                setCancelOpen(false);
                            }}
                        >
                            Cancel replacement
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left text-sm">
                    <p>The original employee and placement remain unchanged.</p>
                    <Textarea value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Cancellation notes" />
                </div>
            </Modal>
        </AdminLayout>
    );
}
