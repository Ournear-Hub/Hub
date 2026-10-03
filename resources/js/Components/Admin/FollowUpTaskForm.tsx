import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button, Input, Label, Select, Textarea } from '@/Components/Admin/ui';
import type { StaffUserOption } from '@/types/applications';

type Props = {
    staffUsers: StaffUserOption[];
    defaults: {
        title?: string;
        application_id?: number | null;
        job_id?: number | null;
        candidate_profile_id?: number | null;
    };
};

export default function FollowUpTaskForm({ staffUsers, defaults }: Props) {
    const form = useForm({
        title: defaults.title ?? '',
        body: '',
        due_at: '',
        assigned_to: '',
        application_id: defaults.application_id ? String(defaults.application_id) : '',
        job_id: defaults.job_id ? String(defaults.job_id) : '',
        candidate_profile_id: defaults.candidate_profile_id ? String(defaults.candidate_profile_id) : '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            assigned_to: data.assigned_to || null,
            due_at: data.due_at || null,
            application_id: data.application_id || null,
            job_id: data.job_id || null,
            candidate_profile_id: data.candidate_profile_id || null,
        }));
        form.post('/admin/tasks', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('body', 'due_at', 'assigned_to');
                form.setData('title', defaults.title ?? '');
            },
        });
    };

    return (
        <section className="space-y-3 rounded-xl border border-line p-4">
            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Create follow-up task</h3>
            <form onSubmit={submit} className="space-y-2">
                <div>
                    <Label htmlFor="follow-up-title">Title</Label>
                    <Input
                        id="follow-up-title"
                        value={form.data.title}
                        onChange={(event) => form.setData('title', event.target.value)}
                        required
                    />
                    {form.errors.title ? <p className="mt-1 text-sm text-danger">{form.errors.title}</p> : null}
                </div>
                <div>
                    <Label htmlFor="follow-up-assignee">Assign to</Label>
                    <Select
                        id="follow-up-assignee"
                        value={form.data.assigned_to}
                        onChange={(event) => form.setData('assigned_to', event.target.value)}
                        aria-label="Assign follow-up to staff"
                    >
                        <option value="">Unassigned</option>
                        {staffUsers.map((user) => (
                            <option key={user.id} value={user.id}>
                                {user.name}
                            </option>
                        ))}
                    </Select>
                    {form.errors.assigned_to ? <p className="mt-1 text-sm text-danger">{form.errors.assigned_to}</p> : null}
                </div>
                <div>
                    <Label htmlFor="follow-up-due">Due</Label>
                    <Input
                        id="follow-up-due"
                        type="datetime-local"
                        value={form.data.due_at}
                        onChange={(event) => form.setData('due_at', event.target.value)}
                    />
                </div>
                <Textarea
                    placeholder="Internal notes"
                    value={form.data.body}
                    onChange={(event) => form.setData('body', event.target.value)}
                />
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? 'Creating…' : 'Create follow-up task'}
                </Button>
                {form.recentlySuccessful ? (
                    <p className="text-sm text-brand">Follow-up task created.</p>
                ) : null}
            </form>
        </section>
    );
}
