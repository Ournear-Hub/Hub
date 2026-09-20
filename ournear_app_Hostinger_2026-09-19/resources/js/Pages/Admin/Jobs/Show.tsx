import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import type { AdminJob } from '@/types/jobs';

type ShowProps = {
    job: AdminJob;
};

export default function JobShow({ job }: ShowProps) {
    return (
        <AdminLayout title={job.title}>
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">{job.title}</h1>
                    <p className="mt-1 text-sm text-muted">{job.project}</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Link
                        href={`/admin/jobs/${job.slug}/edit`}
                        className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white"
                    >
                        Edit
                    </Link>
                    <button
                        type="button"
                        className="rounded-md border border-line px-4 py-2 text-sm"
                        onClick={() => router.patch(`/admin/jobs/${job.slug}/publish`)}
                    >
                        {job.publishing_status === 'published' ? 'Unpublish' : 'Publish'}
                    </button>
                    <button
                        type="button"
                        className="rounded-md border border-line px-4 py-2 text-sm"
                        onClick={() => router.patch(`/admin/jobs/${job.slug}/active`)}
                    >
                        {job.active ? 'Deactivate' : 'Activate'}
                    </button>
                    <button
                        type="button"
                        className="rounded-md border border-line px-4 py-2 text-sm"
                        onClick={() => router.patch(`/admin/jobs/${job.slug}/featured`)}
                    >
                        {job.featured ? 'Unfeature' : 'Feature'}
                    </button>
                </div>
            </div>
            <div className="grid gap-4 lg:grid-cols-3">
                <section className="rounded-xl border border-line bg-white p-6 lg:col-span-2">
                    <h2 className="text-lg font-semibold text-ink">Content</h2>
                    <p className="mt-3 whitespace-pre-line text-sm text-muted">{job.description}</p>
                    {job.responsibilities && (
                        <p className="mt-4 whitespace-pre-line text-sm text-muted">{job.responsibilities}</p>
                    )}
                    {job.requirements && (
                        <p className="mt-4 whitespace-pre-line text-sm text-muted">{job.requirements}</p>
                    )}
                    {job.what_we_offer && (
                        <p className="mt-4 whitespace-pre-line text-sm text-muted">{job.what_we_offer}</p>
                    )}
                </section>
                <aside className="rounded-xl border border-line bg-white p-6 text-sm text-muted">
                    <p>
                        <span className="font-medium text-ink">Industry:</span> {job.industry}
                    </p>
                    <p className="mt-2">
                        <span className="font-medium text-ink">Category:</span> {job.category}
                    </p>
                    <p className="mt-2">
                        <span className="font-medium text-ink">Location:</span> {job.location}
                    </p>
                    <p className="mt-2">
                        <span className="font-medium text-ink">Type:</span> {job.employment_type}
                    </p>
                    <p className="mt-2">
                        <span className="font-medium text-ink">Experience:</span> {job.experience}
                    </p>
                    <p className="mt-2">
                        <span className="font-medium text-ink">Salary:</span> {job.salary ?? '—'}
                    </p>
                    <p className="mt-2">
                        <span className="font-medium text-ink">Vacancies:</span> {job.vacancy}
                    </p>
                    <p className="mt-2">
                        <span className="font-medium text-ink">Posted:</span> {job.posted_date}
                    </p>
                    <p className="mt-2">
                        <span className="font-medium text-ink">Closes:</span> {job.closing_date ?? '—'}
                    </p>
                    <p className="mt-2 capitalize">
                        <span className="font-medium text-ink">Publishing:</span> {job.publishing_status}
                    </p>
                    <p className="mt-2">
                        <span className="font-medium text-ink">Active:</span> {job.active ? 'Yes' : 'No'}
                    </p>
                    <p className="mt-2">
                        <span className="font-medium text-ink">Featured:</span> {job.featured ? 'Yes' : 'No'}
                    </p>
                    <p className="mt-2">
                        <span className="font-medium text-ink">Applications:</span> {job.applications_count}
                    </p>
                    <Link href="/admin/jobs" className="mt-4 inline-block text-brand">
                        Back to jobs
                    </Link>
                </aside>
            </div>
        </AdminLayout>
    );
}
