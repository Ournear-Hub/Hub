import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useMemo } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import type { AdminApplication, AdminApplicationListItem, Paginator } from '@/types/jobs';

type Filters = {
    search: string;
    status: string;
    job_id: string;
    industry: string;
    location: string;
    experience: string;
    date_from: string;
    date_to: string;
    application: string;
};

type Props = {
    applications: Paginator<AdminApplicationListItem>;
    selected: AdminApplication | null;
    filters: Filters;
    options: {
        jobs: Array<{ id: number; title: string; slug: string }>;
        industries: string[];
        locations: string[];
        experiences: string[];
    };
    statuses: string[];
    interviewModes: string[];
    interviewOutcomes: string[];
};

const inputClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function ApplicationsIndex({
    applications,
    selected,
    filters,
    options,
    statuses,
    interviewModes,
    interviewOutcomes,
}: Props) {
    const selectedId = selected?.id ?? null;
    const noteForm = useForm({ note: '' });
    const interviewForm = useForm({
        scheduled_at: selected?.interview?.scheduled_at ?? '',
        mode: selected?.interview?.mode ?? 'phone',
        location_or_link: selected?.interview?.location_or_link ?? '',
        interviewer_name: selected?.interview?.interviewer_name ?? '',
        outcome: selected?.interview?.outcome ?? 'pending',
        notes: selected?.interview?.notes ?? '',
    });

    const queryFor = (applicationId: number) => {
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([key, value]) => {
            if (key !== 'application' && value) {
                params.set(key, value);
            }
        });
        params.set('application', String(applicationId));
        return `/admin/applications?${params.toString()}`;
    };

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get('/admin/applications', Object.fromEntries(form.entries()), { preserveState: true });
    };

    const drawer = useMemo(() => selected, [selected]);

    useEffect(() => {
        interviewForm.setData({
            scheduled_at: selected?.interview?.scheduled_at ?? '',
            mode: selected?.interview?.mode ?? 'phone',
            location_or_link: selected?.interview?.location_or_link ?? '',
            interviewer_name: selected?.interview?.interviewer_name ?? '',
            outcome: selected?.interview?.outcome ?? 'pending',
            notes: selected?.interview?.notes ?? '',
        });
    }, [selected?.id, selected?.interview?.id]);

    return (
        <AdminLayout title="Applications">
            <div
                className={drawer ? 'pointer-events-none' : undefined}
                inert={drawer ? true : undefined}
                aria-hidden={drawer ? true : undefined}
            >
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight text-ink">Applications</h1>
                <p className="mt-1 text-sm text-muted">Candidate applications submitted from the public website.</p>
            </div>

            <form onSubmit={filter} className="mb-6 grid gap-3 rounded-xl border border-line bg-white p-4 md:grid-cols-4">
                <input name="search" defaultValue={filters.search} placeholder="Name, email, mobile, job" className={inputClass} />
                <select name="status" defaultValue={filters.status} className={inputClass}>
                    <option value="">All statuses</option>
                    {statuses.map((status) => (
                        <option key={status} value={status}>
                            {status}
                        </option>
                    ))}
                </select>
                <select name="job_id" defaultValue={filters.job_id} className={inputClass}>
                    <option value="">All jobs</option>
                    {options.jobs.map((job) => (
                        <option key={job.id} value={job.id}>
                            {job.title}
                        </option>
                    ))}
                </select>
                <select name="industry" defaultValue={filters.industry} className={inputClass}>
                    <option value="">All industries</option>
                    {options.industries.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
                <select name="location" defaultValue={filters.location} className={inputClass}>
                    <option value="">All locations</option>
                    {options.locations.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
                <select name="experience" defaultValue={filters.experience} className={inputClass}>
                    <option value="">All experience</option>
                    {options.experiences.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
                <input name="date_from" type="date" defaultValue={filters.date_from} className={inputClass} aria-label="Applied from" />
                <input name="date_to" type="date" defaultValue={filters.date_to} className={inputClass} aria-label="Applied to" />
                <button type="submit" className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white">
                    Filter
                </button>
            </form>

            <div className="overflow-x-auto rounded-xl border border-line bg-white">
                <table className="min-w-full text-left text-sm">
                    <thead className="border-b border-line bg-surface text-muted">
                        <tr>
                            <th className="px-4 py-3 font-medium">Candidate</th>
                            <th className="px-4 py-3 font-medium">Mobile</th>
                            <th className="px-4 py-3 font-medium">Email</th>
                            <th className="px-4 py-3 font-medium">Location</th>
                            <th className="px-4 py-3 font-medium">Job</th>
                            <th className="px-4 py-3 font-medium">Industry</th>
                            <th className="px-4 py-3 font-medium">Date</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {applications.data.length === 0 ? (
                            <tr>
                                <td colSpan={8} className="px-4 py-8 text-muted">
                                    No applications yet.
                                </td>
                            </tr>
                        ) : (
                            applications.data.map((application) => (
                                <tr
                                    key={application.id}
                                    className={`cursor-pointer border-b border-line last:border-0 ${
                                        selectedId === application.id ? 'bg-surface' : 'hover:bg-surface/60'
                                    }`}
                                    onClick={() => router.get(queryFor(application.id), {}, { preserveState: true })}
                                >
                                    <td className="px-4 py-3 font-medium text-ink">{application.full_name}</td>
                                    <td className="px-4 py-3 text-muted">{application.mobile}</td>
                                    <td className="px-4 py-3 text-muted">{application.email}</td>
                                    <td className="px-4 py-3 text-muted">{application.current_location}</td>
                                    <td className="px-4 py-3 text-muted">{application.job?.title ?? '—'}</td>
                                    <td className="px-4 py-3 text-muted">{application.job?.industry ?? '—'}</td>
                                    <td className="px-4 py-3 text-muted">{application.applied_at ?? '—'}</td>
                                    <td className="px-4 py-3 text-muted">{application.status}</td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={applications.links} />
            </div>

            {drawer && (
                <div className="fixed inset-0 z-[60] flex justify-end">
                    <button
                        type="button"
                        className="absolute inset-0 bg-ink/40"
                        aria-label="Close application detail"
                        onClick={() => router.get('/admin/applications', { ...filters, application: undefined })}
                    />
                    <aside className="relative z-10 h-full w-full max-w-lg min-w-0 overflow-y-auto overflow-x-hidden bg-white p-6 shadow-xl">
                        <div className="flex items-start justify-between gap-3">
                            <h2 className="text-xl font-semibold text-ink">{drawer.full_name}</h2>
                            <button
                                type="button"
                                className="text-sm text-muted"
                                onClick={() => router.get('/admin/applications', { ...filters, application: undefined })}
                            >
                                Close
                            </button>
                        </div>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Personal details</h3>
                            <p className="mt-2 text-sm text-ink">{drawer.email}</p>
                            <p className="text-sm text-ink">{drawer.mobile}</p>
                            <p className="text-sm text-ink">{drawer.current_location}</p>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Professional details</h3>
                            <p className="mt-2 text-sm text-ink">Experience: {drawer.experience}</p>
                            <p className="text-sm text-ink">Qualification: {drawer.qualification}</p>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Application details</h3>
                            <p className="mt-2 text-sm text-ink">{drawer.job?.title ?? '—'}</p>
                            <p className="text-sm text-ink">Industry: {drawer.job?.industry ?? '—'}</p>
                            <p className="text-sm text-ink">Applied: {drawer.applied_at ?? '—'}</p>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Additional information</h3>
                            <p className="mt-2 whitespace-pre-line text-sm text-muted">
                                {drawer.additional_information || 'None provided.'}
                            </p>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Resume</h3>
                            {drawer.has_resume ? (
                                <a
                                    href={drawer.resume_url}
                                    className="mt-2 inline-block text-sm font-medium text-brand"
                                >
                                    Download resume
                                </a>
                            ) : (
                                <p className="mt-2 text-sm text-muted">No resume on file.</p>
                            )}
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Application status</h3>
                            <select
                                className={`${inputClass} mt-2`}
                                value={drawer.status}
                                onChange={(event) =>
                                    router.patch(`/admin/applications/${drawer.id}/status`, {
                                        status: event.target.value,
                                    })
                                }
                            >
                                {statuses.map((status) => (
                                    <option key={status} value={status}>
                                        {status}
                                    </option>
                                ))}
                            </select>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Internal Notes</h3>
                            <ul className="mt-2 space-y-2">
                                {(drawer.internal_notes ?? []).length === 0 ? (
                                    <li className="text-sm text-muted">No notes yet.</li>
                                ) : (
                                    drawer.internal_notes.map((note, index) => (
                                        <li key={`${note.created_at}-${index}`} className="rounded-md bg-surface px-3 py-2 text-sm">
                                            <p className="text-ink">{note.body}</p>
                                            <p className="mt-1 text-xs text-muted">
                                                {note.author_name} · {note.created_at}
                                            </p>
                                        </li>
                                    ))
                                )}
                            </ul>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    noteForm.post(`/admin/applications/${drawer.id}/notes`, {
                                        onSuccess: () => noteForm.reset(),
                                    });
                                }}
                                className="mt-3 space-y-2"
                            >
                                <textarea
                                    className={`${inputClass} min-h-24`}
                                    value={noteForm.data.note}
                                    onChange={(event) => noteForm.setData('note', event.target.value)}
                                    placeholder="Add an internal note"
                                />
                                {noteForm.errors.note && <p className="text-sm text-danger">{noteForm.errors.note}</p>}
                                <button
                                    type="submit"
                                    disabled={noteForm.processing}
                                    className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                                >
                                    Add note
                                </button>
                            </form>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Interview</h3>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    interviewForm.put(`/admin/applications/${drawer.id}/interview`);
                                }}
                                className="mt-2 space-y-2"
                            >
                                <input
                                    type="datetime-local"
                                    className={inputClass}
                                    value={interviewForm.data.scheduled_at}
                                    onChange={(event) => interviewForm.setData('scheduled_at', event.target.value)}
                                    required
                                />
                                {interviewForm.errors.scheduled_at && (
                                    <p className="text-sm text-danger">{interviewForm.errors.scheduled_at}</p>
                                )}
                                <select
                                    className={inputClass}
                                    value={interviewForm.data.mode}
                                    onChange={(event) => interviewForm.setData('mode', event.target.value)}
                                >
                                    {interviewModes.map((mode) => (
                                        <option key={mode} value={mode}>
                                            {mode.replace('_', ' ')}
                                        </option>
                                    ))}
                                </select>
                                <input
                                    className={inputClass}
                                    placeholder="Location or link"
                                    value={interviewForm.data.location_or_link}
                                    onChange={(event) => interviewForm.setData('location_or_link', event.target.value)}
                                />
                                <input
                                    className={inputClass}
                                    placeholder="Interviewer name"
                                    value={interviewForm.data.interviewer_name}
                                    onChange={(event) => interviewForm.setData('interviewer_name', event.target.value)}
                                    required
                                />
                                {interviewForm.errors.interviewer_name && (
                                    <p className="text-sm text-danger">{interviewForm.errors.interviewer_name}</p>
                                )}
                                <select
                                    className={inputClass}
                                    value={interviewForm.data.outcome}
                                    onChange={(event) => interviewForm.setData('outcome', event.target.value)}
                                >
                                    {interviewOutcomes.map((outcome) => (
                                        <option key={outcome} value={outcome}>
                                            {outcome.replace('_', ' ')}
                                        </option>
                                    ))}
                                </select>
                                <textarea
                                    className={`${inputClass} min-h-20`}
                                    placeholder="Internal interview notes"
                                    value={interviewForm.data.notes}
                                    onChange={(event) => interviewForm.setData('notes', event.target.value)}
                                />
                                <button
                                    type="submit"
                                    disabled={interviewForm.processing}
                                    className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                                >
                                    Save interview
                                </button>
                            </form>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Activity</h3>
                            <ul className="mt-2 space-y-2">
                                {(drawer.activities ?? []).length === 0 ? (
                                    <li className="text-sm text-muted">No activity yet.</li>
                                ) : (
                                    (drawer.activities ?? []).map((activity) => (
                                        <li key={activity.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                            <p className="text-ink">
                                                {activity.type === 'status_changed'
                                                    ? `Status: ${activity.from_status ?? '—'} → ${activity.to_status ?? '—'}`
                                                    : activity.body || activity.type.replace('_', ' ')}
                                            </p>
                                            <p className="mt-1 text-xs text-muted">
                                                {activity.actor_name} · {activity.created_at}
                                            </p>
                                        </li>
                                    ))
                                )}
                            </ul>
                        </section>
                    </aside>
                </div>
            )}
        </AdminLayout>
    );
}
