import { Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import type { SharedPageProps } from '@/types/page';
import AccountLayout from '@/Layouts/AccountLayout';
import StatusBadge from '@/Components/Account/StatusBadge';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import ApplicantResumeOverview from '@/Components/organisms/ApplicantResumeOverview';
import ApplicationTimeline from '@/Components/organisms/ApplicationTimeline';
import BackNavLink from '@/Components/molecules/BackNavLink';
import DetailHeader from '@/Components/organisms/DetailHeader';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';
import type { RecruiterCandidateSummaryData } from '@/Components/Account/Recruiter/RecruiterCandidateSummary';

type TimelineEvent = {
    key: string;
    label: string;
    at: string | null;
};

type ApplicationDetail = RecruiterCandidateSummaryData & {
    timeline: TimelineEvent[];
    ats_stage?: string;
    ats_stage_label?: string;
    pipeline_stage_label?: string;
    application_contact?: {
        available: boolean;
        email: string | null;
        mobile: string | null;
        source_label: string;
    };
};

type RecruitmentInterview = {
    id: number;
    sort_order: number;
    scheduled_at_label: string | null;
    mode_label: string;
    lifecycle_label: string;
    candidate_response_label: string | null;
};

type RecruitmentMessage = {
    id: number;
    sender_name: string;
    body: string;
    sent_at: string | null;
    is_mine: boolean;
};

type CorrectionRequest = {
    id: number;
    body: string;
    status: string;
    status_label: string;
    response_note: string | null;
};

type RecruitmentWorkflow = {
    can_manage: boolean;
    can_shortlist: boolean;
    can_reject: boolean;
    can_schedule_interview: boolean;
    can_select: boolean;
    can_open_placement: boolean;
    can_offer: boolean;
    can_accept_offer: boolean;
    can_confirm_joining: boolean;
    can_record_joining: boolean;
    placement_status_label?: string | null;
    rejection_reasons: { id: number; name: string }[];
    interview_modes: { value: string; label: string }[];
    interviews: RecruitmentInterview[];
    messages: RecruitmentMessage[];
    correction_requests: CorrectionRequest[];
};

type Props = {
    job: {
        title: string;
        business_id: string | null;
        slug: string;
        location?: string | null;
        status_label?: string | null;
        company?: string | null;
    };
    application: ApplicationDetail;
    list_filters: {
        q: string;
        status: string;
        stage: string;
    };
    recruitment: RecruitmentWorkflow;
};

function listHref(job: Props['job'], filters: Props['list_filters']): string {
    const params = new URLSearchParams();
    if (filters.q) {
        params.set('q', filters.q);
    }
    if (filters.status) {
        params.set('status', filters.status);
    }
    if (filters.stage) {
        params.set('stage', filters.stage);
    }
    const query = params.toString();
    return `/account/jobs/${job.slug}/applicants${query ? `?${query}` : ''}`;
}

function applicationHref(job: Props['job'], application: ApplicationDetail, filters: Props['list_filters']): string {
    const params = new URLSearchParams();
    if (filters.q) {
        params.set('q', filters.q);
    }
    if (filters.status) {
        params.set('status', filters.status);
    }
    if (filters.stage) {
        params.set('stage', filters.stage);
    }
    const query = params.toString();
    const base = `/account/jobs/${job.slug}/applications/${application.id}`;
    return query ? `${base}?${query}` : base;
}

function profileQuery(
    job: Props['job'],
    application: ApplicationDetail,
    listHrefValue: string,
    filters: Props['list_filters'],
): string {
    const params = new URLSearchParams();
    params.set('back', listHrefValue);
    params.set('job_title', job.title);
    params.set('application_status', application.status_label ?? application.status);
    if (application.applied_at) {
        params.set('applied_at', application.applied_at);
    }
    params.set('application_href', applicationHref(job, application, filters));
    return params.toString();
}

function applicationBase(job: Props['job'], application: ApplicationDetail): string {
    return `/account/jobs/${job.slug}/applications/${application.id}`;
}

export default function EmployerApplicationShow({ job, application, list_filters, recruitment }: Props) {
    const page = usePage<SharedPageProps>();
    const flash = page.props.flash;
    const errors = page.props.errors ?? {};
    const [showReject, setShowReject] = useState(false);
    const [showInterview, setShowInterview] = useState(false);

    const rejectForm = useForm({
        rejection_reason_id: '',
        notes: '',
    });
    const interviewForm = useForm({
        scheduled_at: '',
        mode: 'video',
        location_or_link: '',
        interviewer_name: '',
        notes: '',
    });
    const messageForm = useForm({ body: '' });
    const offerForm = useForm({
        offered_at: new Date().toISOString().slice(0, 10),
        salary: '',
        notes: '',
    });
    const joiningForm = useForm({
        expected_joining_date: '',
        actual_joining_date: new Date().toISOString().slice(0, 10),
        notes: '',
    });

    const submitShortlist = () => {
        if (!window.confirm('Shortlist this candidate for this job?')) {
            return;
        }
        router.post(`${applicationBase(job, application)}/shortlist`);
    };

    const submitReject = (event: FormEvent) => {
        event.preventDefault();
        rejectForm.post(`${applicationBase(job, application)}/reject`, {
            onSuccess: () => setShowReject(false),
        });
    };

    const submitInterview = (event: FormEvent) => {
        event.preventDefault();
        interviewForm.post(`${applicationBase(job, application)}/interview`, {
            onSuccess: () => setShowInterview(false),
        });
    };

    const submitMessage = (event: FormEvent) => {
        event.preventDefault();
        messageForm.post(`${applicationBase(job, application)}/messages`, {
            onSuccess: () => messageForm.reset('body'),
        });
    };

    const updateCorrection = (correctionId: number, status: string) => {
        router.patch(`${applicationBase(job, application)}/correction-requests/${correctionId}`, { status });
    };
    const applicantsHref = listHref(job, list_filters);
    const statusLabel = application.status_label ?? application.status;
    const profileHref = application.public_profile_url
        ? `${application.public_profile_url}?${profileQuery(job, application, applicantsHref, list_filters)}`
        : null;
    const candidateSummaryLine = [application.location, application.experience, application.qualification]
        .filter(Boolean)
        .join(' · ');
    const candidateDescription = [application.designation, candidateSummaryLine].filter(Boolean).join(' · ') || undefined;
    const jobContextLine = [job.company, job.location, job.business_id].filter(Boolean).join(' · ') || undefined;
    const atsLabel = application.ats_stage_label ?? application.pipeline_stage_label;
    const hasRecruitmentActions =
        recruitment.can_shortlist ||
        recruitment.can_reject ||
        recruitment.can_schedule_interview ||
        recruitment.can_select ||
        recruitment.can_open_placement ||
        recruitment.can_offer ||
        recruitment.can_accept_offer ||
        recruitment.can_confirm_joining ||
        recruitment.can_record_joining;

    return (
        <AccountLayout
            title={`Application · ${application.name}`}
            description={`${job.title} · Direct Hiring / ATS`}
            breadcrumbs={[
                { label: 'Hiring', href: '/account/hiring' },
                { label: 'Jobs', href: '/account/jobs' },
                { label: job.title, href: `/account/jobs/${job.slug}/edit` },
                { label: 'Applicants', href: applicantsHref },
                { label: application.name },
            ]}
        >
            <div className="mx-auto max-w-3xl space-y-4 lg:max-w-none">
                <Card as="section" padding="compact">
                    <BackNavLink href={applicantsHref} label="Back to applicants" className="mb-3" />
                    <DetailHeader
                        eyebrow="Applicant for"
                        title={job.title}
                        description={jobContextLine}
                    />
                </Card>

                <Card as="section" padding="standard">
                    <DetailHeader
                        headingLevel={2}
                        title={application.name}
                        description={candidateDescription}
                        meta={<StatusBadge status={statusLabel} />}
                        actions={
                            profileHref ? (
                                <Button surface="public" href={profileHref} variant="secondary" className="min-h-11 w-full sm:w-auto">
                                    View candidate profile
                                </Button>
                            ) : (
                                <p className="text-sm text-muted">Public profile not shared by this candidate.</p>
                            )
                        }
                    />
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted">Applied</dt>
                            <dd className="font-medium text-ink">{formatAppliedAt(application.applied_at ?? null)}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Application status</dt>
                            <dd className="font-medium text-ink">{statusLabel}</dd>
                        </div>
                        {atsLabel ? (
                            <div>
                                <dt className="text-muted">ATS pipeline stage</dt>
                                <dd className="font-medium text-ink">{atsLabel}</dd>
                            </div>
                        ) : null}
                        {application.business_id ? (
                            <div>
                                <dt className="text-muted">Application ID</dt>
                                <dd className="font-medium text-ink">{application.business_id}</dd>
                            </div>
                        ) : null}
                    </dl>
                    {'application_contact' in application && application.application_contact?.available ? (
                        <div className="mt-4 rounded-lg border border-line bg-surface/50 p-4">
                            <h3 className="text-sm font-semibold text-ink">Contact (application snapshot)</h3>
                            <dl className="mt-2 grid gap-2 text-sm sm:grid-cols-2">
                                {application.application_contact.email ? (
                                    <div>
                                        <dt className="text-muted">Email</dt>
                                        <dd className="font-medium text-ink">
                                            <a href={`mailto:${application.application_contact.email}`} className="text-brand hover:underline">
                                                {application.application_contact.email}
                                            </a>
                                        </dd>
                                    </div>
                                ) : null}
                                {application.application_contact.mobile ? (
                                    <div>
                                        <dt className="text-muted">Mobile</dt>
                                        <dd className="font-medium text-ink">{application.application_contact.mobile}</dd>
                                    </div>
                                ) : null}
                            </dl>
                            <p className="mt-2 text-xs text-muted">{application.application_contact.source_label}</p>
                        </div>
                    ) : (
                        <p className="mt-4 text-sm text-muted">No contact details were captured on this application snapshot.</p>
                    )}
                </Card>

                <ApplicantResumeOverview
                    emptyMessage="Resume not available for this application snapshot."
                    footnote={
                        <p>Application resume snapshots are historical files from when the candidate applied.</p>
                    }
                    actions={
                        application.has_resume && application.resume_url ? (
                            <>
                                <a
                                    href={`${application.resume_url}?disposition=inline`}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                                >
                                    View
                                </a>
                                <a
                                    href={application.resume_url}
                                    className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                                >
                                    Download
                                </a>
                            </>
                        ) : undefined
                    }
                />

                <ApplicationTimeline events={application.timeline} hideWhenEmpty />

                {flash.success ? <p className="text-sm text-success">{flash.success}</p> : null}
                {flash.error ? <p className="text-sm text-danger">{flash.error}</p> : null}
                {Object.keys(errors).length > 0 ? (
                    <p className="text-sm text-danger">Some fields need attention. Review the form and try again.</p>
                ) : null}

                {recruitment.can_manage ? (
                    <Card as="section" padding="compact">
                        <h2 className="text-base font-semibold text-ink">Recruitment actions</h2>
                        <p className="mt-1 text-sm text-muted">
                            Shortlist, reject, schedule interviews, and message the candidate. OurNear admin recruitment
                            uses the same application record.
                        </p>
                        <div className="mt-4 flex flex-wrap gap-2">
                            {recruitment.can_shortlist ? (
                                <Button type="button" variant="secondary" onClick={submitShortlist}>
                                    Shortlist
                                </Button>
                            ) : null}
                            {recruitment.can_reject ? (
                                <Button type="button" variant="secondary" onClick={() => setShowReject((v) => !v)}>
                                    Reject
                                </Button>
                            ) : null}
                            {recruitment.can_schedule_interview ? (
                                <Button type="button" variant="secondary" onClick={() => setShowInterview((v) => !v)}>
                                    Schedule interview
                                </Button>
                            ) : null}
                            {recruitment.can_select ? (
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => router.post(`${applicationBase(job, application)}/select`)}
                                >
                                    Select for offer
                                </Button>
                            ) : null}
                            {recruitment.can_open_placement ? (
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => router.post(`${applicationBase(job, application)}/placement`)}
                                >
                                    Open placement
                                </Button>
                            ) : null}
                            {recruitment.placement_status_label ? (
                                <span className="self-center text-sm text-muted">
                                    Placement: {recruitment.placement_status_label}
                                </span>
                            ) : null}
                        </div>
                        {recruitment.can_offer ? (
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    offerForm.post(`${applicationBase(job, application)}/offer`);
                                }}
                                className="mt-4 space-y-2 border-t border-line pt-4"
                            >
                                <label className="block text-sm">
                                    <span className="text-muted">Offer date</span>
                                    <input
                                        type="date"
                                        className="mt-1 w-full rounded-lg border border-line px-3 py-2"
                                        value={offerForm.data.offered_at}
                                        onChange={(e) => offerForm.setData('offered_at', e.target.value)}
                                        required
                                    />
                                </label>
                                <Button type="submit" disabled={offerForm.processing}>
                                    Record offer
                                </Button>
                            </form>
                        ) : null}
                        {recruitment.can_accept_offer ? (
                            <div className="mt-4 border-t border-line pt-4">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => router.post(`${applicationBase(job, application)}/accept-offer`)}
                                >
                                    Accept offer
                                </Button>
                            </div>
                        ) : null}
                        {recruitment.can_confirm_joining ? (
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    joiningForm.post(`${applicationBase(job, application)}/confirm-joining`);
                                }}
                                className="mt-4 space-y-2 border-t border-line pt-4"
                            >
                                <label className="block text-sm">
                                    <span className="text-muted">Expected joining date</span>
                                    <input
                                        type="date"
                                        className="mt-1 w-full rounded-lg border border-line px-3 py-2"
                                        value={joiningForm.data.expected_joining_date}
                                        onChange={(e) => joiningForm.setData('expected_joining_date', e.target.value)}
                                        required
                                    />
                                </label>
                                <Button type="submit" disabled={joiningForm.processing}>
                                    Confirm joining
                                </Button>
                            </form>
                        ) : null}
                        {recruitment.can_record_joining ? (
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    joiningForm.post(`${applicationBase(job, application)}/join`);
                                }}
                                className="mt-4 space-y-2 border-t border-line pt-4"
                            >
                                <label className="block text-sm">
                                    <span className="text-muted">Actual joining date</span>
                                    <input
                                        type="date"
                                        className="mt-1 w-full rounded-lg border border-line px-3 py-2"
                                        value={joiningForm.data.actual_joining_date}
                                        onChange={(e) => joiningForm.setData('actual_joining_date', e.target.value)}
                                        required
                                    />
                                </label>
                                <Button type="submit" disabled={joiningForm.processing}>
                                    Mark joined
                                </Button>
                            </form>
                        ) : null}
                        {!hasRecruitmentActions ? (
                            <p className="mt-3 text-sm text-muted">
                                No further self-service actions are available at this stage. Status changes may require
                                OurNear admin recruitment steps, or the application may be closed.
                            </p>
                        ) : null}

                        {showReject && recruitment.can_reject ? (
                            <form onSubmit={submitReject} className="mt-4 space-y-3 border-t border-line pt-4">
                                <label className="block text-sm">
                                    <span className="text-muted">Rejection reason</span>
                                    <select
                                        className="mt-1 w-full rounded-lg border border-line px-3 py-2"
                                        value={rejectForm.data.rejection_reason_id}
                                        onChange={(e) => rejectForm.setData('rejection_reason_id', e.target.value)}
                                        required
                                    >
                                        <option value="">Select reason</option>
                                        {recruitment.rejection_reasons.map((reason) => (
                                            <option key={reason.id} value={reason.id}>
                                                {reason.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <label className="block text-sm">
                                    <span className="text-muted">Notes (optional)</span>
                                    <textarea
                                        className="mt-1 w-full rounded-lg border border-line px-3 py-2"
                                        rows={2}
                                        value={rejectForm.data.notes}
                                        onChange={(e) => rejectForm.setData('notes', e.target.value)}
                                    />
                                </label>
                                <Button type="submit" disabled={rejectForm.processing}>
                                    Confirm reject
                                </Button>
                            </form>
                        ) : null}

                        {showInterview && recruitment.can_schedule_interview ? (
                            <form onSubmit={submitInterview} className="mt-4 space-y-3 border-t border-line pt-4">
                                <label className="block text-sm">
                                    <span className="text-muted">Date and time</span>
                                    <input
                                        type="datetime-local"
                                        className="mt-1 w-full rounded-lg border border-line px-3 py-2"
                                        value={interviewForm.data.scheduled_at}
                                        onChange={(e) => interviewForm.setData('scheduled_at', e.target.value)}
                                        required
                                    />
                                </label>
                                <label className="block text-sm">
                                    <span className="text-muted">Mode</span>
                                    <select
                                        className="mt-1 w-full rounded-lg border border-line px-3 py-2"
                                        value={interviewForm.data.mode}
                                        onChange={(e) => interviewForm.setData('mode', e.target.value)}
                                    >
                                        {recruitment.interview_modes.map((mode) => (
                                            <option key={mode.value} value={mode.value}>
                                                {mode.label}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                                <label className="block text-sm">
                                    <span className="text-muted">Location or link</span>
                                    <input
                                        className="mt-1 w-full rounded-lg border border-line px-3 py-2"
                                        value={interviewForm.data.location_or_link}
                                        onChange={(e) => interviewForm.setData('location_or_link', e.target.value)}
                                    />
                                </label>
                                <Button type="submit" disabled={interviewForm.processing}>
                                    Schedule
                                </Button>
                            </form>
                        ) : null}

                        {recruitment.interviews.length > 0 ? (
                            <div className="mt-4 border-t border-line pt-4">
                                <h3 className="text-sm font-semibold text-ink">Interviews</h3>
                                <ul className="mt-2 space-y-2 text-sm">
                                    {recruitment.interviews.map((interview) => (
                                        <li key={interview.id} className="rounded-lg bg-surface/60 px-3 py-2">
                                            #{interview.sort_order} · {interview.scheduled_at_label ?? '—'} ·{' '}
                                            {interview.mode_label} · {interview.lifecycle_label}
                                            {interview.candidate_response_label
                                                ? ` · Candidate: ${interview.candidate_response_label}`
                                                : ''}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ) : null}
                    </Card>
                ) : null}

                {recruitment.can_manage ? (
                    <Card as="section" padding="compact">
                        <h2 className="text-base font-semibold text-ink">Messages</h2>
                        {recruitment.messages.length > 0 ? (
                            <ul className="mt-3 max-h-64 space-y-2 overflow-y-auto text-sm">
                                {recruitment.messages.map((message) => (
                                    <li
                                        key={message.id}
                                        className={`rounded-lg px-3 py-2 ${message.is_mine ? 'bg-brand/10' : 'bg-surface/60'}`}
                                    >
                                        <p className="font-medium text-ink">{message.sender_name}</p>
                                        <p className="mt-1 whitespace-pre-wrap text-ink">{message.body}</p>
                                        {message.sent_at ? (
                                            <p className="mt-1 text-xs text-muted">{message.sent_at}</p>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="mt-2 text-sm text-muted">No messages yet.</p>
                        )}
                        <form onSubmit={submitMessage} className="mt-4 space-y-2">
                            <textarea
                                className="w-full rounded-lg border border-line px-3 py-2 text-sm"
                                rows={3}
                                placeholder="Message the candidate…"
                                value={messageForm.data.body}
                                onChange={(e) => messageForm.setData('body', e.target.value)}
                                required
                            />
                            <Button type="submit" disabled={messageForm.processing}>
                                Send message
                            </Button>
                        </form>
                    </Card>
                ) : null}

                {!recruitment.can_manage ? (
                    <Card as="section" padding="compact">
                        <h2 className="text-base font-semibold text-ink">Recruitment actions</h2>
                        <p className="mt-2 text-sm text-muted">
                            You can review this application and resume snapshot. Pipeline changes for this record are
                            managed through authorized OurNear recruitment workflows.
                        </p>
                    </Card>
                ) : null}

                {recruitment.correction_requests.length > 0 ? (
                    <Card as="section" padding="compact">
                        <h2 className="text-base font-semibold text-ink">Correction requests</h2>
                        <ul className="mt-3 space-y-3 text-sm">
                            {recruitment.correction_requests.map((item) => (
                                <li key={item.id} className="rounded-lg border border-line p-3">
                                    <p className="font-medium text-ink">{item.status_label}</p>
                                    <p className="mt-1 whitespace-pre-wrap text-ink">{item.body}</p>
                                    {item.response_note ? (
                                        <p className="mt-2 text-muted">Response: {item.response_note}</p>
                                    ) : null}
                                    {item.status === 'pending' ? (
                                        <div className="mt-2 flex gap-2">
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                onClick={() => updateCorrection(item.id, 'acknowledged')}
                                            >
                                                Acknowledge
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="secondary"
                                                onClick={() => updateCorrection(item.id, 'resolved')}
                                            >
                                                Resolve
                                            </Button>
                                        </div>
                                    ) : null}
                                </li>
                            ))}
                        </ul>
                    </Card>
                ) : null}
            </div>
        </AccountLayout>
    );
}
