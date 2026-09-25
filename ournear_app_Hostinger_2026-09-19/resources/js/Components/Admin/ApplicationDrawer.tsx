import { Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import {
    ActivityFeed,
    Button,
    ConfirmDialog,
    Input,
    Label,
    Modal,
    Select,
    StatusBadge,
    Tabs,
    Textarea,
    Timeline,
} from '@/Components/Admin/ui';
import type { AdminApplication, RejectionReasonOption } from '@/types/applications';

type Props = {
    selected: AdminApplication;
    defaultTab?: string;
    statuses: string[];
    interviewModes: string[];
    interviewOutcomes: string[];
    rejectionReasons: RejectionReasonOption[];
};

function activityTitle(activity: NonNullable<AdminApplication['activities']>[number]): string {
    if (activity.type === 'status_changed') {
        return `Status: ${activity.from_status ?? '—'} → ${activity.to_status ?? '—'}`;
    }

    return activity.body || activity.type.replace(/_/g, ' ');
}

function DetailRows({ rows }: { rows: Array<[string, string]> }) {
    return (
        <dl className="grid gap-2 text-sm">
            {rows.map(([label, value]) => (
                <div key={label} className="grid grid-cols-3 gap-2">
                    <dt className="text-muted">{label}</dt>
                    <dd className="col-span-2 text-ink">{value}</dd>
                </div>
            ))}
        </dl>
    );
}

export default function ApplicationDrawer({
    selected,
    defaultTab = 'overview',
    statuses,
    interviewModes,
    interviewOutcomes,
    rejectionReasons,
}: Props) {
    const [tab, setTab] = useState(defaultTab);
    const [confirm, setConfirm] = useState<
        'shortlist' | 'suitable' | 'submit' | 'start' | 'match' | 'contact' | 'called' | 'interviewed' | 'hold' | null
    >(null);
    const [rejectOpen, setRejectOpen] = useState(false);
    const noteForm = useForm({ note: '' });
    const screeningForm = useForm({
        notes: selected.screening?.notes ?? '',
        rejection_reason_id: selected.screening?.rejection_reason_id ? String(selected.screening.rejection_reason_id) : '',
    });
    const interviewForm = useForm({
        scheduled_at: selected.interview?.scheduled_at ?? '',
        mode: selected.interview?.mode ?? 'phone',
        location_or_link: selected.interview?.location_or_link ?? '',
        interviewer_name: selected.interview?.interviewer_name ?? '',
        outcome: selected.interview?.outcome ?? 'pending',
        notes: selected.interview?.notes ?? '',
    });

    useEffect(() => {
        setTab(defaultTab);
        noteForm.reset();
        screeningForm.setData({
            notes: selected.screening?.notes ?? '',
            rejection_reason_id: selected.screening?.rejection_reason_id ? String(selected.screening.rejection_reason_id) : '',
        });
        interviewForm.setData({
            scheduled_at: selected.interview?.scheduled_at ?? '',
            mode: selected.interview?.mode ?? 'phone',
            location_or_link: selected.interview?.location_or_link ?? '',
            interviewer_name: selected.interview?.interviewer_name ?? '',
            outcome: selected.interview?.outcome ?? 'pending',
            notes: selected.interview?.notes ?? '',
        });
        setConfirm(null);
        setRejectOpen(false);
    }, [selected.id, defaultTab]);

    const submitScreening = (decision: string, extra: Record<string, string> = {}) => {
        router.put(`/admin/applications/${selected.id}/screening`, {
            decision,
            notes: screeningForm.data.notes,
            ...extra,
        });
    };

    const submitScreeningAction = (action: string) => {
        router.post(`/admin/applications/${selected.id}/screening/actions`, {
            action,
            remarks: screeningForm.data.notes,
            notes: screeningForm.data.notes,
        });
    };

    const saveInterview = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        interviewForm.put(`/admin/applications/${selected.id}/interview`);
    };

    return (
        <>
            <div className="mb-4 flex flex-wrap gap-3 text-sm">
                {selected.candidate.workspace_url ? (
                    <Link href={selected.candidate.workspace_url} className="font-medium text-brand">
                        Candidate
                    </Link>
                ) : null}
                {selected.job?.workspace_url ? (
                    <Link href={selected.job.workspace_url} className="font-medium text-brand">
                        Job
                    </Link>
                ) : null}
                {selected.links?.employer || selected.employer?.workspace_url ? (
                    <Link href={selected.links?.employer ?? selected.employer?.workspace_url ?? '#'} className="font-medium text-brand">
                        Employer
                    </Link>
                ) : null}
                {selected.links?.screening ? (
                    <Link href={selected.links.screening} className="font-medium text-brand">
                        Screening
                    </Link>
                ) : null}
                {selected.links?.shortlist ? (
                    <Link href={selected.links.shortlist} className="font-medium text-brand">
                        Shortlist
                    </Link>
                ) : null}
                {selected.links?.submissions ? (
                    <Link href={selected.links.submissions} className="font-medium text-brand">
                        Employer Submission
                    </Link>
                ) : null}
            </div>
            <Tabs
                tabs={[
                    { id: 'overview', label: 'Overview' },
                    { id: 'candidate', label: 'Candidate' },
                    { id: 'job', label: 'Job' },
                    { id: 'employer', label: 'Employer' },
                    { id: 'resume', label: 'Resume' },
                    { id: 'documents', label: 'Documents' },
                    { id: 'screening', label: 'Screening' },
                    { id: 'pipeline', label: 'Pipeline' },
                    { id: 'timeline', label: 'Timeline' },
                    { id: 'interview', label: 'Interview' },
                ]}
                active={tab}
                onChange={setTab}
            />

            {tab === 'overview' ? (
                <div className="mt-4 space-y-6">
                    <DetailRows
                        rows={[
                            ['Application ID', selected.business_id ?? '—'],
                            ['Status', selected.status],
                            ['Applied', selected.applied_at ?? '—'],
                            ['Updated', selected.updated_at ?? '—'],
                            ['Job ID', selected.job?.business_id ?? '—'],
                            ['Job', selected.job?.title ?? '—'],
                            ['Candidate ID', selected.candidate.business_id ?? '—'],
                            ['Candidate', selected.candidate.name],
                            ['Employer', selected.employer?.company_name ?? selected.job?.employer?.company_name ?? '—'],
                            ['Screening', selected.screening?.decision_label ?? 'Pending'],
                        ]}
                    />
                    <div>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Additional information</h3>
                        <p className="mt-2 whitespace-pre-line text-sm text-muted">
                            {selected.additional_information || 'None provided.'}
                        </p>
                    </div>
                    <section>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Application status</h3>
                        <Select
                            className="mt-2"
                            value={selected.status}
                            aria-label="Application status"
                            onChange={(event) =>
                                router.patch(`/admin/applications/${selected.id}/status`, {
                                    status: event.target.value,
                                })
                            }
                        >
                            {statuses.map((status) => (
                                <option key={status} value={status}>
                                    {status}
                                </option>
                            ))}
                        </Select>
                    </section>
                    <section>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Internal notes</h3>
                        <ActivityFeed
                            items={(selected.internal_notes ?? []).map((note, index) => ({
                                id: `${note.created_at}-${index}`,
                                title: note.body,
                                meta: `${note.author_name} · ${note.created_at}`,
                            }))}
                            empty="No notes yet."
                        />
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                noteForm.post(`/admin/applications/${selected.id}/notes`, {
                                    onSuccess: () => noteForm.reset(),
                                });
                            }}
                            className="mt-3 space-y-2"
                        >
                            <Textarea
                                value={noteForm.data.note}
                                onChange={(event) => noteForm.setData('note', event.target.value)}
                                placeholder="Add an internal note"
                            />
                            {noteForm.errors.note && <p className="text-sm text-danger">{noteForm.errors.note}</p>}
                            <Button type="submit" disabled={noteForm.processing}>
                                Add note
                            </Button>
                        </form>
                    </section>
                </div>
            ) : null}

            {tab === 'candidate' ? (
                <div className="mt-4 space-y-4">
                    <DetailRows
                        rows={[
                            ['Candidate ID', selected.candidate.business_id ?? '—'],
                            ['Name', selected.candidate.name],
                            ['Mobile', selected.candidate.mobile ?? selected.mobile],
                            ['Email', selected.candidate.email ?? selected.email],
                            ['Location', selected.candidate.location ?? selected.current_location],
                            ['Experience', selected.candidate.experience ?? selected.experience],
                            ['Qualification', selected.candidate.qualification ?? selected.qualification],
                        ]}
                    />
                    {selected.candidate.workspace_url ? (
                        <Link href={selected.candidate.workspace_url} className="inline-block text-sm font-medium text-brand">
                            Open Candidate Workspace
                        </Link>
                    ) : (
                        <p className="text-sm text-muted">This is a guest application, so there is no candidate account workspace.</p>
                    )}
                </div>
            ) : null}

            {tab === 'job' ? (
                <div className="mt-4 space-y-4">
                    <DetailRows
                        rows={[
                            ['Job ID', selected.job?.business_id ?? '—'],
                            ['Job title', selected.job?.title ?? '—'],
                            ['Industry', selected.job?.industry ?? '—'],
                            ['Job Category', selected.job?.category ?? '—'],
                            ['Location', selected.job?.location ?? '—'],
                            ['Employment Type', selected.job?.employment_type ?? '—'],
                            ['Experience', selected.job?.experience ?? '—'],
                            ['Job status', selected.job?.status_label ?? '—'],
                            ['Employer', selected.job?.employer?.company_name ?? selected.employer?.company_name ?? '—'],
                        ]}
                    />
                    {selected.job?.workspace_url ? (
                        <Link href={selected.job.workspace_url} className="inline-block text-sm font-medium text-brand">
                            Open Job Workspace
                        </Link>
                    ) : null}
                </div>
            ) : null}

            {tab === 'employer' ? (
                <div className="mt-4 space-y-4">
                    {selected.employer || selected.job?.employer ? (
                        <>
                            <DetailRows
                                rows={[
                                    ['Employer ID', selected.employer?.business_id ?? selected.job?.employer?.business_id ?? '—'],
                                    ['Company', selected.employer?.company_name ?? selected.job?.employer?.company_name ?? '—'],
                                    ['Contact', selected.employer?.contact_person ?? selected.job?.employer?.contact_person ?? '—'],
                                ]}
                            />
                            {(selected.links?.employer || selected.employer?.workspace_url || selected.job?.employer?.workspace_url) ? (
                                <Link
                                    href={selected.links?.employer ?? selected.employer?.workspace_url ?? selected.job?.employer?.workspace_url ?? '#'}
                                    className="inline-block text-sm font-medium text-brand"
                                >
                                    Open Employer Workspace
                                </Link>
                            ) : null}
                        </>
                    ) : (
                        <p className="text-sm text-muted">This job is not assigned to an employer yet.</p>
                    )}
                </div>
            ) : null}

            {tab === 'resume' ? (
                <div className="mt-4">
                    {selected.has_resume ? (
                        <div className="flex flex-wrap gap-3">
                            <a
                                href={selected.resume_view_url ?? `${selected.resume_url}?disposition=inline`}
                                className="inline-block text-sm font-medium text-brand"
                                target="_blank"
                                rel="noreferrer"
                            >
                                View Resume
                            </a>
                            <a href={selected.resume_url} className="inline-block text-sm font-medium text-brand">
                                Download Resume
                            </a>
                        </div>
                    ) : (
                        <p className="text-sm text-muted">No resume on file.</p>
                    )}
                </div>
            ) : null}

            {tab === 'documents' ? (
                <div className="mt-4 space-y-4">
                    <section>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Resume</h3>
                        {selected.has_resume ? (
                            <div className="mt-2 flex flex-wrap gap-3 text-sm">
                                <a href={selected.resume_view_url ?? `${selected.resume_url}?disposition=inline`} className="font-medium text-brand" target="_blank" rel="noreferrer">
                                    View Resume
                                </a>
                                <a href={selected.resume_url} className="font-medium text-brand">
                                    Download Resume
                                </a>
                            </div>
                        ) : (
                            <p className="mt-2 text-sm text-muted">No resume on file.</p>
                        )}
                    </section>
                    {['Certificates', 'Licences', 'Payslips', 'Experience documents', 'Other documents'].map((label) => (
                        <section key={label}>
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">{label}</h3>
                            <p className="mt-2 text-sm text-muted">Not recorded yet.</p>
                        </section>
                    ))}
                </div>
            ) : null}

            {tab === 'screening' ? (
                <div className="mt-4 space-y-6">
                    <section>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Decision</h3>
                        <div className="mt-2">
                            <DetailRows
                                rows={[
                                    ['Screening status', selected.screening?.stage_label ?? selected.screening_stage_label ?? 'Pending'],
                                    ['Decision', selected.screening?.decision_label ?? 'Pending'],
                                    ['Employer submission', selected.employer_submission_label ?? 'Has not been shortlisted'],
                                    ['Match %', selected.match?.label ?? 'Not enough data'],
                                    ['Screened', selected.screening?.screened_at ?? 'Not recorded yet'],
                                    ['Screened by', selected.screening?.screened_by ?? 'Not recorded yet'],
                                    ['Rejection reason', selected.screening?.rejection_reason ?? 'Not recorded yet'],
                                    ['Shortlisted', selected.screening?.shortlisted_at ?? 'Not recorded yet'],
                                    ['On hold', selected.screening?.held_at ?? 'Not recorded yet'],
                                ]}
                            />
                        </div>
                        <p className="mt-3 text-xs text-muted">
                            {selected.match?.disclaimer ??
                                'Match % compares recorded fields only. It does not determine suitability. The human recruitment decision remains final.'}
                        </p>
                    </section>

                    <section>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Match factors</h3>
                        <ul className="mt-2 space-y-2 text-sm">
                            {(selected.match?.factors ?? []).map((factor) => (
                                <li key={factor.key} className="rounded-md bg-surface px-3 py-2">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <span className="font-medium text-ink">{factor.label}</span>
                                        <StatusBadge
                                            status={
                                                factor.state === 'compared'
                                                    ? factor.matched
                                                        ? 'Matched'
                                                        : 'Does not match'
                                                    : factor.state === 'unavailable'
                                                      ? 'Not available in V1.1'
                                                      : 'Not enough data'
                                            }
                                        />
                                    </div>
                                    <p className="mt-1 text-xs text-muted">{factor.note}</p>
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Screening stages</h3>
                        <div className="mt-2 overflow-x-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead className="text-muted">
                                    <tr>
                                        <th className="px-2 py-2 font-medium">Stage</th>
                                        <th className="px-2 py-2 font-medium">Status</th>
                                        <th className="px-2 py-2 font-medium">Date/time</th>
                                        <th className="px-2 py-2 font-medium">Performed by</th>
                                        <th className="px-2 py-2 font-medium">Remarks</th>
                                        <th className="px-2 py-2 font-medium">Evidence</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(selected.screening_stages ?? []).map((stage) => (
                                        <tr key={stage.key} className="border-t border-line">
                                            <td className="px-2 py-2 text-ink">{stage.label}</td>
                                            <td className="px-2 py-2">
                                                <StatusBadge status={stage.status} />
                                            </td>
                                            <td className="px-2 py-2 text-muted">{stage.occurred_at ?? 'Not recorded yet'}</td>
                                            <td className="px-2 py-2 text-muted">{stage.performed_by ?? 'Not recorded yet'}</td>
                                            <td className="px-2 py-2 text-muted">{stage.remarks ?? 'Not recorded yet'}</td>
                                            <td className="px-2 py-2 text-muted">{stage.evidence ?? 'Not recorded yet'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Screening timeline</h3>
                        <div className="mt-2">
                            <Timeline
                                items={(selected.screening_timeline ?? []).map((item) => ({
                                    id: item.id,
                                    title: item.action,
                                    meta: [
                                        item.occurred_at,
                                        item.status,
                                        item.actor_name,
                                        item.remarks,
                                    ]
                                        .filter(Boolean)
                                        .join(' · '),
                                }))}
                                empty="No screening activity yet. Viewing this page does not create activity."
                            />
                        </div>
                    </section>

                    <div>
                        <Label htmlFor="screening-notes">Remarks</Label>
                        <Textarea
                            id="screening-notes"
                            value={screeningForm.data.notes}
                            onChange={(event) => screeningForm.setData('notes', event.target.value)}
                            placeholder="Remarks for the next screening action"
                        />
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button disabled={!selected.can_start_screening} onClick={() => setConfirm('start')}>
                            Start Screening
                        </Button>
                        <Button disabled={!selected.can_mark_suitable} onClick={() => setConfirm('match')}>
                            Mark Matched
                        </Button>
                        <Button variant="secondary" disabled={!selected.can_contact} onClick={() => setConfirm('contact')}>
                            Contact
                        </Button>
                        <Button variant="secondary" disabled={!selected.can_mark_called} onClick={() => setConfirm('called')}>
                            Mark Called
                        </Button>
                        <Button variant="secondary" disabled={!selected.can_mark_interviewed} onClick={() => setConfirm('interviewed')}>
                            Mark Interviewed
                        </Button>
                        <Button variant="secondary" disabled title="Not available in V1.1">
                            Verify available documents
                        </Button>
                        <Button variant="secondary" disabled={!selected.can_shortlist} onClick={() => setConfirm('shortlist')}>
                            Shortlist
                        </Button>
                        <Button variant="secondary" disabled={!selected.can_hold} onClick={() => setConfirm('hold')}>
                            Hold
                        </Button>
                        <Button variant="danger" disabled={!selected.can_mark_not_suitable} onClick={() => setRejectOpen(true)}>
                            Reject
                        </Button>
                        <Button disabled={!selected.can_submit_to_employer} onClick={() => setConfirm('submit')}>
                            Submit to Employer
                        </Button>
                    </div>
                    <p className="text-sm text-muted">Document verification types are not available in V1.1. No fake verification records are created.</p>
                    {selected.submit_blocked_reason ? (
                        <p className="text-sm text-muted">{selected.submit_blocked_reason}</p>
                    ) : null}
                    <div className="flex flex-wrap gap-3">
                        {selected.job?.id ? (
                            <Link href={`/admin/shortlisted?job_id=${selected.job.id}`} className="inline-block text-sm font-medium text-brand">
                                Open Shortlisted workspace
                            </Link>
                        ) : null}
                        {selected.links?.submissions ? (
                            <Link href={selected.links.submissions} className="inline-block text-sm font-medium text-brand">
                                Open Employer Submission
                            </Link>
                        ) : null}
                    </div>
                </div>
            ) : null}

            {tab === 'pipeline' ? (
                <div className="mt-4 space-y-5">
                    <section>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Shortlist</h3>
                        <p className="mt-2 text-sm text-ink">
                            {selected.screening?.decision_label ?? 'Pending'}
                            {selected.screening?.shortlisted_at ? ` · ${selected.screening.shortlisted_at}` : ''}
                        </p>
                        {selected.links?.shortlist ? (
                            <Link href={selected.links.shortlist} className="mt-2 inline-block text-sm font-medium text-brand">
                                Open shortlist
                            </Link>
                        ) : null}
                    </section>
                    <section>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Employer Submission</h3>
                        {(selected.submissions?.length ?? 0) === 0 ? (
                            <p className="mt-2 text-sm text-muted">Not submitted to an employer yet.</p>
                        ) : (
                            <ul className="mt-2 space-y-2 text-sm">
                                {selected.submissions?.map((submission) => (
                                    <li key={submission.id} className="rounded-md bg-surface px-3 py-2">
                                        {submission.business_id ?? `Submission ${submission.id}`} · {submission.status}
                                        {submission.employer?.company_name ? ` · ${submission.employer.company_name}` : ''}
                                    </li>
                                ))}
                            </ul>
                        )}
                        {selected.links?.submissions ? (
                            <Link href={selected.links.submissions} className="mt-2 inline-block text-sm font-medium text-brand">
                                Open submissions
                            </Link>
                        ) : null}
                    </section>
                    <section>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Selection</h3>
                        {selected.status === 'Selected' || (selected.submissions ?? []).some((item) => item.status === 'Selected') ? (
                            <p className="mt-2 text-sm text-ink">
                                {selected.status === 'Selected' ? 'Application selected' : 'Employer selected this candidate'}
                            </p>
                        ) : (
                            <p className="mt-2 text-sm text-muted">No selection recorded yet.</p>
                        )}
                    </section>
                    <section>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Placement</h3>
                        {(selected.placements?.length ?? 0) === 0 ? (
                            <p className="mt-2 text-sm text-muted">No placement recorded yet.</p>
                        ) : (
                            <ul className="mt-2 space-y-2 text-sm">
                                {selected.placements?.map((placement) => (
                                    <li key={placement.id} className="rounded-md bg-surface px-3 py-2">
                                        {placement.business_id ?? `Placement ${placement.id}`} · {placement.status}
                                    </li>
                                ))}
                            </ul>
                        )}
                        {selected.links?.placements ? (
                            <Link href={selected.links.placements} className="mt-2 inline-block text-sm font-medium text-brand">
                                Open placements
                            </Link>
                        ) : null}
                    </section>
                </div>
            ) : null}

            {tab === 'timeline' ? (
                <div className="mt-4">
                    <Timeline
                        items={(selected.activities ?? []).map((activity) => ({
                            id: activity.id,
                            title: activityTitle(activity),
                            meta: `${activity.actor_name ?? 'System'} · ${activity.created_at ?? ''}`,
                        }))}
                        empty="No recruitment activity yet."
                    />
                </div>
            ) : null}

            {tab === 'interview' ? (
                <form onSubmit={saveInterview} className="mt-4 space-y-2">
                    <Input
                        type="datetime-local"
                        value={interviewForm.data.scheduled_at}
                        onChange={(event) => interviewForm.setData('scheduled_at', event.target.value)}
                        required
                        aria-label="Interview time"
                    />
                    {interviewForm.errors.scheduled_at && (
                        <p className="text-sm text-danger">{interviewForm.errors.scheduled_at}</p>
                    )}
                    <Select
                        value={interviewForm.data.mode}
                        onChange={(event) => interviewForm.setData('mode', event.target.value)}
                        aria-label="Interview mode"
                    >
                        {interviewModes.map((mode) => (
                            <option key={mode} value={mode}>
                                {mode.replace('_', ' ')}
                            </option>
                        ))}
                    </Select>
                    <Input
                        placeholder="Location or link"
                        value={interviewForm.data.location_or_link}
                        onChange={(event) => interviewForm.setData('location_or_link', event.target.value)}
                    />
                    <Input
                        placeholder="Interviewer name"
                        value={interviewForm.data.interviewer_name}
                        onChange={(event) => interviewForm.setData('interviewer_name', event.target.value)}
                        required
                    />
                    {interviewForm.errors.interviewer_name && (
                        <p className="text-sm text-danger">{interviewForm.errors.interviewer_name}</p>
                    )}
                    <Select
                        value={interviewForm.data.outcome}
                        onChange={(event) => interviewForm.setData('outcome', event.target.value)}
                        aria-label="Interview outcome"
                    >
                        {interviewOutcomes.map((outcome) => (
                            <option key={outcome} value={outcome}>
                                {outcome.replace('_', ' ')}
                            </option>
                        ))}
                    </Select>
                    <Textarea
                        className="min-h-20"
                        placeholder="Internal interview notes"
                        value={interviewForm.data.notes}
                        onChange={(event) => interviewForm.setData('notes', event.target.value)}
                    />
                    <Button type="submit" disabled={interviewForm.processing}>
                        Save interview
                    </Button>
                </form>
            ) : null}

            <ConfirmDialog
                open={confirm !== null}
                title={
                    confirm === 'submit'
                        ? 'Submit this candidate to the employer?'
                        : confirm === 'shortlist'
                          ? 'Shortlist this candidate?'
                          : confirm === 'start'
                            ? 'Start screening this application?'
                            : confirm === 'match' || confirm === 'suitable'
                              ? 'Mark this candidate matched?'
                              : confirm === 'contact'
                                ? 'Record that this candidate was contacted?'
                                : confirm === 'called'
                                  ? 'Record that this candidate was called?'
                                  : confirm === 'interviewed'
                                    ? 'Record that this candidate was interviewed?'
                                    : confirm === 'hold'
                                      ? 'Put this candidate on hold?'
                                      : 'Confirm screening action?'
                }
                description={
                    confirm === 'submit'
                        ? selected.submit_blocked_reason ??
                          'This creates an employer submission for this job only. It does not select or place the candidate.'
                        : confirm === 'shortlist'
                          ? 'This shortlists the candidate for this job only. Employer selection is a later step. Screening history is preserved.'
                          : confirm === 'start'
                            ? 'This starts screening and may move a New application to Reviewing. Viewing the page does not start screening.'
                            : confirm === 'match' || confirm === 'suitable'
                              ? 'This records a matched screening decision. Match % does not determine suitability. The human decision remains final.'
                              : confirm === 'hold'
                                ? 'This pauses recruitment for this application. It does not reject the candidate.'
                                : 'This records a screening activity with the remarks entered above.'
                }
                confirmLabel={
                    confirm === 'submit'
                        ? 'Submit'
                        : confirm === 'shortlist'
                          ? 'Shortlist'
                          : confirm === 'start'
                            ? 'Start screening'
                            : confirm === 'match' || confirm === 'suitable'
                              ? 'Mark matched'
                              : confirm === 'hold'
                                ? 'Hold'
                                : 'Confirm'
                }
                onConfirm={() => {
                    if (confirm === 'shortlist') {
                        router.post(`/admin/applications/${selected.id}/shortlist`, {
                            notes: screeningForm.data.notes,
                        });
                    } else if (confirm === 'submit') {
                        router.post(`/admin/applications/${selected.id}/submit`);
                    } else if (confirm === 'suitable') {
                        submitScreening('suitable');
                    } else if (confirm) {
                        const actionMap: Record<string, string> = {
                            start: 'start',
                            match: 'match',
                            contact: 'contact',
                            called: 'called',
                            interviewed: 'interviewed',
                            hold: 'hold',
                        };
                        const action = actionMap[confirm];
                        if (action) {
                            submitScreeningAction(action);
                        }
                    }
                    setConfirm(null);
                }}
                onClose={() => setConfirm(null)}
            />

            <Modal
                open={rejectOpen}
                title="Reject this candidate?"
                onClose={() => setRejectOpen(false)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setRejectOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            variant="danger"
                            onClick={() => {
                                submitScreening('not_suitable', {
                                    rejection_reason_id: screeningForm.data.rejection_reason_id,
                                });
                                setRejectOpen(false);
                            }}
                            disabled={!screeningForm.data.rejection_reason_id}
                        >
                            Mark not suitable / Reject
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left">
                    <p>The application stays on file. A rejection reason is required.</p>
                    <div>
                        <Label htmlFor="rejection_reason_id">Rejection reason</Label>
                        <Select
                            id="rejection_reason_id"
                            value={screeningForm.data.rejection_reason_id}
                            onChange={(event) => screeningForm.setData('rejection_reason_id', event.target.value)}
                        >
                            <option value="">Select a reason</option>
                            {rejectionReasons
                                .filter((reason) => reason.selectable !== false)
                                .map((reason) => (
                                    <option key={reason.id} value={reason.id}>
                                        {reason.name}
                                    </option>
                                ))}
                        </Select>
                    </div>
                </div>
            </Modal>
        </>
    );
}
