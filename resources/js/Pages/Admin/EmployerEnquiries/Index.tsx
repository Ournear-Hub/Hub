import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import StatCard from '@/Components/Admin/StatCard';
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
    crm_lead_id?: number | null;
    crm_workspace_url?: string | null;
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
    can_create_lead?: boolean;
};

export default function EmployerEnquiriesIndex({
    enquiries,
    selected,
    summary,
    filters,
    options,
    statuses,
    can_create_lead = false,
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

    const closeDrawer = useCallback(() => {
        router.get('/admin/employer-enquiries', { ...filters, enquiry: undefined }, { preserveState: true });
    }, [filters]);

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

            <FilterBar onSubmit={filter}>
                <SearchBar defaultValue={filters.search} placeholder="Search company, contact, email" />
                <Select name="status" defaultValue={filters.status} aria-label="Status">
                    <option value="">All statuses</option>
                    {statuses.map((status) => (
                        <option key={status} value={status}>
                            {status}
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
                <Select name="project_location" defaultValue={filters.project_location} aria-label="Project location">
                    <option value="">All locations</option>
                    {options.locations.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </Select>
                <Select name="workforce_type" defaultValue={filters.workforce_type} aria-label="Workforce type">
                    <option value="">All workforce types</option>
                    {options.workforce_types.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </Select>
                <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="From date" />
                <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="To date" />
                <Button type="submit">Filter</Button>
            </FilterBar>

            <DataTable
                columns={[
                    { key: 'id', header: '#', render: (row) => row.id },
                    {
                        key: 'company',
                        header: 'Company',
                        className: 'font-medium text-ink',
                        render: (row) => row.company_name,
                    },
                    { key: 'contact', header: 'Contact Person', render: (row) => row.contact_person },
                    { key: 'mobile', header: 'Mobile', render: (row) => row.mobile_number },
                    { key: 'email', header: 'Email', render: (row) => row.email },
                    { key: 'industry', header: 'Industry', render: (row) => row.industry },
                    { key: 'location', header: 'Location', render: (row) => row.project_location },
                    { key: 'workforce', header: 'Workforce Type', render: (row) => row.workforce_type },
                    { key: 'people', header: 'People', render: (row) => row.number_of_people },
                    { key: 'start', header: 'Start Date', render: (row) => row.expected_start_date ?? '—' },
                    {
                        key: 'status',
                        header: 'Status',
                        render: (row) => <StatusBadge status={row.status} />,
                    },
                    { key: 'date', header: 'Enquiry Date', render: (row) => row.created_at ?? '—' },
                    {
                        key: 'actions',
                        header: 'Actions',
                        render: (row) => (
                            <button
                                type="button"
                                className="font-medium text-brand hover:text-accent"
                                onClick={(event) => {
                                    event.stopPropagation();
                                    router.get(queryFor(row.id), {}, { preserveState: true });
                                }}
                            >
                                View
                            </button>
                        ),
                    },
                ]}
                rows={enquiries.data}
                rowKey={(row) => row.id}
                emptyTitle="No employer enquiries yet."
                selectedKey={selected?.id ?? null}
            />
            <Pagination links={enquiries.links} />

            <Drawer open={selected !== null} title={selected?.company_name ?? 'Enquiry'} onClose={closeDrawer}>
                {selected && (
                    <>
                        <section>
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
                            <Select
                                className="mt-2"
                                value={selected.status}
                                aria-label="Enquiry status"
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
                            </Select>
                        </section>

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Internal Notes</h3>
                            <ActivityFeed
                                items={selected.internal_notes.map((note, index) => ({
                                    id: `${note.created_at}-${index}`,
                                    title: note.body,
                                    meta: `${note.author_name} · ${note.created_at}`,
                                }))}
                                empty="No notes yet."
                            />
                            <form onSubmit={addNote} className="mt-3 space-y-2">
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

                        <div className="mt-6 flex flex-wrap gap-2">
                            {selected.crm_lead_id ? (
                                <Link href={selected.crm_workspace_url ?? `/admin/crm?lead=${selected.crm_lead_id}`} className="rounded-md border border-line px-3 py-2 text-sm font-medium text-brand">
                                    Open CRM lead
                                </Link>
                            ) : can_create_lead ? (
                                <Button onClick={() => router.post(`/admin/employer-enquiries/${selected.id}/lead`)}>
                                    Create CRM lead
                                </Button>
                            ) : null}
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
                    </>
                )}
            </Drawer>
        </AdminLayout>
    );
}
