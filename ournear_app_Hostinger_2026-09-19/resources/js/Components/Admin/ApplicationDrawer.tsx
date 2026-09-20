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
    const [confirm, setConfirm] = useState<'shortlist' | 'suitable' | 'submit' | null>(null);
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

    const saveInterview = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        interviewForm.put(`/admin/applications/${selected.id}/interview`);
    };

    return (
        <>
            <Tabs
                tabs={[
                    { id: 'overview', label: 'Overview' },
                    { id: 'candidate', label: 'Candidate' },
                    { id: 'job', label: 'Job' },
                    { id: 'resume', label: 'Resume' },
                    { id: 'screening', label: 'Screening' },
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
                        ]}
                    />
                    {selected.job?.workspace_url ? (
                        <Link href={selected.job.workspace_url} className="inline-block text-sm font-medium text-brand">
                            Open Job Workspace
                        </Link>
                    ) : null}
                </div>
            ) : null}

            {tab === 'resume' ? (
                <div className="mt-4">
                    {selected.has_resume ? (
                        <a href={selected.resume_url} className="inline-block text-sm font-medium text-brand">
                            Download resume
                        </a>
                    ) : (
                        <p className="text-sm text-muted">No resume on file.</p>
                    )}
                </div>
            ) : null}

            {tab === 'screening' ? (
                <div className="mt-4 space-y-4">
                    <DetailRows
                        rows={[
                            ['Decision', selected.screening?.decision_label ?? 'Pending'],
                            ['Screened', selected.screening?.screened_at ?? '—'],
                            ['Screened by', selected.screening?.screened_by ?? '—'],
                            ['Rejection reason', selected.screening?.rejection_reason ?? '—'],
                            ['Shortlisted', selected.screening?.shortlisted_at ?? '—'],
                        ]}
                    />
                    <div>
                        <Label htmlFor="screening-notes">Screening notes</Label>
                        <Textarea
                            id="screening-notes"
                            value={screeningForm.data.notes}
                            onChange={(event) => screeningForm.setData('notes', event.target.value)}
                            placeholder="Notes for this screening decision"
                        />
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            disabled={!selected.can_mark_suitable}
                            onClick={() => setConfirm('suitable')}
                        >
                            Mark suitable
                        </Button>
                        <Button
                            variant="danger"
                            disabled={!selected.can_mark_not_suitable}
                            onClick={() => setRejectOpen(true)}
                        >
                            Mark not suitable
                        </Button>
                        <Button
                            variant="secondary"
                            disabled={!selected.can_shortlist}
                            onClick={() => setConfirm('shortlist')}
                        >
                            Shortlist candidate
                        </Button>
                        <Button
                            variant="secondary"
                            disabled={!selected.can_submit_to_employer}
                            onClick={() => setConfirm('submit')}
                        >
                            Submit to employer
                        </Button>
                    </div>
                    {selected.submit_blocked_reason ? (
                        <p className="text-sm text-muted">{selected.submit_blocked_reason}</p>
                    ) : null}
                    {selected.job?.id ? (
                        <Link href={`/admin/shortlisted?job_id=${selected.job.id}`} className="inline-block text-sm font-medium text-brand">
                            View shortlist for this job
                        </Link>
                    ) : null}
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
                          : 'Mark this candidate suitable?'
                }
                description={
                    confirm === 'submit'
                        ? selected.submit_blocked_reason ??
                          'This creates an employer submission for this job only. It does not select or place the candidate.'
                        : confirm === 'shortlist'
                          ? 'This shortlists the candidate for this job only. Employer selection is a later step.'
                          : 'This records that the candidate passed initial screening for this job.'
                }
                confirmLabel={confirm === 'submit' ? 'Submit' : confirm === 'shortlist' ? 'Shortlist' : 'Mark suitable'}
                onConfirm={() => {
                    if (confirm === 'shortlist') {
                        router.post(`/admin/applications/${selected.id}/shortlist`, {
                            notes: screeningForm.data.notes,
                        });
                    } else if (confirm === 'submit') {
                        router.post(`/admin/applications/${selected.id}/submit`);
                    } else {
                        submitScreening('suitable');
                    }
                    setConfirm(null);
                }}
                onClose={() => setConfirm(null)}
            />

            <Modal
                open={rejectOpen}
                title="Mark not suitable"
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
                            Mark not suitable
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
