import { useForm } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import Card from '@/Components/atoms/Card';
import Button from '@/Components/atoms/Button';
import BackNavLink from '@/Components/molecules/BackNavLink';
import StatusBadge from '@/Components/Account/StatusBadge';

type Submission = {
    id: number;
    business_id: string;
    name: string;
    status: string;
    status_label: string;
    designation?: string | null;
    resume_url?: string | null;
    allowed_client_statuses: { value: string; label: string }[];
    status_events: { to_status: string; actor_name: string | null; notes: string | null; created_at: string | null }[];
    employer_enquiry_id: number;
};

type Props = {
    submission: Submission;
    enquiry: { detail_href?: string; project_location?: string } | null;
    follow_ups: Record<string, unknown>[];
    messages: { id: number; sender_name: string; body: string; sent_at: string | null; is_mine: boolean }[];
    can_message: boolean;
    rejection_reasons: { id: number; label: string }[];
};

export default function StaffingSubmissionShow({
    submission,
    enquiry,
    follow_ups,
    messages,
    can_message,
    rejection_reasons,
}: Props) {
    const form = useForm({
        status: '',
        client_feedback: '',
        rejection_reason_id: '' as string | number,
        joining_date: '',
        joining_confirmation_notes: '',
    });
    const messageForm = useForm({ body: '' });

    const backHref = enquiry?.detail_href ?? `/account/enquiries/${submission.employer_enquiry_id}`;

    function submitDecision() {
        form.patch(`/account/staffing-submissions/${submission.id}`, { preserveScroll: true });
    }

    return (
        <AccountLayout title={`Staffing candidate · ${submission.name}`} hideShellHeader>
            <div className="mx-auto max-w-3xl space-y-4">
                <Card as="section" padding="compact">
                    <BackNavLink href={backHref} label="Back to staffing requirement" className="mb-3" />
                    <h1 className="text-xl font-semibold text-ink">{submission.name}</h1>
                    {submission.designation ? <p className="text-sm text-muted">{submission.designation}</p> : null}
                    <div className="mt-3 flex flex-wrap items-center gap-2">
                        <StatusBadge status={submission.status_label} />
                        <span className="text-xs text-muted">{submission.business_id}</span>
                    </div>
                    {submission.resume_url ? (
                        <Button surface="account" href={submission.resume_url} variant="secondary" className="mt-4 min-h-11">
                            View resume
                        </Button>
                    ) : null}
                </Card>

                {submission.allowed_client_statuses.length > 0 ? (
                    <Card as="section" padding="compact">
                        <h2 className="text-base font-semibold text-ink">Your decision</h2>
                        <p className="mt-1 text-sm text-muted">
                            Record interview progress, selection, rejection or confirmed joining. OurNear coordinates separately.
                        </p>
                        <div className="mt-4 space-y-3">
                            <label className="block text-sm">
                                <span className="text-muted">Next status</span>
                                <select
                                    className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm"
                                    value={form.data.status}
                                    onChange={(e) => form.setData('status', e.target.value)}
                                >
                                    <option value="">Select…</option>
                                    {submission.allowed_client_statuses.map((opt) => (
                                        <option key={opt.value} value={opt.value}>
                                            {opt.label}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="block text-sm">
                                <span className="text-muted">Feedback (optional)</span>
                                <textarea
                                    className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm"
                                    rows={3}
                                    value={form.data.client_feedback}
                                    onChange={(e) => form.setData('client_feedback', e.target.value)}
                                />
                            </label>
                            {form.data.status === 'client_rejected' ? (
                                <label className="block text-sm">
                                    <span className="text-muted">Rejection reason</span>
                                    <select
                                        className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm"
                                        value={form.data.rejection_reason_id}
                                        onChange={(e) => form.setData('rejection_reason_id', e.target.value)}
                                    >
                                        <option value="">Select…</option>
                                        {rejection_reasons.map((r) => (
                                            <option key={r.id} value={r.id}>
                                                {r.label}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                            ) : null}
                            {form.data.status === 'joined_confirmed' ? (
                                <>
                                    <label className="block text-sm">
                                        <span className="text-muted">Joining date</span>
                                        <input
                                            type="date"
                                            className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm"
                                            value={form.data.joining_date}
                                            onChange={(e) => form.setData('joining_date', e.target.value)}
                                        />
                                    </label>
                                    <label className="block text-sm">
                                        <span className="text-muted">Confirmation notes</span>
                                        <textarea
                                            className="mt-1 w-full rounded-md border border-border px-3 py-2 text-sm"
                                            rows={2}
                                            value={form.data.joining_confirmation_notes}
                                            onChange={(e) => form.setData('joining_confirmation_notes', e.target.value)}
                                        />
                                    </label>
                                </>
                            ) : null}
                            <Button
                                surface="account"
                                type="button"
                                disabled={!form.data.status || form.processing}
                                onClick={submitDecision}
                            >
                                Save decision
                            </Button>
                        </div>
                    </Card>
                ) : null}

                {follow_ups.length > 0 ? (
                    <Card as="section" padding="compact">
                        <h2 className="text-base font-semibold text-ink">Updates from OurNear</h2>
                        <ul className="mt-3 space-y-3 text-sm">
                            {follow_ups.map((row) => (
                                <li key={row.id as number} className="rounded-md border border-border/60 p-3">
                                    <p className="font-medium text-ink">{row.follow_up_type_label as string}</p>
                                    <p className="whitespace-pre-wrap text-ink">{row.body as string}</p>
                                    <p className="text-xs text-muted">
                                        {row.actor_name as string} · {(row.occurred_at as string) ?? ''}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </Card>
                ) : null}

                <Card as="section" padding="compact">
                    <h2 className="text-base font-semibold text-ink">Messages</h2>
                    <ul className="mt-3 space-y-2 text-sm">
                        {messages.length === 0 ? (
                            <li className="text-muted">No messages yet.</li>
                        ) : (
                            messages.map((msg) => (
                                <li key={msg.id} className={msg.is_mine ? 'font-medium text-ink' : 'text-muted'}>
                                    {msg.sender_name}: {msg.body}
                                    {msg.sent_at ? <span className="text-xs text-muted"> · {msg.sent_at}</span> : null}
                                </li>
                            ))
                        )}
                    </ul>
                    {can_message ? (
                        <form
                            className="mt-4 flex flex-col gap-2 sm:flex-row"
                            onSubmit={(e) => {
                                e.preventDefault();
                                messageForm.post(`/account/staffing-submissions/${submission.id}/messages`, {
                                    preserveScroll: true,
                                    onSuccess: () => messageForm.reset('body'),
                                });
                            }}
                        >
                            <textarea
                                className="min-h-[44px] flex-1 rounded-md border border-border px-3 py-2 text-sm"
                                placeholder="Reply to OurNear"
                                rows={2}
                                value={messageForm.data.body}
                                onChange={(e) => messageForm.setData('body', e.target.value)}
                            />
                            <Button type="submit" surface="account" disabled={messageForm.processing || !messageForm.data.body.trim()}>
                                Send
                            </Button>
                        </form>
                    ) : null}
                </Card>

                <Card as="section" padding="compact">
                    <h2 className="text-base font-semibold text-ink">Status history</h2>
                    <ul className="mt-3 space-y-2 text-sm">
                        {submission.status_events.map((event, index) => (
                            <li key={index}>
                                <span className="font-medium text-ink">{event.to_status}</span>
                                {event.actor_name ? <span className="text-muted"> · {event.actor_name}</span> : null}
                                {event.notes ? <p className="text-muted">{event.notes}</p> : null}
                            </li>
                        ))}
                    </ul>
                </Card>
            </div>
        </AccountLayout>
    );
}
