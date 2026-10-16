import { useForm } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import StatusBadge from '@/Components/atoms/StatusBadge';

type ProgressStage = { value: string; label: string };

type AssignmentDetail = {
    id: number;
    candidate_name: string;
    candidate_ournear_id: string | null;
    assignment_type_label: string;
    status: string;
    progress_stage: string;
    progress_stage_label: string;
    requirement_label: string | null;
    admin_notes: string | null;
    recruiter_notes: string | null;
    follow_up_at: string | null;
    interview_at: string | null;
    interview_details: string | null;
    can_download_resume: boolean;
    can_submit_to_employer: boolean;
    allowed_progress_stages: ProgressStage[];
    status_events: {
        progress_stage_label: string | null;
        to_status_label: string;
        notes: string | null;
        actor_name: string | null;
        created_at: string | null;
    }[];
    profile_summary: {
        location: string | null;
        experience: string | null;
        availability: string | null;
    };
};

type Props = {
    assignment: AssignmentDetail;
};

export default function RecruiterTalentPoolShow({ assignment }: Props) {
    const form = useForm({
        progress_stage: assignment.progress_stage,
        notes: assignment.recruiter_notes ?? '',
        follow_up_at: assignment.follow_up_at?.slice(0, 10) ?? '',
        interview_at: assignment.interview_at?.slice(0, 10) ?? '',
        interview_details: assignment.interview_details ?? '',
    });

    const submitForm = useForm({});

    return (
        <AccountLayout title={assignment.candidate_name} hideShellHeader>
            <div className="mx-auto max-w-3xl space-y-4">
                <header className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-ink">{assignment.candidate_name}</h1>
                        {assignment.candidate_ournear_id ? (
                            <p className="text-sm text-muted">{assignment.candidate_ournear_id}</p>
                        ) : null}
                        <p className="mt-1 text-sm text-muted">
                            {assignment.assignment_type_label}
                            {assignment.requirement_label ? ` · ${assignment.requirement_label}` : ''}
                        </p>
                    </div>
                    <StatusBadge tone="neutral">{assignment.progress_stage_label}</StatusBadge>
                </header>

                {assignment.admin_notes ? (
                    <Card padding="standard">
                        <p className="text-xs font-semibold uppercase tracking-wide text-muted">Admin instructions</p>
                        <p className="mt-2 whitespace-pre-wrap text-sm text-ink">{assignment.admin_notes}</p>
                    </Card>
                ) : null}

                <Card padding="standard">
                    <p className="text-xs font-semibold uppercase tracking-wide text-muted">Profile summary</p>
                    <ul className="mt-2 space-y-1 text-sm text-ink">
                        <li>Location: {assignment.profile_summary.location ?? '—'}</li>
                        <li>Experience: {assignment.profile_summary.experience ?? '—'}</li>
                        <li>Availability: {assignment.profile_summary.availability ?? '—'}</li>
                    </ul>
                    {assignment.can_download_resume ? (
                        <div className="mt-4">
                            <Button
                                surface="public"
                                href={`/account/talent-pool/${assignment.id}/resume`}
                                variant="outline"
                            >
                                View / download resume
                            </Button>
                        </div>
                    ) : null}
                </Card>

                {assignment.can_submit_to_employer ? (
                    <Card padding="standard">
                        <p className="text-sm font-semibold text-ink">Submit to employer</p>
                        <p className="mt-1 text-sm text-muted">
                            Uses the linked real application — does not create a duplicate candidate or application.
                        </p>
                        <Button
                            surface="public"
                            type="button"
                            variant="primary"
                            className="mt-3"
                            disabled={submitForm.processing}
                            onClick={() => submitForm.post(`/account/talent-pool/${assignment.id}/submit-to-employer`)}
                        >
                            Submit candidate to employer
                        </Button>
                    </Card>
                ) : null}

                {assignment.allowed_progress_stages.length > 0 ? (
                    <Card padding="standard">
                        <p className="text-sm font-semibold text-ink">Update progress</p>
                        <form
                            className="mt-3 space-y-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.put(`/account/talent-pool/${assignment.id}/status`);
                            }}
                        >
                            <label className="block text-sm font-medium text-ink">
                                Status
                                <select
                                    className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                                    value={form.data.progress_stage}
                                    onChange={(event) => form.setData('progress_stage', event.target.value)}
                                >
                                    {assignment.allowed_progress_stages.map((stage) => (
                                        <option key={stage.value} value={stage.value}>
                                            {stage.label}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="block text-sm font-medium text-ink">
                                Recruiter notes
                                <textarea
                                    className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                                    rows={3}
                                    value={form.data.notes}
                                    onChange={(event) => form.setData('notes', event.target.value)}
                                />
                            </label>
                            <label className="block text-sm font-medium text-ink">
                                Follow-up date
                                <input
                                    type="date"
                                    className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                                    value={form.data.follow_up_at}
                                    onChange={(event) => form.setData('follow_up_at', event.target.value)}
                                />
                            </label>
                            <label className="block text-sm font-medium text-ink">
                                Interview date
                                <input
                                    type="date"
                                    className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                                    value={form.data.interview_at}
                                    onChange={(event) => form.setData('interview_at', event.target.value)}
                                />
                            </label>
                            <label className="block text-sm font-medium text-ink">
                                Interview details
                                <textarea
                                    className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                                    rows={2}
                                    value={form.data.interview_details}
                                    onChange={(event) => form.setData('interview_details', event.target.value)}
                                />
                            </label>
                            <Button surface="public" type="submit" variant="primary" disabled={form.processing}>
                                Save progress
                            </Button>
                        </form>
                    </Card>
                ) : null}

                <Card padding="standard">
                    <p className="text-sm font-semibold text-ink">History</p>
                    <ul className="mt-3 space-y-2 text-sm text-muted">
                        {assignment.status_events.map((event, index) => (
                            <li key={`${event.created_at}-${index}`}>
                                <span className="font-medium text-ink">
                                    {event.progress_stage_label ?? event.to_status_label}
                                </span>
                                {event.actor_name ? ` · ${event.actor_name}` : ''}
                                {event.created_at ? ` · ${event.created_at}` : ''}
                                {event.notes ? (
                                    <p className="mt-0.5 whitespace-pre-wrap text-muted">{event.notes}</p>
                                ) : null}
                            </li>
                        ))}
                    </ul>
                </Card>
            </div>
        </AccountLayout>
    );
}
