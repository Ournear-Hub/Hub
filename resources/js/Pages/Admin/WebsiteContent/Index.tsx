import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import CmsEditor from '@/Pages/Admin/WebsiteContent/CmsEditor';
import ShowcasePanel from '@/Pages/Admin/WebsiteContent/ShowcasePanel';
import type { CmsPage } from '@/types/cms';
import type { Paginator } from '@/types/jobs';
import type { MediaOption } from '@/types/media';

type Note = {
    body: string;
    author_id: number | null;
    author_name: string;
    created_at: string;
};

type ContactEnquiry = {
    id: number;
    full_name: string;
    mobile_number: string;
    email: string | null;
    interested_in: string;
    message: string;
    status: string;
    internal_notes: Note[];
    created_at: string | null;
    call_url: string;
    whatsapp_url: string | null;
    email_url: string | null;
};

type Filters = {
    search: string;
    status: string;
    enquiry: string;
    page: string;
    section: string;
};

type Tab = {
    slug: string;
    title: string;
};

type Props = {
    tabs: Tab[];
    cmsPage: CmsPage;
    contentVersion: string | null;
    activePage: string;
    activeSection: string;
    enquiries: Paginator<ContactEnquiry>;
    selected: ContactEnquiry | null;
    filters: Filters;
    statuses: string[];
    mediaOptions: MediaOption[];
    can_create_lead?: boolean;
    showcaseStatistics?: {
        registered_companies: number;
        talent_pool_companies: number;
        staffing_companies: number;
        registered_users: number;
    } | null;
    showcaseCompanies?: {
        id: number;
        name: string;
        is_active: boolean;
        sort_order: number;
        show_on_home: boolean;
        show_on_talent_pool: boolean;
        show_on_staffing: boolean;
        media_asset_id: number | null;
        logo_url: string | null;
        logo_filename: string | null;
        employer_profile_id?: number | null;
    }[];
    showcaseEmployerOptions?: { id: number; name: string }[];
    showcaseSurface?: 'home' | 'talent_pool' | 'staffing' | null;
    showcaseTablesReady?: boolean;
};

const inputClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function WebsiteContentIndex({
    tabs,
    cmsPage,
    contentVersion,
    activePage,
    activeSection,
    enquiries,
    selected,
    filters,
    statuses,
    mediaOptions = [],
    can_create_lead = false,
    showcaseStatistics = null,
    showcaseCompanies = [],
    showcaseEmployerOptions = [],
    showcaseSurface = null,
    showcaseTablesReady = true,
}: Props) {
    const isShowcasePage = activePage.startsWith('showcase-');
    const noteForm = useForm({ note: '' });

    const openTab = (slug: string) => {
        router.get(
            '/admin/website-content',
            { page: slug, search: filters.search, status: filters.status },
            { preserveState: false },
        );
    };

    const queryFor = (enquiryId: number) => {
        const params = new URLSearchParams();
        params.set('page', 'contact');
        if (filters.search) {
            params.set('search', filters.search);
        }
        if (filters.status) {
            params.set('status', filters.status);
        }
        params.set('enquiry', String(enquiryId));
        return `/admin/website-content?${params.toString()}`;
    };

    const closeDrawer = () => {
        router.get(
            '/admin/website-content',
            { page: 'contact', search: filters.search, status: filters.status },
            { preserveState: true },
        );
    };

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get(
            '/admin/website-content',
            { ...Object.fromEntries(form.entries()), page: 'contact' },
            { preserveState: true },
        );
    };

    const addNote = (event: FormEvent) => {
        event.preventDefault();
        if (!selected) {
            return;
        }
        noteForm.post(`/admin/website-content/contact-enquiries/${selected.id}/notes`, {
            onSuccess: () => noteForm.reset(),
        });
    };

    return (
        <AdminLayout title="Website Content">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Website Content</h1>
                    <p className="mt-1 text-sm text-muted">Manage public OurNear pages (Jobs, Hire, and marketing content) from structured content.</p>
                </div>
            </div>

            <div className="mt-6 flex flex-wrap gap-2 border-b border-line pb-3">
                {tabs.map((tab) => (
                    <button
                        key={tab.slug}
                        type="button"
                        onClick={() => openTab(tab.slug)}
                        className={`rounded-md px-4 py-2 text-sm font-semibold ${
                            activePage === tab.slug ? 'bg-brand text-white' : 'bg-white text-ink ring-1 ring-line'
                        }`}
                    >
                        {tab.title}
                    </button>
                ))}
            </div>

            <div className="mt-6">
                {isShowcasePage ? (
                    <ShowcasePanel
                        mode={activePage === 'showcase-statistics' ? 'statistics' : 'companies'}
                        statistics={showcaseStatistics}
                        companies={showcaseCompanies}
                        employerOptions={showcaseEmployerOptions}
                        showcaseSurface={showcaseSurface}
                        mediaOptions={mediaOptions}
                        activePage={activePage}
                        tablesReady={showcaseTablesReady}
                    />
                ) : (
                    <CmsEditor
                        key={`${cmsPage.slug}-${contentVersion ?? ''}`}
                        cmsPage={cmsPage}
                        activeSection={activeSection}
                        mediaOptions={mediaOptions}
                    />
                )}
            </div>

            {activePage === 'contact' ? (
                <section className="mt-10">
                    <h2 className="text-xl font-semibold text-ink">Contact Us enquiries</h2>
                    <p className="mt-1 text-sm text-muted">
                        Public Contact Us messages. This is not a separate sidebar item.
                    </p>

                    <form onSubmit={filter} className="mt-4 grid gap-3 rounded-xl border border-line bg-white p-4 md:grid-cols-3">
                        <input name="search" defaultValue={filters.search} placeholder="Search name, email, message" className={inputClass} />
                        <select name="status" defaultValue={filters.status} className={inputClass}>
                            <option value="">All statuses</option>
                            {statuses.map((status) => (
                                <option key={status} value={status}>
                                    {status}
                                </option>
                            ))}
                        </select>
                        <button type="submit" className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white">
                            Filter
                        </button>
                    </form>

                    <div className="mt-4 overflow-x-auto rounded-xl border border-line bg-white">
                        <table className="min-w-full text-left text-sm">
                            <thead className="border-b border-line bg-surface text-muted">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Name</th>
                                    <th className="px-4 py-3 font-medium">Mobile</th>
                                    <th className="px-4 py-3 font-medium">Email</th>
                                    <th className="px-4 py-3 font-medium">Interested in</th>
                                    <th className="px-4 py-3 font-medium">Status</th>
                                    <th className="px-4 py-3 font-medium">Date</th>
                                    <th className="px-4 py-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {enquiries.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-8 text-muted">
                                            No contact enquiries yet.
                                        </td>
                                    </tr>
                                ) : (
                                    enquiries.data.map((enquiry) => (
                                        <tr key={enquiry.id} className="border-b border-line last:border-0">
                                            <td className="px-4 py-3 font-medium text-ink">{enquiry.full_name}</td>
                                            <td className="px-4 py-3 text-muted">{enquiry.mobile_number}</td>
                                            <td className="px-4 py-3 text-muted">{enquiry.email ?? '—'}</td>
                                            <td className="px-4 py-3 text-muted">{enquiry.interested_in}</td>
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
                </section>
            ) : null}

            {selected && (
                <div className="fixed inset-0 z-40 flex justify-end bg-ink/40">
                    <button type="button" className="h-full flex-1" aria-label="Close contact enquiry" onClick={closeDrawer} />
                    <aside className="h-full w-full max-w-lg overflow-y-auto bg-white p-6 shadow-xl">
                        <div className="flex items-start justify-between gap-3">
                            <h2 className="text-xl font-semibold text-ink">{selected.full_name}</h2>
                            <button type="button" className="text-sm text-muted" onClick={closeDrawer}>
                                Close
                            </button>
                        </div>
                        <p className="mt-2 text-sm text-ink">{selected.mobile_number}</p>
                        <p className="text-sm text-ink">{selected.email ?? 'No email provided'}</p>
                        <p className="mt-2 text-sm text-muted">Interested in: {selected.interested_in}</p>
                        <p className="mt-4 whitespace-pre-line text-sm text-muted">{selected.message}</p>
                        {can_create_lead ? (
                            <button
                                type="button"
                                className="mt-4 rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white"
                                onClick={() => router.post(`/admin/website-content/contact-enquiries/${selected.id}/lead`)}
                            >
                                Create CRM lead
                            </button>
                        ) : null}

                        <section className="mt-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Status</h3>
                            <select
                                className={`${inputClass} mt-2`}
                                value={selected.status}
                                onChange={(event) =>
                                    router.patch(`/admin/website-content/contact-enquiries/${selected.id}/status`, {
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
                                <button
                                    type="submit"
                                    disabled={noteForm.processing}
                                    className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                                >
                                    Add note
                                </button>
                            </form>
                        </section>
                    </aside>
                </div>
            )}
        </AdminLayout>
    );
}
