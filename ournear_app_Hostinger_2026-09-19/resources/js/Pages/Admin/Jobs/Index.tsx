import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import type { AdminJob, Paginator } from '@/types/jobs';

type Filters = {
    search: string;
    status: string;
    industry: string;
    category: string;
    location: string;
    featured: string;
    active: string;
};

type JobsIndexProps = {
    jobs: Paginator<AdminJob>;
    filters: Filters;
    options: {
        locations: string[];
        industries: string[];
        categories: string[];
    };
};

const inputClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function JobsIndex({ jobs, filters, options }: JobsIndexProps) {
    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get('/admin/jobs', Object.fromEntries(form.entries()), { preserveState: true });
    };

    return (
        <AdminLayout title="Jobs">
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Jobs</h1>
                    <p className="mt-1 text-sm text-muted">Recruitment listings. This is not the Laravel queue table.</p>
                </div>
                <Link
                    href="/admin/jobs/create"
                    className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white"
                >
                    Create job
                </Link>
            </div>

            <form onSubmit={filter} className="mb-6 grid gap-3 rounded-xl border border-line bg-white p-4 md:grid-cols-4">
                <input name="search" defaultValue={filters.search} placeholder="Search title or project" className={inputClass} />
                <select name="status" defaultValue={filters.status} className={inputClass}>
                    <option value="">All publishing</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
                <select name="industry" defaultValue={filters.industry} className={inputClass}>
                    <option value="">All industries</option>
                    {options.industries.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
                <select name="category" defaultValue={filters.category} className={inputClass}>
                    <option value="">All categories</option>
                    {options.categories.map((value) => (
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
                <select name="featured" defaultValue={filters.featured} className={inputClass}>
                    <option value="">Featured: any</option>
                    <option value="1">Featured</option>
                    <option value="0">Not featured</option>
                </select>
                <select name="active" defaultValue={filters.active} className={inputClass}>
                    <option value="">Active: any</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <button type="submit" className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white">
                    Filter
                </button>
            </form>

            <div className="overflow-x-auto rounded-xl border border-line bg-white">
                <table className="min-w-full text-left text-sm">
                    <thead className="border-b border-line bg-surface text-muted">
                        <tr>
                            <th className="px-4 py-3 font-medium">Title</th>
                            <th className="px-4 py-3 font-medium">Project</th>
                            <th className="px-4 py-3 font-medium">Location</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                            <th className="px-4 py-3 font-medium">Flags</th>
                            <th className="px-4 py-3 font-medium">Apps</th>
                            <th className="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {jobs.data.length === 0 ? (
                            <tr>
                                <td colSpan={7} className="px-4 py-8 text-muted">
                                    No jobs match these filters.
                                </td>
                            </tr>
                        ) : (
                            jobs.data.map((job) => (
                                <tr key={job.id} className="border-b border-line last:border-0">
                                    <td className="px-4 py-3 font-medium text-ink">{job.title}</td>
                                    <td className="px-4 py-3 text-muted">{job.project}</td>
                                    <td className="px-4 py-3 text-muted">{job.location}</td>
                                    <td className="px-4 py-3 capitalize text-muted">
                                        {job.publishing_status}
                                        {!job.active ? ' · inactive' : ''}
                                    </td>
                                    <td className="px-4 py-3 text-muted">{job.featured ? 'Featured' : '—'}</td>
                                    <td className="px-4 py-3 text-muted">{job.applications_count}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-wrap gap-2">
                                            <Link href={`/admin/jobs/${job.slug}`} className="text-brand hover:text-accent">
                                                View
                                            </Link>
                                            <Link href={`/admin/jobs/${job.slug}/edit`} className="text-brand hover:text-accent">
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                className="text-brand hover:text-accent"
                                                onClick={() => router.patch(`/admin/jobs/${job.slug}/publish`)}
                                            >
                                                {job.publishing_status === 'published' ? 'Unpublish' : 'Publish'}
                                            </button>
                                            <button
                                                type="button"
                                                className="text-brand hover:text-accent"
                                                onClick={() => router.patch(`/admin/jobs/${job.slug}/active`)}
                                            >
                                                {job.active ? 'Deactivate' : 'Activate'}
                                            </button>
                                            <button
                                                type="button"
                                                className="text-brand hover:text-accent"
                                                onClick={() => router.patch(`/admin/jobs/${job.slug}/featured`)}
                                            >
                                                {job.featured ? 'Unfeature' : 'Feature'}
                                            </button>
                                            <button
                                                type="button"
                                                className="text-danger"
                                                onClick={() => {
                                                    if (confirm('Delete this job?')) {
                                                        router.delete(`/admin/jobs/${job.slug}`);
                                                    }
                                                }}
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
            <Pagination links={jobs.links} />
        </AdminLayout>
    );
}
