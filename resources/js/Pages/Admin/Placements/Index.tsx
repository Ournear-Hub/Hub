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
import type { Paginator } from '@/types/jobs';
import type { PlacementDetail, PlacementListItem } from '@/types/placements';

type Props = {
    placements: Paginator<PlacementListItem>;
    selected: PlacementDetail | null;
    filters: {
        search: string;
        employer_id: string;
        job_id: string;
        candidate_id: string;
        status: string;
        joining_status: string;
        date_from: string;
        date_to: string;
        placement: string;
    };
    options: {
        employers: Array<{ id: number; label: string }>;
        jobs: Array<{ id: number; title: string; business_id: string | null }>;
        candidates?: Array<{ id: number; label: string }>;
        statuses: string[];
        joining_statuses?: Array<{ value: string; label: string }>;
    };
    statuses: string[];
};

function activityTitle(activity: PlacementDetail['activities'][number]): string {
    if (activity.from_status && activity.to_status) {
        return `Status: ${activity.from_status} → ${activity.to_status}`;
    }

    return activity.body || activity.type.replace(/_/g, ' ');
}

export default function PlacementsIndex({ placements, selected, filters, options, statuses }: Props) {
    const [tab, setTab] = useState('overview');
    const [confirm, setConfirm] = useState<'accept' | 'withdraw' | null>(null);
    const [offerOpen, setOfferOpen] = useState(false);
    const [declineOpen, setDeclineOpen] = useState(false);
    const [joiningOpen, setJoiningOpen] = useState(false);
    const [joinOpen, setJoinOpen] = useState(false);
    const [notes, setNotes] = useState('');
    const [offeredAt, setOfferedAt] = useState(new Date().toISOString().slice(0, 10));
    const [salary, setSalary] = useState('');
    const [expectedJoiningDate, setExpectedJoiningDate] = useState('');
    const [actualJoiningDate, setActualJoiningDate] = useState('');

    const visit = (overrides: Record<string, string | undefined> = {}, replaceAll = false) => {
        const next = replaceAll ? overrides : { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/placements', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ placement: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        visit({
            ...Object.fromEntries(new FormData(event.currentTarget).entries()),
            placement: filters.placement,
        } as Record<string, string>);
    };

    const columns: Array<DataTableColumn<PlacementListItem>> = [
        { key: 'id', header: 'Placement ID', className: 'font-medium text-ink', render: (row) => recorded(row.business_id) },
        { key: 'candidate', header: 'Candidate', className: 'font-medium text-ink', render: (row) => recorded(row.candidate.name) },
        { key: 'application', header: 'Application', render: (row) => recorded(row.application?.business_id) },
        { key: 'job', header: 'Job', render: (row) => recorded(row.job?.title) },
        { key: 'employer', header: 'Employer', render: (row) => recorded(row.employer?.company_name) },
        { key: 'selection', header: 'Selection', render: (row) => recorded(row.selection?.business_id ?? row.submission?.business_id) },
        { key: 'status', header: 'Placement status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'offer', header: 'Offer status', render: (row) => recorded(row.offer_status) },
        { key: 'joining', header: 'Joining status', render: (row) => <StatusBadge status={row.joining_status ?? 'Pending'} /> },
        { key: 'placed', header: 'Placement date', render: (row) => recorded(row.created_at) },
        { key: 'expected', header: 'Expected joining', render: (row) => recorded(row.expected_joining_date) },
        { key: 'actual', header: 'Actual joining', render: (row) => recorded(row.actual_joining_date) },
        { key: 'created', header: 'Created date', render: (row) => recorded(row.created_at) },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button variant="ghost" onClick={() => visit({ placement: String(row.id) })}>
                    View
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout title="Placements" breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Placements' }]}>
            <div
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6">
                    <DetailHeader
                        headingLevel={2}
                        title="Placement records from employer selections"
                        description="Placement starts from an employer-selected submission for a specific job. This is not a global candidate selection."
                    />
                </div>
                <FilterBar className="md:grid-cols-4 xl:grid-cols-6" onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Placement, employer, job, candidate, submission ID" />
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
                    <Select name="status" defaultValue={filters.status} aria-label="Placement status">
                        <option value="">All statuses</option>
                        {statuses.map((status) => (
                            <option key={status} value={status}>
                                {status}
                            </option>
                        ))}
                    </Select>
                    <Select name="joining_status" defaultValue={filters.joining_status} aria-label="Joining status">
                        <option value="">All joining statuses</option>
                        {(options.joining_statuses ?? []).map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </Select>
                    <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="Created from" />
                    <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="Created to" />
                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit">Filter</Button>
                        <Button type="button" variant="secondary" onClick={() => visit({}, true)}>
                            Reset
                        </Button>
                    </div>
                </FilterBar>
                <DataTable
                    columns={columns}
                    rows={placements.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No placements match these filters"
                    emptyDescription="Create a placement from an employer-selected submission."
                    onRowClick={(row) => visit({ placement: String(row.id) })}
                    selectedKey={selected?.id ?? null}
                />
                <Pagination links={placements.links} />
            </div>

            <Drawer open={selected !== null} title={selected?.business_id ?? 'Placement'} size="xl" onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-4">
                        <Tabs
                            tabs={[
                                { id: 'overview', label: 'Overview' },
                                { id: 'chain', label: 'Recruitment chain' },
                                { id: 'candidate', label: 'Candidate' },
                                { id: 'job', label: 'Job' },
                                { id: 'employer', label: 'Employer' },
                                { id: 'submission', label: 'Submission' },
                                { id: 'offer', label: 'Offer' },
                                { id: 'joining', label: 'Joining' },
                                { id: 'employee', label: 'Employee' },
                                { id: 'replacement', label: 'Replacement' },
                                { id: 'finance', label: 'Finance' },
                                { id: 'timeline', label: 'Timeline' },
                            ]}
                            active={tab}
                            onChange={setTab}
                        />
                        {tab === 'overview' ? (
                            <dl className="grid gap-2 text-sm">
                                {[
                                    ['Placement ID', recorded(selected.business_id)],
                                    ['Status', selected.status],
                                    ['Offer status', recorded(selected.offer_status)],
                                    ['Joining status', recorded(selected.joining_status)],
                                    ['Employer', recorded(selected.employer?.company_name)],
                                    ['Job', recorded(selected.job?.title)],
                                    ['Candidate', recorded(selected.candidate.name)],
                                    ['Application', recorded(selected.application?.business_id)],
                                    ['Selection', recorded(selected.selection?.business_id ?? selected.submission?.business_id)],
                                    ['Placement date', recorded(selected.created_at)],
                                    ['Expected joining', recorded(selected.expected_joining_date)],
                                    ['Actual joining', recorded(selected.actual_joining_date)],
                                    ['Created', recorded(selected.created_at)],
                                ].map(([label, value]) => (
                                    <div key={label} className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">{label}</dt>
                                        <dd className="col-span-2 text-ink">{value}</dd>
                                    </div>
                                ))}
                            </dl>
                        ) : null}
                        {tab === 'chain' ? <RecruitmentChain steps={selected.chain ?? []} /> : null}
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
                        {tab === 'employer' ? (
                            <div className="space-y-3 text-sm">
                                <p className="font-medium text-ink">{selected.employer?.company_name}</p>
                                <p className="text-muted">{selected.employer?.business_id ?? '—'}</p>
                                {selected.employer?.id ? (
                                    <Link href={`/admin/employers?employer=${selected.employer.id}`} className="inline-block font-medium text-brand">
                                        Open Employer Workspace
                                    </Link>
                                ) : null}
                            </div>
                        ) : null}
                        {tab === 'submission' ? (
                            <div className="space-y-3 text-sm">
                                <p className="font-medium text-ink">{selected.submission?.business_id ?? 'Submission'}</p>
                                <p className="text-muted">{selected.submission?.status ?? '—'}</p>
                                {selected.submission?.id ? (
                                    <Link href={`/admin/submissions?submission=${selected.submission.id}`} className="inline-block font-medium text-brand">
                                        Open submission
                                    </Link>
                                ) : null}
                            </div>
                        ) : null}
                        {tab === 'offer' ? (
                            <div className="space-y-3 text-sm">
                                {selected.offer ? (
                                    <dl className="grid gap-2">
                                        {[
                                            ['Offer status', recorded(selected.offer_status)],
                                            ['Offered', recorded(selected.offer.offered_at)],
                                            ['Offer reference', 'Not available in V1.1'],
                                            ['Salary / CTC', recorded(selected.offer.salary)],
                                            ['Joining date', recorded(selected.expected_joining_date)],
                                            ['Offer document', 'Not available in V1.1'],
                                            ['Accepted', recorded(selected.offer.accepted_at)],
                                            ['Declined', recorded(selected.offer.declined_at)],
                                            ['Withdrawn', recorded(selected.offer.withdrawn_at)],
                                            ['Remarks', recorded(selected.offer.notes)],
                                        ].map(([label, value]) => (
                                            <div key={label} className="grid grid-cols-3 gap-2">
                                                <dt className="text-muted">{label}</dt>
                                                <dd className="col-span-2 text-ink">{value}</dd>
                                            </div>
                                        ))}
                                    </dl>
                                ) : (
                                    <p className="text-muted">No offer has been recorded yet.</p>
                                )}
                                <div className="flex flex-wrap gap-2">
                                    <Button disabled={!selected.can_offer} onClick={() => setOfferOpen(true)}>
                                        Record offer
                                    </Button>
                                    <Button disabled={!selected.can_accept} onClick={() => setConfirm('accept')}>
                                        Accept offer
                                    </Button>
                                    <Button variant="danger" disabled={!selected.can_decline} onClick={() => setDeclineOpen(true)}>
                                        Decline offer
                                    </Button>
                                    <Button variant="secondary" disabled={!selected.can_withdraw} onClick={() => setConfirm('withdraw')}>
                                        Withdraw
                                    </Button>
                                </div>
                            </div>
                        ) : null}
                        {tab === 'joining' ? (
                            <div className="space-y-3 text-sm">
                                <dl className="grid gap-2">
                                    {[
                                        ['Expected joining', recorded(selected.expected_joining_date)],
                                        ['Confirmed', recorded(selected.joining_confirmed_at)],
                                        ['Confirmed by', recorded(selected.joining_confirmed_by)],
                                        ['Actual joining', recorded(selected.actual_joining_date)],
                                        ['Joining status', recorded(selected.joining_status)],
                                        ['Notes', recorded(selected.joining_notes)],
                                    ].map(([label, value]) => (
                                        <div key={label} className="grid grid-cols-3 gap-2">
                                            <dt className="text-muted">{label}</dt>
                                            <dd className="col-span-2 text-ink">{value}</dd>
                                        </div>
                                    ))}
                                </dl>
                                <div className="flex flex-wrap gap-2">
                                    <Button disabled={!selected.can_confirm_joining} onClick={() => setJoiningOpen(true)}>
                                        Confirm joining
                                    </Button>
                                    <Button disabled={!selected.can_join} onClick={() => setJoinOpen(true)}>
                                        Mark joined
                                    </Button>
                                </div>
                            </div>
                        ) : null}
                        {tab === 'employee' ? (
                            selected.employee ? (
                                <div className="space-y-3 text-sm">
                                    <p className="font-medium text-ink">{selected.employee.business_id}</p>
                                    <Link href={`/admin/employees?employee=${selected.employee.id}`} className="inline-block font-medium text-brand">
                                        Open employee record
                                    </Link>
                                </div>
                            ) : (
                                <p className="text-sm text-muted">The employee record is created when the candidate joins.</p>
                            )
                        ) : null}
                        {tab === 'replacement' ? (
                            <div className="space-y-3 text-sm">
                                {selected.replacement?.as_original ? (
                                    <p>
                                        Replacement requested:{' '}
                                        <Link href={`/admin/replacements?replacement=${selected.replacement.as_original.id}`} className="font-medium text-brand">
                                            {selected.replacement.as_original.business_id}
                                        </Link>{' '}
                                        ({selected.replacement.as_original.status})
                                        {selected.replacement.as_original.replacement_employee_name
                                            ? ` · Replacement employee ${selected.replacement.as_original.replacement_employee_name}`
                                            : ''}
                                    </p>
                                ) : null}
                                {selected.replacement?.as_replacement ? (
                                    <p>
                                        This is a replacement placement for{' '}
                                        {selected.replacement.as_replacement.original_employee_name ?? 'the original employee'}. Case{' '}
                                        <Link href={`/admin/replacements?replacement=${selected.replacement.as_replacement.id}`} className="font-medium text-brand">
                                            {selected.replacement.as_replacement.business_id}
                                        </Link>
                                        {selected.replacement.as_replacement.original_placement_id ? (
                                            <>
                                                {' '}
                                                · Original placement{' '}
                                                <Link
                                                    href={`/admin/placements?placement=${selected.replacement.as_replacement.original_placement_id}`}
                                                    className="font-medium text-brand"
                                                >
                                                    {selected.replacement.as_replacement.original_placement_business_id}
                                                </Link>
                                            </>
                                        ) : null}
                                    </p>
                                ) : null}
                                {!selected.replacement ? (
                                    <p className="text-muted">No replacement case is linked to this placement.</p>
                                ) : null}
                            </div>
                        ) : null}
                        {tab === 'finance' ? (
                            <div className="space-y-3 text-sm">
                                {selected.invoice ? (
                                    <p>
                                        Invoice{' '}
                                        <Link href={`/admin/finance?invoice=${selected.invoice.id}`} className="font-medium text-brand">
                                            {selected.invoice.business_id}
                                        </Link>{' '}
                                        · {selected.invoice.status} · {selected.invoice.currency} {selected.invoice.total_amount}
                                    </p>
                                ) : (
                                    <p className="text-muted">
                                        Billing starts after joining. Create the invoice from the employee record. Placement is not itself an invoice.
                                    </p>
                                )}
                            </div>
                        ) : null}
                        {tab === 'timeline' ? (
                            <Timeline
                                items={(selected.activities ?? []).map((activity) => ({
                                    id: activity.id,
                                    title: activityTitle(activity),
                                    meta: `${activity.actor_name ?? 'System'} · ${activity.created_at ?? ''}`,
                                }))}
                                empty="No placement activity yet."
                            />
                        ) : null}
                    </div>
                ) : null}
            </Drawer>

            <ConfirmDialog
                open={confirm !== null}
                title={confirm === 'accept' ? 'Record offer acceptance?' : 'Withdraw this placement?'}
                description={
                    confirm === 'accept'
                        ? 'Acceptance is for this employer, job, and placement only.'
                        : 'Withdrawn placements cannot continue to joining.'
                }
                confirmLabel={confirm === 'accept' ? 'Accept' : 'Withdraw'}
                onConfirm={() => {
                    if (!selected || confirm === null) {
                        return;
                    }
                    router.post(`/admin/placements/${selected.id}/${confirm}`, { notes });
                    setConfirm(null);
                }}
                onClose={() => setConfirm(null)}
            />

            <Modal
                open={offerOpen}
                title="Record offer"
                onClose={() => setOfferOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setOfferOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            onClick={() => {
                                if (!selected) {
                                    return;
                                }
                                router.post(`/admin/placements/${selected.id}/offer`, {
                                    offered_at: offeredAt,
                                    salary,
                                    notes,
                                });
                                setOfferOpen(false);
                            }}
                        >
                            Save offer
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left">
                    <Input type="date" value={offeredAt} onChange={(event) => setOfferedAt(event.target.value)} aria-label="Offer date" />
                    <Input value={salary} onChange={(event) => setSalary(event.target.value)} placeholder="Salary / CTC (optional)" />
                    <Textarea value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Offer notes" />
                </div>
            </Modal>

            <Modal
                open={declineOpen}
                title="Decline offer"
                onClose={() => setDeclineOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setDeclineOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            variant="danger"
                            onClick={() => {
                                if (!selected) {
                                    return;
                                }
                                router.post(`/admin/placements/${selected.id}/decline`, { notes });
                                setDeclineOpen(false);
                            }}
                        >
                            Decline
                        </Button>
                    </>
                }
            >
                <Textarea value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Decline notes" />
            </Modal>

            <Modal
                open={joiningOpen}
                title="Confirm joining"
                onClose={() => setJoiningOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setJoiningOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            onClick={() => {
                                if (!selected) {
                                    return;
                                }
                                router.post(`/admin/placements/${selected.id}/confirm-joining`, {
                                    expected_joining_date: expectedJoiningDate,
                                    notes,
                                });
                                setJoiningOpen(false);
                            }}
                        >
                            Confirm
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left">
                    <Input
                        type="date"
                        value={expectedJoiningDate}
                        onChange={(event) => setExpectedJoiningDate(event.target.value)}
                        aria-label="Expected joining date"
                    />
                    <Textarea value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Joining notes" />
                </div>
            </Modal>

            <Modal
                open={joinOpen}
                title="Mark candidate as joined"
                onClose={() => setJoinOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setJoinOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            onClick={() => {
                                if (!selected) {
                                    return;
                                }
                                router.post(`/admin/placements/${selected.id}/join`, {
                                    actual_joining_date: actualJoiningDate,
                                    notes,
                                });
                                setJoinOpen(false);
                            }}
                        >
                            Create employee
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left">
                    <p>This creates the employee record for this employer, job, and placement only.</p>
                    <Input
                        type="date"
                        value={actualJoiningDate}
                        onChange={(event) => setActualJoiningDate(event.target.value)}
                        aria-label="Actual joining date"
                    />
                    <Textarea value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Joining notes" />
                </div>
            </Modal>
        </AdminLayout>
    );
}
