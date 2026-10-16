import { useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import BackNavLink from '@/Components/molecules/BackNavLink';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import DetailHeader from '@/Components/organisms/DetailHeader';

type TimelineRow = {
    key: string;
    kind: string;
    label: string;
    actor_name?: string | null;
    notes?: string | null;
    at?: string | null;
};

type Props = {
    submission: Record<string, unknown> & {
        id: number;
        candidate_name?: string | null;
        company_name?: string | null;
        owner_resolution_label?: string | null;
        internal_notes?: string | null;
        rejection_reason?: string | null;
    };
    status_timeline: TimelineRow[];
    follow_ups: Record<string, unknown>[];
    messages: { id: number; sender_name: string; body: string; sent_at: string | null; is_mine: boolean }[];
    can_manage: boolean;
    can_view_internal: boolean;
    follow_up_type_options: { value: string; label: string }[];
    visibility_options: { value: string; label: string }[];
    resume_url: string;
};

export default function TalentPoolCompanySubmissionShow({
    submission,
    status_timeline,
    follow_ups,
    messages,
    can_manage,
    can_view_internal,
    follow_up_type_options,
    visibility_options,
    resume_url,
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
        <AdminLayout title="Talent Pool submission">
            <BackNavLink href="/admin/talent-pool-workspace" label="Back to workspace" className="mb-4" />
            <DetailHeader
                title={(submission.candidate_name as string) ?? 'Candidate'}
                description={`${submission.company_name ?? 'Company'} · ${submission.owner_resolution_label ?? 'Ownership recorded'}`}
            />

            <Card className="mt-4 p-4 text-sm">
                <dl className="grid gap-2 sm:grid-cols-2">
                    <div>
                        <dt className="text-muted">Responsible recruiter</dt>
                        <dd>{submission.responsible_recruiter_name as string}</dd>
                    </div>
                    <div>
                        <dt className="text-muted">Submitted by</dt>
                        <dd>{submission.submitted_by_name as string}</dd>
                    </div>
                    <div>
                        <dt className="text-muted">Status</dt>
                        <dd>{submission.status_label as string}</dd>
                    </div>
                    {submission.rejection_reason ? (
                        <div>
                            <dt className="text-muted">Rejection reason</dt>
                            <dd>{submission.rejection_reason as string}</dd>
                        </div>
                    ) : null}
                </dl>
                {can_view_internal && submission.internal_notes ? (
                    <p className="mt-3 whitespace-pre-wrap text-muted">Internal notes: {submission.internal_notes as string}</p>
                ) : null}
                <a href={resume_url} className="mt-3 inline-block text-sm font-semibold text-brand hover:underline">
                    Open resume (employer delivery route)
                </a>
            </Card>

            <section className="mt-8">
                <h2 className="text-base font-semibold text-ink">Status history (employer-attributed)</h2>
                <ul className="mt-2 space-y-2 text-sm text-muted">
                    {status_timeline.map((row) => (
                        <li key={row.key}>
                            {row.label}
                            {row.actor_name ? ` · ${row.actor_name}` : ''}
                            {row.at ? ` · ${row.at}` : ''}
                            {row.notes ? <p className="whitespace-pre-wrap text-ink">{row.notes}</p> : null}
                        </li>
                    ))}
                </ul>
            </section>

            <section className="mt-8">
                <h2 className="text-base font-semibold text-ink">Follow-ups & activity</h2>
                <ul className="mt-2 space-y-3 text-sm">
                    {follow_ups.map((row) => (
                        <li key={row.id as number} className="rounded border border-line p-3">
                            <p className="font-medium text-ink">{row.follow_up_type_label as string}</p>
                            <p className="whitespace-pre-wrap">{row.body as string}</p>
                            <p className="text-muted">
                                {(row.visibility_label as string) ?? ''} · {row.actor_name as string} · {row.occurred_at as string}
                            </p>
                        </li>
                    ))}
                </ul>
            </section>

            {can_manage ? (
                <Card className="mt-6 p-4">
                    <h3 className="font-semibold text-ink">Record follow-up</h3>
                    <form
                        className="mt-3 space-y-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            followUpForm.post(`/admin/talent-pool-company-submissions/${submission.id}/follow-ups`, {
                                preserveScroll: true,
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
                            placeholder="Outcome / employer response"
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
                <h2 className="text-base font-semibold text-ink">Employer conversation</h2>
                <ul className="mt-2 space-y-2 text-sm">
                    {messages.map((msg) => (
                        <li key={msg.id} className={msg.is_mine ? 'text-ink' : 'text-muted'}>
                            <strong>{msg.sender_name}</strong>: {msg.body}
                            {msg.sent_at ? <span className="text-muted"> · {msg.sent_at}</span> : null}
                        </li>
                    ))}
                </ul>
                {can_manage ? (
                    <form
                        className="mt-3 flex gap-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            messageForm.post(`/admin/talent-pool-company-submissions/${submission.id}/messages`, {
                                preserveScroll: true,
                                onSuccess: () => messageForm.reset('body'),
                            });
                        }}
                    >
                        <input
                            className="min-h-11 flex-1 rounded border border-line px-3 text-sm"
                            value={messageForm.data.body}
                            onChange={(e) => messageForm.setData('body', e.target.value)}
                            placeholder="Message employer contact…"
                        />
                        <Button type="submit" surface="admin" variant="secondary" disabled={messageForm.processing}>
                            Send
                        </Button>
                    </form>
                ) : null}
            </section>
        </AdminLayout>
    );
}
