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
import type {
    AdminApplication,
    JobComparisonRow,
    LabeledOption,
    RejectionReasonOption,
    ScorecardTemplateOption,
    ScreeningMatchFactor,
    StaffUserOption,
} from '@/types/applications';
import FollowUpTaskForm from '@/Components/Admin/FollowUpTaskForm';

type Props = {
    selected: AdminApplication;
    defaultTab?: string;
    statuses: string[];
    interviewModes: string[];
    interviewOutcomes: string[];
    interviewTypes?: LabeledOption[];
    staffUsers?: StaffUserOption[];
    scorecardTemplates?: ScorecardTemplateOption[];
    rejectionReasons: RejectionReasonOption[];
    can_create_task?: boolean;
};

function activityTitle(activity: NonNullable<AdminApplication['activities']>[number]): string {
    if (activity.type === 'status_changed') {
        return `Status: ${activity.from_status ?? '—'} → ${activity.to_status ?? '—'}`;
    }

    return activity.body || activity.type.replace(/_/g, ' ');
}

function recordedValue(value?: string | null): string {
    return value && value.trim() !== '' ? value : 'Not recorded';
}

function factorStateLabel(factor: Pick<ScreeningMatchFactor, 'state' | 'matched'>): string {
    if (factor.state === 'compared') {
        return factor.matched ? 'Matched' : 'Partial';
    }

    if (factor.state === 'presented') {
        return 'Presented';
    }

    if (factor.state === 'missing') {
        return 'Missing';
    }

    if (factor.state === 'unavailable') {
        return 'Unavailable';
    }

    return factor.state;
}

function sourceLabel(source?: string | null): string {
    if (source === 'snapshot') {
        return 'Application snapshot';
    }

    if (source === 'profile') {
        return 'Current candidate profile';
    }

    return 'Not recorded';
}

function CandidateVsJobDesk({
    rows,
    snapshotNote,
}: {
    rows: Array<JobComparisonRow | ScreeningMatchFactor>;
    snapshotNote: string;
}) {
    return (
        <div className="overflow-x-auto rounded-lg border border-line">
            <table className="min-w-full text-left text-sm">
                <thead className="bg-surface text-muted">
                    <tr>
                        <th className="px-3 py-2 font-medium">Factor</th>
                        <th className="px-3 py-2 font-medium">Job recorded value</th>
                        <th className="px-3 py-2 font-medium">Candidate / application</th>
                        <th className="px-3 py-2 font-medium">State</th>
                        <th className="px-3 py-2 font-medium">Match information</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr key={row.key} className="border-t border-line align-top">
                            <th className="px-3 py-2 font-medium text-ink">
                                {row.label}
                                <p className="mt-1 text-xs font-normal text-muted">{sourceLabel(row.source)}</p>
                            </th>
                            <td className="px-3 py-2 text-ink">{recordedValue(row.job_value)}</td>
                            <td className="px-3 py-2 text-ink">{recordedValue(row.candidate_value)}</td>
                            <td className="px-3 py-2">
                                <StatusBadge status={factorStateLabel(row)} />
                            </td>
                            <td className="px-3 py-2 text-muted">
                                {row.state === 'compared'
                                    ? row.matched
                                        ? 'Recorded values match.'
                                        : 'Recorded values do not match.'
                                    : row.note}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
            <p className="border-t border-line px-3 py-2 text-xs text-muted">{snapshotNote}</p>
        </div>
    );
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
    interviewTypes = [],
    staffUsers = [],
    scorecardTemplates = [],
    rejectionReasons,
    can_create_task = false,
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
        scheduled_at: '',
        mode: 'phone',
        location_or_link: '',
        interviewer_name: '',
        interviewer_user_id: '',
        type: '',
        outcome: 'pending',
        notes: '',
    });
    const scorecardForm = useForm({
        scorecard_template_id: '',
        numeric_score: '',
        notes: '',
        qualitative: {} as Record<string, string>,
    });
    const [rescheduleId, setRescheduleId] = useState<number | null>(null);
    const [cancelId, setCancelId] = useState<number | null>(null);
    const [scorecardId, setScorecardId] = useState<number | null>(null);
    const rescheduleForm = useForm({
        scheduled_at: '',
        mode: 'phone',
        location_or_link: '',
        interviewer_name: '',
        interviewer_user_id: '',
        type: '',
        notes: '',
    });
    const cancelForm = useForm({ cancelled_reason: '' });

    useEffect(() => {
        setTab(defaultTab);
        noteForm.reset();
        screeningForm.setData({
            notes: selected.screening?.notes ?? '',
            rejection_reason_id: selected.screening?.rejection_reason_id ? String(selected.screening.rejection_reason_id) : '',
        });
        interviewForm.setData({
            scheduled_at: '',
            mode: 'phone',
            location_or_link: '',
            interviewer_name: '',
            interviewer_user_id: '',
            type: '',
            outcome: 'pending',
            notes: '',
        });
        scorecardForm.reset();
        rescheduleForm.reset();
        cancelForm.reset();
        setRescheduleId(null);
        setCancelId(null);
        setScorecardId(null);
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
        interviewForm.transform((data) => ({
            ...data,
            interviewer_user_id: data.interviewer_user_id || null,
            type: data.type || null,
        }));
        interviewForm.put(`/admin/applications/${selected.id}/interview`);
    };

    const interviews = selected.interviews?.length ? selected.interviews : selected.interview ? [selected.interview] : [];
    const activeScorecardTemplate = scorecardTemplates.find(
        (template) => String(template.id) === scorecardForm.data.scorecard_template_id,
    );

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
                    {can_create_task ? (
                        <FollowUpTaskForm
                            key={`application-task-${selected.id}`}
                            staffUsers={staffUsers}
                            defaults={{
                                title: `Follow up: ${selected.full_name}`,
                                application_id: selected.id,
                                job_id: selected.job?.id ?? null,
                                candidate_profile_id: selected.candidate?.id ?? null,
                            }}
                        />
                    ) : null}
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
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Candidate vs Job</h3>
                        <p className="mt-2 text-xs text-muted">
                            {selected.job_comparison?.disclaimer ??
                                selected.match?.disclaimer ??
                                'Match % compares recorded fields only; does not determine suitability; human decision remains final.'}
                        </p>
                        <div className="mt-3">
                            <CandidateVsJobDesk
                                rows={selected.job_comparison?.rows ?? selected.match?.factors ?? []}
                                snapshotNote={
                                    selected.job_comparison?.snapshot_note ??
                                    'Location, experience, and qualification use the application snapshot. Other professional fields use the current candidate profile when an account exists.'
                                }
                            />
                        </div>
                    </section>

                    {selected.automation_result ? (
                        <section>
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Automation recommendation</h3>
                            <p className="mt-2 text-sm text-ink">
                                {selected.automation_result.match_label
                                    ? `Match: ${selected.automation_result.match_label}`
                                    : 'Match: not scored'}
                            </p>
                            <p className="mt-1 text-xs text-muted">
                                {selected.automation_result.automation_name}
                                {selected.automation_result.trigger_label ? ` · ${selected.automation_result.trigger_label}` : ''}
                                {selected.automation_result.application_origin
                                    ? ` · ${selected.automation_result.application_origin === 'guest' ? 'OurNear Jobs / guest apply' : 'Account application'}`
                                    : ''}
                            </p>
                            {selected.automation_result.recommendation ? (
                                <p className="mt-2 text-sm text-muted">{selected.automation_result.recommendation}</p>
                            ) : null}
                            {(selected.automation_result.matching_factors ?? []).length > 0 ? (
                                <div className="mt-3">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-muted">Matching factors</p>
                                    <ul className="mt-1 space-y-1 text-sm">
                                        {(selected.automation_result.matching_factors ?? []).map((factor) => (
                                            <li key={factor}>✓ {factor}</li>
                                        ))}
                                    </ul>
                                </div>
                            ) : null}
                            {(selected.automation_result.potential_gaps ?? []).length > 0 ? (
                                <div className="mt-3">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-muted">Potential gaps</p>
                                    <ul className="mt-1 space-y-1 text-sm">
                                        {(selected.automation_result.potential_gaps ?? []).map((factor) => (
                                            <li key={factor}>⚠ {factor}</li>
                                        ))}
                                    </ul>
                                </div>
                            ) : null}
                            <p className="mt-2 text-xs text-muted">
                                Stored from the existing ScreeningMatchService result. Automation does not shortlist.
                            </p>
                        </section>
                    ) : null}

                    <section>
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Match factors</h3>
                        <ul className="mt-2 space-y-2 text-sm">
                            {(selected.match?.factors ?? []).map((factor) => (
                                <li key={factor.key} className="rounded-md bg-surface px-3 py-2">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <span className="font-medium text-ink">{factor.label}</span>
                                        <StatusBadge status={factorStateLabel(factor)} />
                                    </div>
                                    <p className="mt-1 text-xs text-muted">
                                        Job: {recordedValue(factor.job_value)} · Candidate: {recordedValue(factor.candidate_value)}
                                    </p>
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
                        <Button variant="secondary" disabled title="Document verification is not stored on applications yet.">
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
                <div className="mt-4 space-y-6">
                    {interviews.length === 0 ? (
                        <p className="text-sm text-muted">No interviews scheduled yet. The first save becomes interview #1.</p>
                    ) : (
                        <ul className="space-y-3">
                            {interviews.map((interview) => (
                                <li key={interview.id} className="rounded-lg border border-line p-3">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <p className="text-sm font-semibold text-ink">
                                            Interview #{interview.sort_order ?? interview.id}
                                            {interview.type_label ? ` · ${interview.type_label}` : ''}
                                        </p>
                                        <StatusBadge status={interview.lifecycle_label ?? interview.lifecycle_status ?? 'scheduled'} />
                                    </div>
                                    <p className="mt-1 text-sm text-muted">
                                        {[interview.scheduled_at_label ?? interview.scheduled_at, interview.mode.replace('_', ' '), interview.interviewer_name]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                    {interview.scorecard ? (
                                        <p className="mt-1 text-sm text-muted">
                                            Scorecard
                                            {interview.scorecard.numeric_score !== null ? ` · ${interview.scorecard.numeric_score}` : ''}
                                            {' · does not change hiring status'}
                                        </p>
                                    ) : null}
                                    <div className="mt-3 flex flex-wrap gap-2">
                                        {interview.can_confirm ? (
                                            <Button
                                                variant="secondary"
                                                onClick={() => router.post(`/admin/interviews/${interview.id}/confirm`)}
                                            >
                                                Confirm
                                            </Button>
                                        ) : null}
                                        {interview.can_reschedule ? (
                                            <Button
                                                variant="secondary"
                                                onClick={() => {
                                                    setRescheduleId(interview.id);
                                                    rescheduleForm.setData({
                                                        scheduled_at: interview.scheduled_at ?? '',
                                                        mode: interview.mode,
                                                        location_or_link: interview.location_or_link ?? '',
                                                        interviewer_name: interview.interviewer_name ?? '',
                                                        interviewer_user_id: interview.interviewer_user_id
                                                            ? String(interview.interviewer_user_id)
                                                            : '',
                                                        type: interview.type ?? '',
                                                        notes: interview.notes ?? '',
                                                    });
                                                }}
                                            >
                                                Reschedule
                                            </Button>
                                        ) : null}
                                        {interview.can_cancel ? (
                                            <Button variant="danger" onClick={() => setCancelId(interview.id)}>
                                                Cancel
                                            </Button>
                                        ) : null}
                                        <Button variant="secondary" onClick={() => setScorecardId(interview.id)}>
                                            {interview.scorecard ? 'Update scorecard' : 'Add scorecard'}
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}

                    <form onSubmit={saveInterview} className="space-y-2">
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Schedule another interview</h3>
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
                            value={interviewForm.data.type}
                            onChange={(event) => interviewForm.setData('type', event.target.value)}
                            aria-label="Interview type"
                        >
                            <option value="">Type (optional)</option>
                            {interviewTypes.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </Select>
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
                        <Select
                            value={interviewForm.data.interviewer_user_id}
                            onChange={(event) => interviewForm.setData('interviewer_user_id', event.target.value)}
                            aria-label="Interviewer user"
                        >
                            <option value="">Interviewer (staff, optional)</option>
                            {staffUsers.map((user) => (
                                <option key={user.id} value={user.id}>
                                    {user.name}
                                </option>
                            ))}
                        </Select>
                        <Input
                            placeholder="Interviewer name"
                            value={interviewForm.data.interviewer_name}
                            onChange={(event) => interviewForm.setData('interviewer_name', event.target.value)}
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
                            Schedule interview
                        </Button>
                    </form>
                </div>
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

            <Modal
                open={rescheduleId !== null}
                title="Reschedule interview"
                onClose={() => setRescheduleId(null)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setRescheduleId(null)}>
                            Close
                        </Button>
                        <Button
                            onClick={() => {
                                if (rescheduleId === null) {
                                    return;
                                }
                                rescheduleForm.transform((data) => ({
                                    ...data,
                                    interviewer_user_id: data.interviewer_user_id || null,
                                    type: data.type || null,
                                }));
                                rescheduleForm.post(`/admin/interviews/${rescheduleId}/reschedule`, {
                                    onSuccess: () => setRescheduleId(null),
                                });
                            }}
                            disabled={rescheduleForm.processing}
                        >
                            Reschedule
                        </Button>
                    </>
                }
            >
                <div className="space-y-2 text-left">
                    <p className="text-sm text-muted">This keeps the original interview as rescheduled and creates the next interview row.</p>
                    <Input
                        type="datetime-local"
                        value={rescheduleForm.data.scheduled_at}
                        onChange={(event) => rescheduleForm.setData('scheduled_at', event.target.value)}
                        aria-label="New interview time"
                    />
                    <Select
                        value={rescheduleForm.data.mode}
                        onChange={(event) => rescheduleForm.setData('mode', event.target.value)}
                        aria-label="New interview mode"
                    >
                        {interviewModes.map((mode) => (
                            <option key={mode} value={mode}>
                                {mode.replace('_', ' ')}
                            </option>
                        ))}
                    </Select>
                    <Input
                        placeholder="Location or link"
                        value={rescheduleForm.data.location_or_link}
                        onChange={(event) => rescheduleForm.setData('location_or_link', event.target.value)}
                    />
                </div>
            </Modal>

            <Modal
                open={cancelId !== null}
                title="Cancel interview"
                onClose={() => setCancelId(null)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setCancelId(null)}>
                            Close
                        </Button>
                        <Button
                            variant="danger"
                            onClick={() => {
                                if (cancelId === null) {
                                    return;
                                }
                                cancelForm.post(`/admin/interviews/${cancelId}/cancel`, {
                                    onSuccess: () => setCancelId(null),
                                });
                            }}
                            disabled={cancelForm.processing}
                        >
                            Cancel interview
                        </Button>
                    </>
                }
            >
                <Textarea
                    className="min-h-20"
                    placeholder="Cancellation reason"
                    value={cancelForm.data.cancelled_reason}
                    onChange={(event) => cancelForm.setData('cancelled_reason', event.target.value)}
                />
            </Modal>

            <Modal
                open={scorecardId !== null}
                title="Interview scorecard"
                onClose={() => setScorecardId(null)}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setScorecardId(null)}>
                            Close
                        </Button>
                        <Button
                            onClick={() => {
                                if (scorecardId === null) {
                                    return;
                                }
                                scorecardForm.transform((data) => ({
                                    scorecard_template_id: data.scorecard_template_id || null,
                                    numeric_score: data.numeric_score === '' ? null : data.numeric_score,
                                    notes: data.notes,
                                    qualitative: data.qualitative,
                                }));
                                scorecardForm.post(`/admin/interviews/${scorecardId}/scorecard`, {
                                    onSuccess: () => setScorecardId(null),
                                });
                            }}
                            disabled={scorecardForm.processing}
                        >
                            Save scorecard
                        </Button>
                    </>
                }
            >
                <div className="space-y-3 text-left">
                    <p className="text-sm text-muted">Scores are recorded only. They never change hiring or ATS status.</p>
                    <Select
                        value={scorecardForm.data.scorecard_template_id}
                        onChange={(event) => scorecardForm.setData('scorecard_template_id', event.target.value)}
                        aria-label="Scorecard template"
                    >
                        <option value="">No template</option>
                        {scorecardTemplates.map((template) => (
                            <option key={template.id} value={template.id}>
                                {template.name}
                            </option>
                        ))}
                    </Select>
                    {(activeScorecardTemplate?.criteria ?? []).map((criterion) => (
                        <div key={criterion.key}>
                            <Label htmlFor={`score-${criterion.key}`}>{criterion.label}</Label>
                            <Textarea
                                id={`score-${criterion.key}`}
                                value={scorecardForm.data.qualitative[criterion.key] ?? ''}
                                onChange={(event) =>
                                    scorecardForm.setData('qualitative', {
                                        ...scorecardForm.data.qualitative,
                                        [criterion.key]: event.target.value,
                                    })
                                }
                            />
                        </div>
                    ))}
                    <Input
                        type="number"
                        min={0}
                        max={100}
                        placeholder="Optional numeric score"
                        value={scorecardForm.data.numeric_score}
                        onChange={(event) => scorecardForm.setData('numeric_score', event.target.value)}
                    />
                    <Textarea
                        placeholder="Scorecard notes"
                        value={scorecardForm.data.notes}
                        onChange={(event) => scorecardForm.setData('notes', event.target.value)}
                    />
                </div>
            </Modal>
        </>
    );
}
