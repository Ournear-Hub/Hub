import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useEffect } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    ActivityFeed,
    Button,
    DataTable,
    Drawer,
    FilterBar,
    Input,
    SearchBar,
    Select,
    StatusBadge,
    Textarea,
} from '@/Components/Admin/ui';
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

    const closeDrawer = useCallback(() => {
        router.get('/admin/applications', { ...filters, application: undefined }, { preserveState: true });
    }, [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get('/admin/applications', Object.fromEntries(form.entries()), { preserveState: true });
    };

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
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Applications</h1>
                    <p className="mt-1 text-sm text-muted">Candidate applications submitted from the public website.</p>
                </div>

                <FilterBar onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Name, email, mobile, job" />
                    <Select name="status" defaultValue={filters.status} aria-label="Status">
                        <option value="">All statuses</option>
                        {statuses.map((status) => (
                            <option key={status} value={status}>
                                {status}
                            </option>
                        ))}
                    </Select>
                    <Select name="job_id" defaultValue={filters.job_id} aria-label="Job">
                        <option value="">All jobs</option>
                        {options.jobs.map((job) => (
                            <option key={job.id} value={job.id}>
                                {job.title}
                            </option>
                        ))}
                    </Select>
                    <Select name="industry" defaultValue={filters.industry} aria-label="Industry">
                        <option value="">All industries</option>
                        {options.industries.map((value) => (
                            <option key={value} value={value}>
                                {value}
                            </option>
                        ))}
                    </Select>
                    <Select name="location" defaultValue={filters.location} aria-label="Location">
                        <option value="">All locations</option>
                        {options.locations.map((value) => (
                            <option key={value} value={value}>
                                {value}
                            </option>
                        ))}
                    </Select>
                    <Select name="experience" defaultValue={filters.experience} aria-label="Experience">
                        <option value="">All experience</option>
                        {options.experiences.map((value) => (
                            <option key={value} value={value}>
                                {value}
                            </option>
                        ))}
                    </Select>
                    <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="Applied from" />
                    <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="Applied to" />
                    <Button type="submit">Filter</Button>
                </FilterBar>

                <DataTable
                    columns={[
                        {
                            key: 'candidate',
                            header: 'Candidate',
                            className: 'font-medium text-ink',
                            render: (row) => row.full_name,
                        },
                        { key: 'mobile', header: 'Mobile', render: (row) => row.mobile },
                        { key: 'email', header: 'Email', render: (row) => row.email },
                        { key: 'location', header: 'Location', render: (row) => row.current_location },
                        { key: 'job', header: 'Job', render: (row) => row.job?.title ?? '—' },
                        { key: 'industry', header: 'Industry', render: (row) => row.job?.industry ?? '—' },
                        { key: 'date', header: 'Date', render: (row) => row.applied_at ?? '—' },
                        {
                            key: 'status',
                            header: 'Status',
                            render: (row) => <StatusBadge status={row.status} />,
                        },
                    ]}
                    rows={applications.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No applications yet."
                    selectedKey={selectedId}
                    onRowClick={(row) => router.get(queryFor(row.id), {}, { preserveState: true })}
                />
                <Pagination links={applications.links} />
            </div>

            <Drawer open={selected !== null} title={selected?.full_name ?? 'Application'} onClose={closeDrawer}>
                {selected && (
                    <>
                        <section>
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Personal details</h3>
                            <p className="mt-2 text-sm text-ink">{selected.email}</p>
                            <p className="text-sm text-ink">{selected.mobile}</p>
                            <p className="text-sm text-ink">{selected.current_location}</p>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Professional details</h3>
                            <p className="mt-2 text-sm text-ink">Experience: {selected.experience}</p>
                            <p className="text-sm text-ink">Qualification: {selected.qualification}</p>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Application details</h3>
                            <p className="mt-2 text-sm text-ink">{selected.job?.title ?? '—'}</p>
                            <p className="text-sm text-ink">Industry: {selected.job?.industry ?? '—'}</p>
                            <p className="text-sm text-ink">Applied: {selected.applied_at ?? '—'}</p>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Additional information</h3>
                            <p className="mt-2 whitespace-pre-line text-sm text-muted">
                                {selected.additional_information || 'None provided.'}
                            </p>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Resume</h3>
                            {selected.has_resume ? (
                                <a href={selected.resume_url} className="mt-2 inline-block text-sm font-medium text-brand">
                                    Download resume
                                </a>
                            ) : (
                                <p className="mt-2 text-sm text-muted">No resume on file.</p>
                            )}
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Application status</h3>
                            <Select
                                className="mt-2"
                                value={selected.status}
                                aria-label="Application status"
                                onChange={(event) =>
                                    router.patch(`/admin/applications/${selected.id}/status`, {
                                        status: event.target.value,
                                    })
                                }
                            >
                                {statuses.map((status) => (
                                    <option key={status} value={status}>
                                        {status}
                                    </option>
                                ))}
                            </Select>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Internal Notes</h3>
                            <ActivityFeed
                                items={(selected.internal_notes ?? []).map((note, index) => ({
                                    id: `${note.created_at}-${index}`,
                                    title: note.body,
                                    meta: `${note.author_name} · ${note.created_at}`,
                                }))}
                                empty="No notes yet."
                            />
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    noteForm.post(`/admin/applications/${selected.id}/notes`, {
                                        onSuccess: () => noteForm.reset(),
                                    });
                                }}
                                className="mt-3 space-y-2"
                            >
                                <Textarea
                                    value={noteForm.data.note}
                                    onChange={(event) => noteForm.setData('note', event.target.value)}
                                    placeholder="Add an internal note"
                                />
                                {noteForm.errors.note && <p className="text-sm text-danger">{noteForm.errors.note}</p>}
                                <Button type="submit" disabled={noteForm.processing}>
                                    Add note
                                </Button>
                            </form>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Interview</h3>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    interviewForm.put(`/admin/applications/${selected.id}/interview`);
                                }}
                                className="mt-2 space-y-2"
                            >
                                <Input
                                    type="datetime-local"
                                    value={interviewForm.data.scheduled_at}
                                    onChange={(event) => interviewForm.setData('scheduled_at', event.target.value)}
                                    required
                                    aria-label="Interview time"
                                />
                                {interviewForm.errors.scheduled_at && (
                                    <p className="text-sm text-danger">{interviewForm.errors.scheduled_at}</p>
                                )}
                                <Select
                                    value={interviewForm.data.mode}
                                    onChange={(event) => interviewForm.setData('mode', event.target.value)}
                                    aria-label="Interview mode"
                                >
                                    {interviewModes.map((mode) => (
                                        <option key={mode} value={mode}>
                                            {mode.replace('_', ' ')}
                                        </option>
                                    ))}
                                </Select>
                                <Input
                                    placeholder="Location or link"
                                    value={interviewForm.data.location_or_link}
                                    onChange={(event) => interviewForm.setData('location_or_link', event.target.value)}
                                />
                                <Input
                                    placeholder="Interviewer name"
                                    value={interviewForm.data.interviewer_name}
                                    onChange={(event) => interviewForm.setData('interviewer_name', event.target.value)}
                                    required
                                />
                                {interviewForm.errors.interviewer_name && (
                                    <p className="text-sm text-danger">{interviewForm.errors.interviewer_name}</p>
                                )}
                                <Select
                                    value={interviewForm.data.outcome}
                                    onChange={(event) => interviewForm.setData('outcome', event.target.value)}
                                    aria-label="Interview outcome"
                                >
                                    {interviewOutcomes.map((outcome) => (
                                        <option key={outcome} value={outcome}>
                                            {outcome.replace('_', ' ')}
                                        </option>
                                    ))}
                                </Select>
                                <Textarea
                                    className="min-h-20"
                                    placeholder="Internal interview notes"
                                    value={interviewForm.data.notes}
                                    onChange={(event) => interviewForm.setData('notes', event.target.value)}
                                />
                                <Button type="submit" disabled={interviewForm.processing}>
                                    Save interview
                                </Button>
                            </form>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Activity</h3>
                            <ActivityFeed
                                items={(selected.activities ?? []).map((activity) => ({
                                    id: activity.id,
                                    title:
                                        activity.type === 'status_changed'
                                            ? `Status: ${activity.from_status ?? '—'} → ${activity.to_status ?? '—'}`
                                            : activity.body || activity.type.replace('_', ' '),
                                    meta: `${activity.actor_name} · ${activity.created_at}`,
                                }))}
                            />
                        </section>
                    </>
                )}
            </Drawer>
        </AdminLayout>
    );
}
