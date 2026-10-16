import { useForm } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import BackNavLink from '@/Components/molecules/BackNavLink';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import DetailHeader from '@/Components/organisms/DetailHeader';
import ApplicantResumeOverview from '@/Components/organisms/ApplicantResumeOverview';
import { formatAppliedAt } from '@/Components/Account/CandidateHome/candidateHomeNextAction';
import type { RecruiterCandidateSummaryData } from '@/Components/Account/Recruiter/RecruiterCandidateSummary';

type TimelineEvent = { key: string; label: string; at: string | null; notes?: string | null };

type FollowUpRow = { id: number; body: string; follow_up_type_label?: string; occurred_at?: string | null };
type MessageRow = { id: number; sender_name: string; body: string; sent_at: string | null; is_mine: boolean };

type Props = {
    company: { name: string; business_id: string | null };
    submission: RecruiterCandidateSummaryData & {
        timeline: TimelineEvent[];
        resume_url?: string | null;
        source_label?: string;
        rejection_reason?: string | null;
    };
    shared_follow_ups: FollowUpRow[];
    messages: MessageRow[];
    rejection_reasons: { id: number; name: string }[];
    list_filters: { q: string; status: string };
};

export default function CoordinatedTalentPoolShow({
    company,
    submission,
    shared_follow_ups,
    messages,
    rejection_reasons,
}: Props) {
    const statusForm = useForm({ action: 'review', notes: '', rejection_reason_id: '' });
    const messageForm = useForm({ body: '' });

    const submitAction = (action: string) => {
        statusForm.setData('action', action);
        statusForm.patch(`/account/coordinated-talent-pool/${submission.id}/status`, { preserveScroll: true });
    };

    return (
        <AccountLayout title="Talent Pool candidate">
            <BackNavLink href="/account/coordinated-talent-pool" label="Back to OurNear submissions" className="mb-4" />
            <DetailHeader
                title={submission.name ?? 'Candidate'}
                description={submission.source_label ?? 'OurNear Talent Pool submission (not a job application).'}
            />

            <Card className="mt-6 p-4">
                <dl className="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt className="text-muted">Submitted</dt>
                        <dd className="font-medium text-ink">{formatAppliedAt(submission.submitted_at ?? null)}</dd>
                    </div>
                    <div>
                        <dt className="text-muted">Submission status</dt>
                        <dd className="font-medium text-ink">{submission.status_label ?? submission.status}</dd>
                    </div>
                    {submission.business_id ? (
                        <div>
                            <dt className="text-muted">Submission ID</dt>
                            <dd className="font-medium text-ink">{submission.business_id}</dd>
                        </div>
                    ) : null}
                </dl>
            </Card>

            <ApplicantResumeOverview
                className="mt-6"
                summary={{
                    designation: submission.designation,
                    experience: submission.experience,
                    qualification: submission.qualification,
                    location: submission.location,
                }}
                emptyMessage="Resume not available for this Talent Pool submission."
                footnote={<p>{submission.source_label ?? 'OurNear coordinated submission (not a job application).'}</p>}
                actions={
                    submission.has_resume && submission.resume_url ? (
                        <>
                            <a
                                href={`${submission.resume_url}?disposition=inline`}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                            >
                                View
                            </a>
                            <a
                                href={submission.resume_url}
                                className="inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                            >
                                Download
                            </a>
                        </>
                    ) : undefined
                }
            />

            <section className="mt-8 space-y-3">
                <h2 className="text-base font-semibold text-ink">Your review progress</h2>
                <div className="flex flex-wrap gap-2">
                    <Button type="button" surface="public" variant="secondary" onClick={() => submitAction('review')}>
                        Mark under review
                    </Button>
                    <Button type="button" surface="public" variant="secondary" onClick={() => submitAction('hold')}>
                        On hold
                    </Button>
                    <Button type="button" surface="public" variant="primary" onClick={() => submitAction('select')}>
                        Selected
                    </Button>
                    <Button type="button" surface="public" variant="outline" onClick={() => submitAction('reject')}>
                        Reject
                    </Button>
                </div>
                <textarea
                    className="w-full rounded-md border border-line p-2 text-sm"
                    placeholder="Notes for your team (visible on this submission history)"
                    value={statusForm.data.notes}
                    onChange={(e) => statusForm.setData('notes', e.target.value)}
                />
                {statusForm.errors.notes ? <p className="text-sm text-danger">{statusForm.errors.notes}</p> : null}
                <select
                    className="mt-2 w-full rounded-md border border-line p-2 text-sm"
                    value={statusForm.data.rejection_reason_id}
                    onChange={(e) => statusForm.setData('rejection_reason_id', e.target.value)}
                >
                    <option value="">Rejection reason (if rejecting)</option>
                    {rejection_reasons.map((reason) => (
                        <option key={reason.id} value={String(reason.id)}>
                            {reason.name}
                        </option>
                    ))}
                </select>
                {submission.rejection_reason ? (
                    <p className="mt-2 text-sm text-muted">Recorded rejection reason: {submission.rejection_reason}</p>
                ) : null}
            </section>

            <section className="mt-8">
                <h2 className="text-base font-semibold text-ink">Messages with OurNear</h2>
                <ul className="mt-2 space-y-2 text-sm">
                    {messages.map((msg) => (
                        <li key={msg.id}>
                            <strong>{msg.sender_name}</strong>: {msg.body}
                        </li>
                    ))}
                </ul>
                <form
                    className="mt-3 flex gap-2"
                    onSubmit={(e) => {
                        e.preventDefault();
                        messageForm.post(`/account/coordinated-talent-pool/${submission.id}/messages`, {
                            preserveScroll: true,
                            onSuccess: () => messageForm.reset('body'),
                        });
                    }}
                >
                    <input
                        className="min-h-11 flex-1 rounded border border-line px-3 text-sm"
                        value={messageForm.data.body}
                        onChange={(e) => messageForm.setData('body', e.target.value)}
                        placeholder="Reply to OurNear…"
                    />
                    <Button type="submit" surface="public" variant="secondary" disabled={messageForm.processing}>
                        Send
                    </Button>
                </form>
            </section>

            {shared_follow_ups.length > 0 ? (
                <section className="mt-8">
                    <h2 className="text-base font-semibold text-ink">Shared coordination notes</h2>
                    <ul className="mt-2 space-y-2 text-sm text-muted">
                        {shared_follow_ups.map((row) => (
                            <li key={row.id}>
                                {row.follow_up_type_label}: {row.body}
                            </li>
                        ))}
                    </ul>
                </section>
            ) : null}

            <section className="mt-8">
                <h2 className="text-base font-semibold text-ink">Status history</h2>
                <ul className="mt-2 space-y-2 text-sm text-muted">
                    {submission.timeline.map((event) => (
                        <li key={event.key}>
                            {event.label}
                            {event.at ? ` · ${event.at}` : ''}
                            {event.notes ? <p className="whitespace-pre-wrap text-ink">{event.notes}</p> : null}
                        </li>
                    ))}
                </ul>
            </section>
        </AccountLayout>
    );
}
