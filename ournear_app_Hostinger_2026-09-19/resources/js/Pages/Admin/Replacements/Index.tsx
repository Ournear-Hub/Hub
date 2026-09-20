import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    Button,
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

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
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
        { key: 'id', header: 'Replacement ID', className: 'font-medium text-ink', render: (row) => row.business_id ?? '—' },
        { key: 'original', header: 'Original employee', render: (row) => row.original_employee?.name ?? '—' },
        { key: 'employer', header: 'Employer', render: (row) => row.employer?.company_name ?? '—' },
        { key: 'job', header: 'Job', render: (row) => row.job?.title ?? '—' },
        { key: 'candidate', header: 'Replacement candidate', render: (row) => row.replacement_candidate.name ?? '—' },
        { key: 'reason', header: 'Reason', render: (row) => row.reason },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'requested', header: 'Requested', render: (row) => row.requested_at ?? '—' },
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
        <AdminLayout title="Replacements">
            <div
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Replacements</h1>
                    <p className="mt-1 text-sm text-muted">
                        A replacement starts from a joined employee. The original employee and placement stay on file; the
                        replacement candidate follows the existing recruitment workflow.
                    </p>
                </div>
                <FilterBar onSubmit={filter}>
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
                    <Button type="submit">Filter</Button>
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
                                        ['Replacement ID', selected.business_id ?? '—'],
                                        ['Status', selected.status],
                                        ['Reason', selected.reason],
                                        ['Employer', selected.employer?.company_name ?? '—'],
                                        ['Job', selected.job?.title ?? '—'],
                                        ['Requested', selected.requested_at ?? '—'],
                                        ['Requested by', selected.requested_by ?? '—'],
                                        ['Completed', selected.completed_at ?? '—'],
                                        ['Cancelled', selected.cancelled_at ?? '—'],
                                        ['Notes', selected.notes || '—'],
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
