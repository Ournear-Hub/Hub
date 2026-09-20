import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import StatCard from '@/Components/Admin/StatCard';
import type { Paginator } from '@/types/jobs';

type Note = {
    body: string;
    author_id: number | null;
    author_name: string;
    created_at: string;
};

type EmployerEnquiry = {
    id: number;
    company_name: string;
    contact_person: string;
    mobile_number: string;
    email: string;
    industry: string;
    project_location: string;
    workforce_type: string;
    number_of_people: number;
    required_skills: string;
    expected_start_date: string | null;
    training_required: boolean;
    additional_requirements: string | null;
    status: string;
    internal_notes: Note[];
    created_at: string | null;
    call_url: string;
    whatsapp_url: string;
    email_url: string;
};

type Filters = {
    search: string;
    status: string;
    industry: string;
    project_location: string;
    workforce_type: string;
    date_from: string;
    date_to: string;
    enquiry: string;
};

type Props = {
    enquiries: Paginator<EmployerEnquiry>;
    selected: EmployerEnquiry | null;
    summary: Record<string, number>;
    filters: Filters;
    options: {
        industries: string[];
        locations: string[];
        workforce_types: string[];
    };
    statuses: string[];
};

const inputClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function EmployerEnquiriesIndex({
    enquiries,
    selected,
    summary,
    filters,
    options,
    statuses,
}: Props) {
    const noteForm = useForm({ note: '' });

    const queryFor = (enquiryId: number) => {
        const params = new URLSearchParams();
        Object.entries(filters).forEach(([key, value]) => {
            if (key !== 'enquiry' && value) {
                params.set(key, value);
            }
        });
        params.set('enquiry', String(enquiryId));
        return `/admin/employer-enquiries?${params.toString()}`;
    };

    const closeDrawer = () => {
        router.get('/admin/employer-enquiries', { ...filters, enquiry: undefined }, { preserveState: true });
    };

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get('/admin/employer-enquiries', Object.fromEntries(form.entries()), { preserveState: true });
    };

    const addNote = (event: FormEvent) => {
        event.preventDefault();
        if (!selected) {
            return;
        }
        noteForm.post(`/admin/employer-enquiries/${selected.id}/notes`, {
            onSuccess: () => noteForm.reset(),
        });
    };

    return (
        <AdminLayout title="Employer Enquiries">
            <div className="mb-6">
                <h1 className="text-2xl font-semibold tracking-tight text-ink">Employer Enquiries</h1>
                <p className="mt-1 text-sm text-muted">Workforce and project requests submitted from the public website.</p>
            </div>

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                <StatCard label="Total Enquiries" value={summary.total ?? 0} />
                {statuses.map((status) => (
                    <StatCard key={status} label={status} value={summary[status] ?? 0} />
                ))}
            </div>

            <form onSubmit={filter} className="mb-6 grid gap-3 rounded-xl border border-line bg-white p-4 md:grid-cols-4">
                <input name="search" defaultValue={filters.search} placeholder="Search company, contact, email" className={inputClass} />
                <select name="status" defaultValue={filters.status} className={inputClass}>
                    <option value="">All statuses</option>
                    {statuses.map((status) => (
                        <option key={status} value={status}>
                            {status}
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
                <select name="project_location" defaultValue={filters.project_location} className={inputClass}>
                    <option value="">All locations</option>
                    {options.locations.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
                <select name="workforce_type" defaultValue={filters.workforce_type} className={inputClass}>
                    <option value="">All workforce types</option>
                    {options.workforce_types.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
                <input name="date_from" type="date" defaultValue={filters.date_from} className={inputClass} />
                <input name="date_to" type="date" defaultValue={filters.date_to} className={inputClass} />
                <button type="submit" className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white">
                    Filter
                </button>
            </form>

            <div className="overflow-x-auto rounded-xl border border-line bg-white">
                <table className="min-w-full text-left text-sm">
                    <thead className="border-b border-line bg-surface text-muted">
                        <tr>
                            <th className="px-4 py-3 font-medium">#</th>
                            <th className="px-4 py-3 font-medium">Company</th>
                            <th className="px-4 py-3 font-medium">Contact Person</th>
                            <th className="px-4 py-3 font-medium">Mobile</th>
                            <th className="px-4 py-3 font-medium">Email</th>
                            <th className="px-4 py-3 font-medium">Industry</th>
                            <th className="px-4 py-3 font-medium">Location</th>
                            <th className="px-4 py-3 font-medium">Workforce Type</th>
                            <th className="px-4 py-3 font-medium">People</th>
                            <th className="px-4 py-3 font-medium">Start Date</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                            <th className="px-4 py-3 font-medium">Enquiry Date</th>
                            <th className="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {enquiries.data.length === 0 ? (
                            <tr>
                                <td colSpan={13} className="px-4 py-8 text-muted">
                                    No employer enquiries yet.
                                </td>
                            </tr>
                        ) : (
                            enquiries.data.map((enquiry) => (
                                <tr key={enquiry.id} className="border-b border-line last:border-0">
                                    <td className="px-4 py-3 text-muted">{enquiry.id}</td>
                                    <td className="px-4 py-3 font-medium text-ink">{enquiry.company_name}</td>
                                    <td className="px-4 py-3 text-muted">{enquiry.contact_person}</td>
                                    <td className="px-4 py-3 text-muted">{enquiry.mobile_number}</td>
                                    <td className="px-4 py-3 text-muted">{enquiry.email}</td>
                                    <td className="px-4 py-3 text-muted">{enquiry.industry}</td>
                                    <td className="px-4 py-3 text-muted">{enquiry.project_location}</td>
                                    <td className="px-4 py-3 text-muted">{enquiry.workforce_type}</td>
                                    <td className="px-4 py-3 text-muted">{enquiry.number_of_people}</td>
                                    <td className="px-4 py-3 text-muted">{enquiry.expected_start_date ?? '—'}</td>
                                    <td className="px-4 py-3 text-muted">{enquiry.status}</td>
                                    <td className="px-4 py-3 text-muted">{enquiry.created_at ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <button
                                            type="button"
                                            className="font-medium text-brand hover:text-accent"
                                            onClick={() => router.get(queryFor(enquiry.id), {}, { preserveState: true })}
                                        >
                                            View
                                        </button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={enquiries.links} />

            {selected && (
                <div className="fixed inset-0 z-40 flex justify-end bg-ink/40">
                    <button type="button" className="h-full flex-1" aria-label="Close enquiry detail" onClick={closeDrawer} />
                    <aside className="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-xl">
                        <div className="flex items-start justify-between gap-3">
                            <h2 className="text-xl font-semibold text-ink">{selected.company_name}</h2>
                            <button type="button" className="text-sm text-muted" onClick={closeDrawer}>
                                Close
                            </button>
                        </div>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Company Details</h3>
                            <p className="mt-2 text-sm text-ink">{selected.company_name}</p>
                            <p className="text-sm text-ink">{selected.contact_person}</p>
                            <p className="text-sm text-ink">{selected.mobile_number}</p>
                            <p className="text-sm text-ink">{selected.email}</p>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Project Details</h3>
                            <p className="mt-2 text-sm text-ink">Industry: {selected.industry}</p>
                            <p className="text-sm text-ink">Project location: {selected.project_location}</p>
                            <p className="text-sm text-ink">Expected start: {selected.expected_start_date ?? '—'}</p>
                            <p className="text-sm text-ink">Training required: {selected.training_required ? 'Yes' : 'No'}</p>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Workforce Requirement</h3>
                            <p className="mt-2 text-sm text-ink">{selected.workforce_type}</p>
                            <p className="text-sm text-ink">People: {selected.number_of_people}</p>
                            <p className="mt-2 whitespace-pre-line text-sm text-muted">{selected.required_skills}</p>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Additional Requirements</h3>
                            <p className="mt-2 whitespace-pre-line text-sm text-muted">
                                {selected.additional_requirements || 'None provided.'}
                            </p>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Status</h3>
                            <select
                                className={`${inputClass} mt-2`}
                                value={selected.status}
                                onChange={(event) =>
                                    router.patch(`/admin/employer-enquiries/${selected.id}/status`, {
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
                                {selected.internal_notes.length === 0 ? (
                                    <li className="text-sm text-muted">No notes yet.</li>
                                ) : (
                                    selected.internal_notes.map((note, index) => (
                                        <li key={`${note.created_at}-${index}`} className="rounded-md bg-surface px-3 py-2 text-sm">
                                            <p className="text-ink">{note.body}</p>
                                            <p className="mt-1 text-xs text-muted">
                                                {note.author_name} · {note.created_at}
                                            </p>
                                        </li>
                                    ))
                                )}
                            </ul>
                            <form onSubmit={addNote} className="mt-3 space-y-2">
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

                        <div className="mt-6 flex flex-wrap gap-2">
                            <a href={selected.call_url} className="rounded-md border border-line px-3 py-2 text-sm font-medium text-ink">
                                Call
                            </a>
                            <a
                                href={selected.whatsapp_url}
                                target="_blank"
                                rel="noreferrer"
                                className="rounded-md border border-line px-3 py-2 text-sm font-medium text-ink"
                            >
                                WhatsApp
                            </a>
                            <a href={selected.email_url} className="rounded-md border border-line px-3 py-2 text-sm font-medium text-ink">
                                Email
                            </a>
                        </div>
                    </aside>
                </div>
            )}
        </AdminLayout>
    );
}
