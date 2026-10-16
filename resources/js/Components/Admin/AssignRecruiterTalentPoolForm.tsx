import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button, Label, Select } from '@/Components/Admin/ui';

type RecruiterOption = { id: number; name: string };

type Props = {
    candidateUserId: number;
    candidateProfileId: number;
    applicationId: number;
    jobId: number;
    defaultRecruiterUserId: number | null;
    recruiters: RecruiterOption[];
    jobLabel: string;
};

export default function AssignRecruiterTalentPoolForm({
    candidateUserId,
    candidateProfileId,
    applicationId,
    jobId,
    defaultRecruiterUserId,
    recruiters,
    jobLabel,
}: Props) {
    const form = useForm({
        candidate_user_id: String(candidateUserId),
        assigned_recruiter_user_id: defaultRecruiterUserId ? String(defaultRecruiterUserId) : '',
        application_id: String(applicationId),
        job_id: String(jobId),
        assignment_type: 'job',
        admin_notes: '',
        return_candidate_profile_id: String(candidateProfileId),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/admin/talent-pool-assignments', {
            preserveScroll: true,
        });
    };

    return (
        <form onSubmit={submit} className="mt-2 rounded-md border border-line bg-white p-3 space-y-2">
            <p className="text-xs text-muted">
                Assign to recruiter Talent Pool for manpower support ({jobLabel}). Does not create a new application.
            </p>
            <div>
                <Label htmlFor={`recruiter-${applicationId}`}>Recruiter</Label>
                <Select
                    id={`recruiter-${applicationId}`}
                    value={form.data.assigned_recruiter_user_id}
                    onChange={(event) => form.setData('assigned_recruiter_user_id', event.target.value)}
                >
                    <option value="">Select recruiter</option>
                    {recruiters.map((recruiter) => (
                        <option key={recruiter.id} value={recruiter.id}>
                            {recruiter.name}
                        </option>
                    ))}
                </Select>
                {form.errors.assigned_recruiter_user_id ? (
                    <p className="mt-1 text-xs text-danger">{form.errors.assigned_recruiter_user_id}</p>
                ) : null}
                {form.errors.job_id ? <p className="mt-1 text-xs text-danger">{form.errors.job_id}</p> : null}
                {'assignment' in form.errors && form.errors.assignment ? (
                    <p className="mt-1 text-xs text-danger">{String(form.errors.assignment)}</p>
                ) : null}
            </div>
            <Button type="submit" disabled={form.processing}>
                Assign to Talent Pool
            </Button>
        </form>
    );
}
