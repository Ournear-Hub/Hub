import { useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Card from '@/Components/atoms/Card';
import DetailHeader from '@/Components/organisms/DetailHeader';
import StatusBadge from '@/Components/Account/StatusBadge';
import BackNavLink from '@/Components/molecules/BackNavLink';
import Button from '@/Components/atoms/Button';

type Props = {
    submission: {
        id: number;
        business_id: string;
        status_label: string;
        candidate_name: string | null;
        requirement_label: string | null;
        recruiter_name: string | null;
        internal_notes: string | null;
        client_feedback: string | null;
        joining_date: string | null;
        status_events: {
            to_status: string;
            notes: string | null;
            actor_name: string | null;
            created_at: string | null;
        }[];
    };
    enquiry: Record<string, unknown> | null;
    follow_ups: Record<string, unknown>[];
    messages: { id: number; sender_name: string; body: string; sent_at: string | null; is_mine: boolean }[];
    can_manage: boolean;
    can_message: boolean;
    can_view_internal: boolean;
    follow_up_type_options: { value: string; label: string }[];
    visibility_options: { value: string; label: string }[];
};

export default function AdminStaffingSubmissionShow({
    submission,
    follow_ups,
    messages,
    can_manage,
    can_message,
    can_view_internal,
    follow_up_type_options,
    visibility_options,
}: Props) {
    const followUpForm = useForm({
        follow_up_type: 'phone_call',
        occurred_at: '',
        summary: '',
        outcome: '',
        issue: '',
        next_action: '',
        next_action_due_at: '',
        visibility: 'internal',
    });
    const messageForm = useForm({ body: '' });

    return (
        <AdminLayout title={`Staffing submission · ${submission.business_id}`}>
            <BackNavLink href="/admin/staffing-workspace" label="Back to Staffing workspace" className="mb-4" />
            <DetailHeader title={submission.candidate_name ?? 'Candidate'} description={submission.requirement_label ?? undefined} />
            <div className="mt-4 flex flex-wrap items-center gap-2">
                <StatusBadge status={submission.status_label} />
                <span className="text-sm text-muted">{submission.business_id}</span>
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <Card className="p-4">
                    <h2 className="text-sm font-semibold text-ink">Coordination</h2>
                    <p className="mt-2 text-sm text-muted">OurNear recruiter: {submission.recruiter_name ?? '—'}</p>
                    {can_view_internal && submission.internal_notes ? (
                        <p className="mt-3 text-sm text-ink">
                            <span className="font-medium">Internal notes: </span>
                            {submission.internal_notes}
                        </p>
                    ) : can_view_internal ? (
                        <p className="mt-3 text-sm text-muted">No internal notes.</p>
                    ) : null}
                </Card>
                <Card className="p-4">
                    <h2 className="text-sm font-semibold text-ink">Client-visible</h2>
                    {submission.client_feedback ? (
                        <p className="mt-2 text-sm text-ink">{submission.client_feedback}</p>
                    ) : (
                        <p className="mt-2 text-sm text-muted">No client feedback recorded.</p>
                    )}
                    {submission.joining_date ? (
                        <p className="mt-2 text-sm text-ink">Joining date: {submission.joining_date}</p>
                    ) : null}
                </Card>
            </div>

            <Card className="mt-6 p-4">
                <h2 className="text-sm font-semibold text-ink">Status history</h2>
                <ul className="mt-3 space-y-2 text-sm">
                    {submission.status_events.map((event, index) => (
                        <li key={index} className="border-b border-border/60 pb-2 last:border-0">
                            <span className="font-medium text-ink">{event.to_status}</span>
                            {event.actor_name ? <span className="text-muted"> · {event.actor_name}</span> : null}
                            {event.notes ? <p className="text-muted">{event.notes}</p> : null}
                            {event.created_at ? <p className="text-xs text-muted">{event.created_at}</p> : null}
                        </li>
                    ))}
                </ul>
            </Card>

            <section className="mt-8">
                <h2 className="text-base font-semibold text-ink">Follow-ups & activity</h2>
                <ul className="mt-2 space-y-3 text-sm">
                    {follow_ups.length === 0 ? (
                        <li className="text-muted">No follow-ups recorded yet.</li>
                    ) : (
                        follow_ups.map((row) => (
                            <li key={row.id as number} className="rounded border border-line p-3">
                                <p className="font-medium text-ink">{row.follow_up_type_label as string}</p>
                                <p className="whitespace-pre-wrap">{row.body as string}</p>
                                <p className="text-muted">
                                    {(row.visibility_label as string) ?? ''} · {row.actor_name as string} ·{' '}
                                    {(row.occurred_at as string) ?? ''}
                                </p>
                            </li>
                        ))
                    )}
                </ul>
            </section>

            {can_manage ? (
                <Card className="mt-6 p-4">
                    <h3 className="font-semibold text-ink">Record follow-up</h3>
                    <form
                        className="mt-3 space-y-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            followUpForm.post(`/admin/staffing-candidate-submissions/${submission.id}/follow-ups`, {
                                preserveScroll: true,
                                onSuccess: () => followUpForm.reset('summary', 'outcome', 'issue', 'next_action'),
                            });
                        }}
                    >
                        <select
                            className="w-full rounded border border-line p-2 text-sm"
                            value={followUpForm.data.follow_up_type}
                            onChange={(e) => followUpForm.setData('follow_up_type', e.target.value)}
                        >
                            {follow_up_type_options.map((opt) => (
                                <option key={opt.value} value={opt.value}>
                                    {opt.label}
                                </option>
                            ))}
                        </select>
                        <textarea
                            className="w-full rounded border border-line p-2 text-sm"
                            placeholder="Summary"
                            value={followUpForm.data.summary}
                            onChange={(e) => followUpForm.setData('summary', e.target.value)}
                        />
                        <textarea
                            className="w-full rounded border border-line p-2 text-sm"
                            placeholder="Outcome / client response"
                            value={followUpForm.data.outcome}
                            onChange={(e) => followUpForm.setData('outcome', e.target.value)}
                        />
                        <input
                            type="datetime-local"
                            className="w-full rounded border border-line p-2 text-sm"
                            value={followUpForm.data.next_action_due_at}
                            onChange={(e) => followUpForm.setData('next_action_due_at', e.target.value)}
                        />
                        <select
                            className="w-full rounded border border-line p-2 text-sm"
                            value={followUpForm.data.visibility}
                            onChange={(e) => followUpForm.setData('visibility', e.target.value)}
                        >
                            {visibility_options.map((opt) => (
                                <option key={opt.value} value={opt.value}>
                                    {opt.label}
                                </option>
                            ))}
                        </select>
                        <Button type="submit" surface="admin" variant="primary" disabled={followUpForm.processing}>
                            Save follow-up
                        </Button>
                    </form>
                </Card>
            ) : null}

            <section className="mt-8">
                <h2 className="text-base font-semibold text-ink">Client conversation</h2>
                <ul className="mt-2 space-y-2 text-sm">
                    {messages.length === 0 ? (
                        <li className="text-muted">No messages yet.</li>
                    ) : (
                        messages.map((msg) => (
                            <li key={msg.id} className={msg.is_mine ? 'text-ink' : 'text-muted'}>
                                <strong>{msg.sender_name}</strong>: {msg.body}
                                {msg.sent_at ? <span className="text-muted"> · {msg.sent_at}</span> : null}
                            </li>
                        ))
                    )}
                </ul>
                {can_message ? (
                    <form
                        className="mt-3 flex gap-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            messageForm.post(`/admin/staffing-candidate-submissions/${submission.id}/messages`, {
                                preserveScroll: true,
                                onSuccess: () => messageForm.reset('body'),
                            });
                        }}
                    >
                        <input
                            className="min-w-0 flex-1 rounded border border-line p-2 text-sm"
                            placeholder="Message to client"
                            value={messageForm.data.body}
                            onChange={(e) => messageForm.setData('body', e.target.value)}
                        />
                        <Button type="submit" surface="admin" variant="primary" disabled={messageForm.processing}>
                            Send
                        </Button>
                    </form>
                ) : null}
            </section>
        </AdminLayout>
    );
}
