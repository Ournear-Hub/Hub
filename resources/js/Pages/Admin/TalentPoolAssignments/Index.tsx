import { router, useForm } from '@inertiajs/react';
import { useMemo, useState, type FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import { Button, Input, Label, Select } from '@/Components/Admin/ui';
import Modal from '@/Components/Admin/ui/Modal';
import DetailHeader from '@/Components/organisms/DetailHeader';
import DataTable from '@/Components/molecules/DataTable';

type CandidateRow = {
    profile_id: number;
    user_id: number;
    name: string;
    ournear_id: string | null;
    email: string | null;
    mobile: string | null;
    skills: string | null;
    experience: string | null;
    preferred_location: string | null;
    has_resume: boolean;
    allocation_status: string;
    assigned_recruiter: string | null;
    active_assignment_id: number | null;
    last_activity: string | null;
};

type AssignmentRow = {
    id: number;
    candidate_name: string;
    recruiter_name: string;
    recruiter_company: string | null;
    assignment_type_label: string;
    progress_stage_label: string;
    status_label: string;
    requirement_label: string | null;
    follow_up_at: string | null;
    follow_up_overdue: boolean;
    created_at: string | null;
    updated_at: string | null;
};

type StatusEvent = {
    progress_stage_label: string | null;
    to_status_label: string;
    notes: string | null;
    actor_name: string | null;
    created_at: string | null;
};

type SelectedAssignment = AssignmentRow & {
    status_events: StatusEvent[];
    assignment_type?: string;
    can_submit_to_staffing_client?: boolean;
};

type Props = {
    candidates: {
        data: CandidateRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    assignments: {
        data: AssignmentRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    selected_assignment: SelectedAssignment | null;
    recruiters: { id: number; name: string }[];
    employers: { id: number; name: string }[];
    jobs: { id: number; title: string; employer_id: number }[];
    staffing_requirements: {
        id: number;
        label: string;
        company_name: string | null;
        staffing_access_label: string;
    }[];
    staffing_companies: {
        id: number;
        name: string;
        staffing_access_label: string;
        has_requirements: boolean;
        requirements_count: number;
    }[];
    can_manage: boolean;
    filters: Record<string, string | boolean>;
    assignment_types: { value: string; label: string }[];
    progress_stages: { value: string; label: string }[];
    statuses: { value: string; label: string }[];
    company_relationship: {
        employers: { id: number; name: string }[];
        recruiters: { id: number; name: string }[];
        selected_employer_id: number | null;
        active: {
            recruiter_name: string | null;
            status_label: string | null;
            created_at: string | null;
        } | null;
        history: {
            id: number;
            recruiter_name: string | null;
            status_label: string | null;
            assigned_by_name: string | null;
            created_at: string | null;
            ended_at: string | null;
        }[];
        can_manage: boolean;
    };
    recruiter_workspace_scoped?: boolean;
    company_submissions: {
        id: number;
        business_id: string | null;
        candidate_name: string | null;
        company_name: string | null;
        responsible_recruiter_name: string | null;
        owner_resolution_label: string | null;
        status_label: string;
        submitted_at: string | null;
    }[];
    can_submit_to_company: boolean;
};

export default function AdminTalentPoolAssignmentsIndex({
    candidates,
    assignments,
    selected_assignment,
    recruiters,
    employers,
    jobs,
    staffing_requirements,
    staffing_companies,
    can_manage,
    filters,
    assignment_types,
    statuses,
    company_relationship,
    recruiter_workspace_scoped = false,
    company_submissions = [],
    can_submit_to_company = false,
}: Props) {
    const [selectedUserIds, setSelectedUserIds] = useState<number[]>([]);
    const [assignOpen, setAssignOpen] = useState(false);
    const [search, setSearch] = useState(String(filters.search ?? ''));

    const assignForm = useForm({
        candidate_user_ids: [] as number[],
        assigned_recruiter_user_id: '',
        assignment_type: 'talent_pool',
        job_id: '',
        employer_enquiry_id: '',
        admin_notes: '',
        follow_up_at: '',
    });

    const selectedCandidates = useMemo(
        () => candidates.data.filter((row) => selectedUserIds.includes(row.user_id)),
        [candidates.data, selectedUserIds],
    );

    const jobsForRecruiter = useMemo(() => jobs, [jobs]);

    const companyRecruiterForm = useForm({
        assigned_recruiter_user_id: '',
        notes: '',
    });

    const submitCompanyForm = useForm({
        candidate_user_id: '',
        employer_profile_id: '',
        recruiter_talent_pool_assignment_id: '',
        responsible_recruiter_user_id: '',
        internal_notes: '',
    });

    const submitStaffingClientForm = useForm({
        internal_notes: '',
    });

    const toggleRow = (userId: number) => {
        setSelectedUserIds((current) =>
            current.includes(userId) ? current.filter((id) => id !== userId) : [...current, userId],
        );
    };

    const toggleAllOnPage = () => {
        const pageIds = candidates.data.map((row) => row.user_id);
        const allSelected = pageIds.every((id) => selectedUserIds.includes(id));
        if (allSelected) {
            setSelectedUserIds((current) => current.filter((id) => !pageIds.includes(id)));
        } else {
            setSelectedUserIds((current) => Array.from(new Set([...current, ...pageIds])));
        }
    };

    const openAssign = () => {
        assignForm.setData('candidate_user_ids', selectedUserIds);
        setAssignOpen(true);
    };

    const submitAssign = (event: FormEvent) => {
        event.preventDefault();
        assignForm.post('/admin/talent-pool-assignments', {
            onSuccess: () => {
                setAssignOpen(false);
                setSelectedUserIds([]);
            },
        });
    };

    const applySearch = (event: FormEvent) => {
        event.preventDefault();
        router.get('/admin/talent-pool-assignments', { ...filters, search }, { preserveState: true });
    };

    const candidateColumns = [
        {
            key: 'select',
            header: 'Select',
            render: (row: CandidateRow) => (
                <input
                    type="checkbox"
                    aria-label={`Select ${row.name}`}
                    checked={selectedUserIds.includes(row.user_id)}
                    onChange={() => toggleRow(row.user_id)}
                />
            ),
        },
        { key: 'name', header: 'Candidate', render: (row: CandidateRow) => row.name ?? '—' },
        { key: 'id', header: 'OurNear ID', render: (row: CandidateRow) => row.ournear_id ?? '—' },
        {
            key: 'contact',
            header: 'Contact',
            render: (row: CandidateRow) => (
                <span className="text-xs">
                    {row.email ?? '—'}
                    {row.mobile ? ` · ${row.mobile}` : ''}
                </span>
            ),
        },
        { key: 'skills', header: 'Skills', render: (row: CandidateRow) => row.skills ?? '—' },
        { key: 'experience', header: 'Experience', render: (row: CandidateRow) => row.experience ?? '—' },
        {
            key: 'location',
            header: 'Preferred location',
            render: (row: CandidateRow) => row.preferred_location ?? '—',
        },
        {
            key: 'resume',
            header: 'Resume',
            render: (row: CandidateRow) => (row.has_resume ? 'Available' : '—'),
        },
        { key: 'allocation', header: 'Allocation', render: (row: CandidateRow) => row.allocation_status },
        { key: 'recruiter', header: 'Recruiter', render: (row: CandidateRow) => row.assigned_recruiter ?? '—' },
        { key: 'activity', header: 'Last activity', render: (row: CandidateRow) => row.last_activity ?? '—' },
    ];

    const trackingColumns = [
        { key: 'candidate', header: 'Candidate', render: (row: AssignmentRow) => row.candidate_name },
        {
            key: 'recruiter',
            header: 'Recruiter',
            render: (row: AssignmentRow) => row.recruiter_name ?? row.recruiter_company ?? '—',
        },
        { key: 'type', header: 'Type', render: (row: AssignmentRow) => row.assignment_type_label },
        { key: 'requirement', header: 'Requirement', render: (row: AssignmentRow) => row.requirement_label ?? '—' },
        {
            key: 'progress',
            header: 'Progress',
            render: (row: AssignmentRow) => (
                <span className={row.follow_up_overdue ? 'font-semibold text-accent' : ''}>
                    {row.progress_stage_label}
                    {row.follow_up_overdue ? ' (overdue)' : ''}
                </span>
            ),
        },
        { key: 'assigned', header: 'Assigned', render: (row: AssignmentRow) => row.created_at ?? '—' },
        { key: 'updated', header: 'Last updated', render: (row: AssignmentRow) => row.updated_at ?? '—' },
        {
            key: 'follow_up',
            header: 'Follow-up',
            render: (row: AssignmentRow) => row.follow_up_at ?? '—',
        },
        {
            key: 'actions',
            header: '',
            render: (row: AssignmentRow) => (
                <Button
                    type="button"
                    variant="secondary"
                    onClick={() =>
                        router.get('/admin/talent-pool-assignments', { ...filters, assignment: String(row.id) })
                    }
                >
                    Details
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout title="Talent Pool">
            <DetailHeader
                title="Talent Pool"
                description="OurNear coordination: assign internal recruiters to companies and candidates. Does not create employer submissions or job applications."
            />

            {recruiter_workspace_scoped ? (
                <p className="mb-6 rounded-lg border border-line bg-surface-muted/40 px-4 py-3 text-sm text-muted">
                    Showing assignments and candidates linked to your OurNear recruiter responsibilities.
                </p>
            ) : null}

            <section className="mb-10 space-y-4 rounded-xl border border-line bg-white p-4">
                <h2 className="text-lg font-semibold text-ink">Company relationship recruiter</h2>
                <p className="text-sm text-muted">
                    Assign an OurNear internal recruiter to manage the client company relationship (Talent Pool and follow-up).
                    This is separate from employer Hiring / ATS.
                </p>
                <div className="flex flex-wrap items-end gap-3">
                    <div>
                        <Label>Company</Label>
                        <Select
                            value={String(company_relationship.selected_employer_id ?? '')}
                            onChange={(event) =>
                                router.get('/admin/talent-pool-assignments', {
                                    ...filters,
                                    company_employer_id: event.target.value,
                                })
                            }
                        >
                            <option value="">Select company</option>
                            {company_relationship.employers.map((employer) => (
                                <option key={employer.id} value={employer.id}>
                                    {employer.name}
                                </option>
                            ))}
                        </Select>
                    </div>
                    {company_relationship.selected_employer_id ? (
                        <p className="text-sm text-ink">
                            Current:{' '}
                            {company_relationship.active?.recruiter_name
                                ? `${company_relationship.active.recruiter_name} (${company_relationship.active.status_label})`
                                : 'Unassigned'}
                        </p>
                    ) : null}
                </div>
                {company_relationship.selected_employer_id && company_relationship.can_manage ? (
                    <form
                        className="flex flex-wrap items-end gap-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            companyRecruiterForm.post(
                                `/admin/employers/${company_relationship.selected_employer_id}/relationship-recruiter`,
                                { preserveScroll: true },
                            );
                        }}
                    >
                        <div>
                            <Label>OurNear recruiter</Label>
                            <Select
                                value={companyRecruiterForm.data.assigned_recruiter_user_id}
                                onChange={(event) =>
                                    companyRecruiterForm.setData('assigned_recruiter_user_id', event.target.value)
                                }
                            >
                                <option value="">Select recruiter</option>
                                {company_relationship.recruiters.map((recruiter) => (
                                    <option key={recruiter.id} value={recruiter.id}>
                                        {recruiter.name}
                                    </option>
                                ))}
                            </Select>
                        </div>
                        <div>
                            <Label>Notes (optional)</Label>
                            <Input
                                value={companyRecruiterForm.data.notes}
                                onChange={(event) => companyRecruiterForm.setData('notes', event.target.value)}
                            />
                        </div>
                        <Button type="submit" disabled={companyRecruiterForm.processing}>
                            Save assignment
                        </Button>
                    </form>
                ) : null}
                {company_relationship.history.length > 0 ? (
                    <ul className="mt-2 space-y-1 text-sm text-muted">
                        {company_relationship.history.map((row) => (
                            <li key={row.id}>
                                {row.recruiter_name ?? '—'} · {row.status_label}
                                {row.created_at ? ` · from ${row.created_at}` : ''}
                                {row.ended_at ? ` · ended ${row.ended_at}` : ''}
                            </li>
                        ))}
                    </ul>
                ) : null}
            </section>

            {can_submit_to_company ? (
                <section className="mb-10 space-y-4 rounded-xl border border-line bg-white p-4">
                    <h2 className="text-lg font-semibold text-ink">Submit candidate to company</h2>
                    <p className="text-sm text-muted">
                        Creates a Talent Pool company submission only — not a job application or staffing record.
                    </p>
                    <form
                        className="flex flex-wrap items-end gap-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            const first = selectedUserIds[0];
                            if (first) {
                                submitCompanyForm.setData('candidate_user_id', String(first));
                            }
                            const row = candidates.data.find((c) => c.user_id === first);
                            if (row?.active_assignment_id) {
                                submitCompanyForm.setData(
                                    'recruiter_talent_pool_assignment_id',
                                    String(row.active_assignment_id),
                                );
                            }
                            submitCompanyForm.post('/admin/talent-pool-company-submissions', {
                                preserveScroll: true,
                                onSuccess: () => submitCompanyForm.reset(),
                            });
                        }}
                    >
                        <div>
                            <Label>Company</Label>
                            <Select
                                value={submitCompanyForm.data.employer_profile_id}
                                onChange={(e) => submitCompanyForm.setData('employer_profile_id', e.target.value)}
                            >
                                <option value="">Select company</option>
                                {employers.map((employer) => (
                                    <option key={employer.id} value={employer.id}>
                                        {employer.name}
                                    </option>
                                ))}
                            </Select>
                        </div>
                        <div>
                            <Label>Owner override (optional)</Label>
                            <Select
                                value={submitCompanyForm.data.responsible_recruiter_user_id}
                                onChange={(e) =>
                                    submitCompanyForm.setData('responsible_recruiter_user_id', e.target.value)
                                }
                            >
                                <option value="">Auto from assignments</option>
                                {recruiters.map((r) => (
                                    <option key={r.id} value={r.id}>
                                        {r.name}
                                    </option>
                                ))}
                            </Select>
                        </div>
                        <Button type="submit" disabled={submitCompanyForm.processing || selectedUserIds.length !== 1}>
                            Submit selected candidate
                        </Button>
                    </form>
                    {company_submissions.length > 0 ? (
                        <ul className="mt-2 space-y-1 text-sm text-muted">
                            {company_submissions.map((row) => (
                                <li key={row.id}>
                                    {row.candidate_name} → {row.company_name} · {row.status_label} · owner{' '}
                                    {row.responsible_recruiter_name ?? '—'} ({row.owner_resolution_label})
                                </li>
                            ))}
                        </ul>
                    ) : null}
                </section>
            ) : null}

            <section className="mb-10 space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h2 className="text-lg font-semibold text-ink">Candidates</h2>
                    {can_manage ? (
                        <Button type="button" disabled={selectedUserIds.length === 0} onClick={openAssign}>
                            Assign to Recruiter ({selectedUserIds.length})
                        </Button>
                    ) : null}
                </div>

                <form onSubmit={applySearch} className="flex flex-wrap items-center gap-2">
                    <Input
                        className="max-w-md"
                        placeholder="Search name, email, ID, mobile…"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                    />
                    <Button type="submit">Search</Button>
                    <Button type="button" variant="secondary" onClick={toggleAllOnPage}>
                        Select all on page
                    </Button>
                </form>

                <DataTable
                    columns={candidateColumns}
                    rows={candidates.data}
                    rowKey={(row) => row.user_id}
                    emptyTitle="No candidates match your search."
                />
                <Pagination links={candidates.links} />
            </section>

            <section className="space-y-4 border-t border-line pt-8">
                <h2 className="text-lg font-semibold text-ink">Assignment tracking</h2>
                <div className="flex flex-wrap gap-2">
                    <Select
                        value={String(filters.recruiter_user_id ?? '')}
                        onChange={(event) =>
                            router.get('/admin/talent-pool-assignments', {
                                ...filters,
                                recruiter_user_id: event.target.value,
                            })
                        }
                    >
                        <option value="">All recruiters</option>
                        {recruiters.map((recruiter) => (
                            <option key={recruiter.id} value={recruiter.id}>
                                {recruiter.name}
                            </option>
                        ))}
                    </Select>
                    <Select
                        value={String(filters.assignment_type ?? '')}
                        onChange={(event) =>
                            router.get('/admin/talent-pool-assignments', {
                                ...filters,
                                assignment_type: event.target.value,
                            })
                        }
                    >
                        <option value="">All types</option>
                        {assignment_types.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </Select>
                    <Select
                        value={String(filters.status ?? '')}
                        onChange={(event) =>
                            router.get('/admin/talent-pool-assignments', { ...filters, status: event.target.value })
                        }
                    >
                        <option value="">All statuses</option>
                        {statuses.map((status) => (
                            <option key={status.value} value={status.value}>
                                {status.label}
                            </option>
                        ))}
                    </Select>
                    <Button
                        type="button"
                        variant={filters.follow_up_due ? 'primary' : 'secondary'}
                        onClick={() =>
                            router.get('/admin/talent-pool-assignments', {
                                ...filters,
                                follow_up_due: filters.follow_up_due ? '' : '1',
                            })
                        }
                    >
                        Overdue follow-ups
                    </Button>
                </div>

                <DataTable
                    columns={trackingColumns}
                    rows={assignments.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No assignments yet."
                />
                <Pagination links={assignments.links} />
            </section>

            {selected_assignment ? (
                <section className="mt-8 rounded-xl border border-line bg-white p-4">
                    <h3 className="text-base font-semibold text-ink">
                        Assignment #{selected_assignment.id} · {selected_assignment.candidate_name}
                    </h3>
                    <p className="mt-1 text-sm text-muted">
                        {selected_assignment.progress_stage_label} · {selected_assignment.assignment_type_label}
                        {selected_assignment.requirement_label ? ` · ${selected_assignment.requirement_label}` : ''}
                    </p>
                    <ul className="mt-4 space-y-2 text-sm text-muted">
                        {selected_assignment.status_events.map((event, index) => (
                            <li key={`${event.created_at}-${index}`}>
                                <span className="font-medium text-ink">
                                    {event.progress_stage_label ?? event.to_status_label}
                                </span>
                                {event.actor_name ? ` · ${event.actor_name}` : ''}
                                {event.created_at ? ` · ${event.created_at}` : ''}
                                {event.notes ? <p className="whitespace-pre-wrap">{event.notes}</p> : null}
                            </li>
                        ))}
                    </ul>
                    {selected_assignment.can_submit_to_staffing_client ? (
                        <form
                            className="mt-6 space-y-3 border-t border-line pt-4"
                            onSubmit={(event) => {
                                event.preventDefault();
                                submitStaffingClientForm.post(
                                    `/admin/talent-pool-assignments/${selected_assignment.id}/submit-to-staffing-client`,
                                    { preserveScroll: true },
                                );
                            }}
                        >
                            <h4 className="text-sm font-semibold text-ink">Submit to staffing client</h4>
                            <p className="text-sm text-muted">
                                Creates a staffing candidate submission for the linked requirement — not a job application or
                                Talent Pool company submission.
                            </p>
                            <div>
                                <Label>Internal notes (optional)</Label>
                                <Input
                                    value={submitStaffingClientForm.data.internal_notes}
                                    onChange={(e) => submitStaffingClientForm.setData('internal_notes', e.target.value)}
                                />
                            </div>
                            <Button type="submit" disabled={submitStaffingClientForm.processing}>
                                Submit to client
                            </Button>
                        </form>
                    ) : null}
                </section>
            ) : null}

            <Modal
                open={assignOpen}
                title="Assign to Recruiter"
                onClose={() => setAssignOpen(false)}
                footer={
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" onClick={() => setAssignOpen(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" form="assign-recruiter-form" disabled={assignForm.processing}>
                            Confirm assignment
                        </Button>
                    </div>
                }
            >
                <form id="assign-recruiter-form" onSubmit={submitAssign} className="space-y-3">
                    {Object.entries(assignForm.errors as Record<string, string>).map(([field, message]) => (
                        <p key={field} className="rounded-md bg-danger/10 px-3 py-2 text-sm text-danger">
                            {message}
                        </p>
                    ))}
                    <p className="text-sm text-muted">
                        {selectedCandidates.length} candidate{selectedCandidates.length === 1 ? '' : 's'} selected
                    </p>
                    <ul className="max-h-32 overflow-y-auto text-sm text-ink">
                        {selectedCandidates.map((row) => (
                            <li key={row.user_id}>
                                {row.name} {row.ournear_id ? `(${row.ournear_id})` : ''}
                            </li>
                        ))}
                    </ul>
                    <div>
                        <Label>Recruiter</Label>
                        <Select
                            value={assignForm.data.assigned_recruiter_user_id}
                            onChange={(event) => assignForm.setData('assigned_recruiter_user_id', event.target.value)}
                        >
                            <option value="">Select recruiter</option>
                            {recruiters.map((recruiter) => (
                                <option key={recruiter.id} value={recruiter.id}>
                                    {recruiter.name}
                                </option>
                            ))}
                        </Select>
                    </div>
                    <div>
                        <Label>Assignment type</Label>
                        <Select
                            value={assignForm.data.assignment_type}
                            onChange={(event) => {
                                const nextType = event.target.value;
                                assignForm.setData((data) => ({
                                    ...data,
                                    assignment_type: nextType,
                                    job_id: nextType === 'job' ? data.job_id : '',
                                    employer_enquiry_id: nextType === 'staffing' ? data.employer_enquiry_id : '',
                                }));
                            }}
                        >
                            {assignment_types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </Select>
                    </div>
                    {assignForm.data.assignment_type === 'job' ? (
                        <div>
                            <Label>Job</Label>
                            <Select
                                value={assignForm.data.job_id}
                                onChange={(event) => assignForm.setData('job_id', event.target.value)}
                            >
                                <option value="">Select job</option>
                                {jobsForRecruiter.map((job) => (
                                    <option key={job.id} value={job.id}>
                                        {job.title}
                                    </option>
                                ))}
                            </Select>
                        </div>
                    ) : null}
                    {assignForm.data.assignment_type === 'staffing' ? (
                        <div>
                            <Label>Staffing requirement (not a company)</Label>
                            {staffing_requirements.length === 0 ? (
                                <p className="mt-1 text-sm text-muted">
                                    No eligible staffing requirements yet. Companies appear in Admin → Employers; requirements
                                    are submitted after Staffing access is approved.
                                    {staffing_companies.some((c) => c.name.toLowerCase().includes('ournear')) ? (
                                        <span>
                                            {' '}
                                            OurNear is registered as a company but has no submitted requirement and may still
                                            need Staffing approval.
                                        </span>
                                    ) : null}
                                </p>
                            ) : (
                                <Select
                                    value={assignForm.data.employer_enquiry_id}
                                    onChange={(event) => assignForm.setData('employer_enquiry_id', event.target.value)}
                                >
                                    <option value="">Select requirement</option>
                                    {staffing_requirements.map((row) => (
                                        <option key={row.id} value={row.id}>
                                            {row.label} ({row.staffing_access_label})
                                        </option>
                                    ))}
                                </Select>
                            )}
                        </div>
                    ) : null}
                    <div>
                        <Label>Instructions (optional)</Label>
                        <Input
                            value={assignForm.data.admin_notes}
                            onChange={(event) => assignForm.setData('admin_notes', event.target.value)}
                        />
                    </div>
                    <div>
                        <Label>Follow-up date (optional)</Label>
                        <Input
                            type="date"
                            value={assignForm.data.follow_up_at}
                            onChange={(event) => assignForm.setData('follow_up_at', event.target.value)}
                        />
                    </div>
                </form>
            </Modal>
        </AdminLayout>
    );
}
