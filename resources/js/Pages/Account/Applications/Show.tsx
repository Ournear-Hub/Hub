import { Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';
import Button from '@/Components/atoms/Button';
import AccountLayout from '@/Layouts/AccountLayout';
import ApplicationTimeline from '@/Components/organisms/ApplicationTimeline';
import BackNavLink from '@/Components/molecules/BackNavLink';
import DetailHeader from '@/Components/organisms/DetailHeader';
import type { SharedPageProps } from '@/types/page';

type TimelineEvent = {
    key: string;
    label: string;
    at: string | null;
};

type ApplicationDetail = {
    id: number;
    business_id: string | null;
    applied_at: string | null;
    updated_at: string | null;
    last_updated_at: string | null;
    status: string;
    can_withdraw: boolean;
    withdraw_requires_confirmation: boolean;
    job: {
        title: string;
        slug: string;
        company: string | null;
        location: string | null;
        employment_type: string | null;
        experience: string | null;
        salary: string | null;
        is_closed: boolean;
        status_label: string;
        can_view: boolean;
        href: string | null;
    } | null;
    resume: {
        has_resume: boolean;
        filename: string | null;
        extension: string | null;
        size: number | null;
        download_url: string | null;
    };
    timeline: TimelineEvent[];
};

type RecruitmentInterview = {
    id: number;
    sort_order: number;
    scheduled_at_label: string | null;
    mode_label: string;
    location_or_link: string | null;
    lifecycle_label: string;
    candidate_response_label: string | null;
    can_respond: boolean;
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
    status_label: string;
    response_note: string | null;
    created_at: string | null;
};

type RecruitmentWorkflow = {
    interviews: RecruitmentInterview[];
    respondable_interview_id: number | null;
    messages: RecruitmentMessage[];
    correction_requests: CorrectionRequest[];
    can_submit_correction_request: boolean;
};

type ShowProps = {
    application: ApplicationDetail;
    recruitment: RecruitmentWorkflow;
};

function fileSize(bytes: number | null): string {
    if (!bytes) {
        return '—';
    }

    if (bytes < 1024) {
        return `${bytes} B`;
    }

    return `${Math.round(bytes / 1024)} KB`;
}

export default function ApplicationShow({ application, recruitment }: ShowProps) {
    const page = usePage<SharedPageProps>();
    const flash = page.props.flash;
    const errors = page.props.errors as Record<string, string | undefined> | undefined;
    const respondForm = useForm({ response: 'accepted', note: '' });
    const correctionForm = useForm({ body: '' });
    const messageForm = useForm({ body: '' });

    const submitInterviewResponse = (event: FormEvent) => {
        event.preventDefault();
        const interviewId = recruitment.respondable_interview_id;
        if (!interviewId) {
            return;
        }
        respondForm.post(`/account/applications/${application.id}/interviews/${interviewId}/response`);
    };

    const submitCorrection = (event: FormEvent) => {
        event.preventDefault();
        correctionForm.post(`/account/applications/${application.id}/correction-requests`, {
            onSuccess: () => correctionForm.reset('body'),
        });
    };

    const submitMessage = (event: FormEvent) => {
        event.preventDefault();
        messageForm.post(`/account/applications/${application.id}/messages`, {
            onSuccess: () => messageForm.reset('body'),
        });
    };

    const jobMetaLine = [application.job?.company, application.job?.location, application.job?.employment_type]
        .filter(Boolean)
        .join(' · ');
    const applicationEyebrow = application.business_id ?? `Application #${application.id}`;

    const withdraw = () => {
        const message = application.withdraw_requires_confirmation
            ? 'You have been selected. Are you sure you want to withdraw this application?'
            : 'Withdraw this application?';

        if (!window.confirm(message)) {
            return;
        }

        router.patch(`/account/applications/${application.id}/withdraw`);
    };

    return (
        <AccountLayout
            title="Application"
            description="Your application status and recruitment timeline."
            breadcrumbs={[
                { label: 'Account', href: '/account' },
                { label: 'Applications', href: '/account/applications' },
                { label: application.business_id ?? 'Application' },
            ]}
        >
            {flash.success ? <p className="mb-4 text-sm text-success">{flash.success}</p> : null}
            {errors?.application ? <p className="mb-4 text-sm text-danger">{errors.application}</p> : null}

            <BackNavLink href="/account/applications" label="Back to My Applications" className="mb-4" />

            <div className="space-y-3">
                <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <DetailHeader
                        eyebrow={applicationEyebrow}
                        title="Application"
                        meta={
                            <span className="rounded-full bg-surface px-3 py-1 text-sm font-medium text-ink">
                                {application.status}
                            </span>
                        }
                        actions={
                            application.can_withdraw ? (
                                <button type="button" className="text-sm font-medium text-danger" onClick={withdraw}>
                                    Withdraw application
                                </button>
                            ) : undefined
                        }
                    />
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted">Applied</dt>
                            <dd className="mt-1 text-ink">{application.applied_at ?? '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Last updated</dt>
                            <dd className="mt-1 text-ink">{application.last_updated_at ?? application.updated_at ?? '—'}</dd>
                        </div>
                    </dl>
                </section>

                <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <DetailHeader
                        headingLevel={2}
                        eyebrow="Job"
                        title={application.job?.title ?? 'Role'}
                        description={jobMetaLine || undefined}
                        actions={
                            application.job?.href ? (
                                <Link href={application.job.href} className="text-sm font-medium text-brand">
                                    View job
                                </Link>
                            ) : (
                                <p className="text-sm text-muted">This job listing is closed or no longer public.</p>
                            )
                        }
                    />
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted">Experience</dt>
                            <dd className="mt-1 text-ink">{application.job?.experience || '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Salary</dt>
                            <dd className="mt-1 text-ink">{application.job?.salary || '—'}</dd>
                        </div>
                        <div>
                            <dt className="text-muted">Job status</dt>
                            <dd className="mt-1 text-ink">{application.job?.is_closed ? 'Closed' : application.job?.status_label || '—'}</dd>
                        </div>
                    </dl>
                </section>

                <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <h2 className="text-lg font-semibold text-ink">Resume used</h2>
                    {application.resume.has_resume ? (
                        <>
                            <p className="mt-2 text-sm text-ink">{application.resume.filename}</p>
                            <p className="mt-1 text-sm text-muted">
                                Snapshot from this application
                                {application.resume.extension ? ` · ${application.resume.extension.toUpperCase()}` : ''}
                                {application.resume.size ? ` · ${fileSize(application.resume.size)}` : ''}
                            </p>
                            {application.resume.download_url ? (
                                <a href={application.resume.download_url} className="mt-4 inline-block text-sm font-medium text-accent">
                                    Download snapshot
                                </a>
                            ) : null}
                        </>
                    ) : (
                        <p className="mt-2 text-sm text-muted">No resume snapshot is available.</p>
                    )}
                </section>

                <ApplicationTimeline events={application.timeline} title="Timeline" aria-label="Application timeline" />

                {recruitment.interviews.length > 0 ? (
                    <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
                        <h2 className="text-lg font-semibold text-ink">Interviews</h2>
                        <ul className="mt-3 space-y-3 text-sm">
                            {recruitment.interviews.map((interview) => (
                                <li key={interview.id} className="rounded-lg bg-surface/60 px-3 py-2">
                                    <p className="font-medium text-ink">
                                        Interview #{interview.sort_order} · {interview.scheduled_at_label ?? '—'}
                                    </p>
                                    <p className="text-muted">
                                        {interview.mode_label} · {interview.lifecycle_label}
                                        {interview.location_or_link ? ` · ${interview.location_or_link}` : ''}
                                    </p>
                                    {interview.candidate_response_label ? (
                                        <p className="mt-1 text-ink">Your response: {interview.candidate_response_label}</p>
                                    ) : null}
                                </li>
                            ))}
                        </ul>
                        {recruitment.respondable_interview_id ? (
                            <form onSubmit={submitInterviewResponse} className="mt-4 space-y-3 border-t border-line pt-4">
                                <p className="text-sm text-muted">Respond to the scheduled interview (separate from application status).</p>
                                <label className="block text-sm">
                                    <span className="text-muted">Response</span>
                                    <select
                                        className="mt-1 w-full rounded-lg border border-line px-3 py-2"
                                        value={respondForm.data.response}
                                        onChange={(e) => respondForm.setData('response', e.target.value)}
                                    >
                                        <option value="accepted">Accept</option>
                                        <option value="declined">Decline</option>
                                    </select>
                                </label>
                                <label className="block text-sm">
                                    <span className="text-muted">Note (optional)</span>
                                    <textarea
                                        className="mt-1 w-full rounded-lg border border-line px-3 py-2"
                                        rows={2}
                                        value={respondForm.data.note}
                                        onChange={(e) => respondForm.setData('note', e.target.value)}
                                    />
                                </label>
                                <Button type="submit" disabled={respondForm.processing}>
                                    Submit response
                                </Button>
                            </form>
                        ) : null}
                    </section>
                ) : null}

                <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <h2 className="text-lg font-semibold text-ink">Messages</h2>
                    {recruitment.messages.length > 0 ? (
                        <ul className="mt-3 max-h-64 space-y-2 overflow-y-auto text-sm">
                            {recruitment.messages.map((message) => (
                                <li
                                    key={message.id}
                                    className={`rounded-lg px-3 py-2 ${message.is_mine ? 'bg-brand/10' : 'bg-surface/60'}`}
                                >
                                    <p className="font-medium text-ink">{message.sender_name}</p>
                                    <p className="mt-1 whitespace-pre-wrap text-ink">{message.body}</p>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="mt-2 text-sm text-muted">No messages from the employer yet.</p>
                    )}
                    <form onSubmit={submitMessage} className="mt-4 space-y-2">
                        <textarea
                            className="w-full rounded-lg border border-line px-3 py-2 text-sm"
                            rows={3}
                            placeholder="Message the employer…"
                            value={messageForm.data.body}
                            onChange={(e) => messageForm.setData('body', e.target.value)}
                        />
                        <Button type="submit" disabled={messageForm.processing}>
                            Send message
                        </Button>
                    </form>
                </section>

                <section className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <h2 className="text-lg font-semibold text-ink">Correction requests</h2>
                    <p className="mt-1 text-sm text-muted">
                        Request clarification or correction without editing your application snapshot directly.
                    </p>
                    {recruitment.correction_requests.length > 0 ? (
                        <ul className="mt-3 space-y-2 text-sm">
                            {recruitment.correction_requests.map((item) => (
                                <li key={item.id} className="rounded-lg border border-line p-3">
                                    <p className="font-medium text-ink">{item.status_label}</p>
                                    <p className="mt-1 whitespace-pre-wrap">{item.body}</p>
                                    {item.response_note ? (
                                        <p className="mt-2 text-muted">Response: {item.response_note}</p>
                                    ) : null}
                                </li>
                            ))}
                        </ul>
                    ) : null}
                    {recruitment.can_submit_correction_request ? (
                        <form onSubmit={submitCorrection} className="mt-4 space-y-2">
                            <textarea
                                className="w-full rounded-lg border border-line px-3 py-2 text-sm"
                                rows={3}
                                placeholder="Describe what should be corrected or clarified…"
                                value={correctionForm.data.body}
                                onChange={(e) => correctionForm.setData('body', e.target.value)}
                                required
                            />
                            <Button type="submit" disabled={correctionForm.processing}>
                                Submit request
                            </Button>
                        </form>
                    ) : null}
                </section>
            </div>

        </AccountLayout>
    );
}
