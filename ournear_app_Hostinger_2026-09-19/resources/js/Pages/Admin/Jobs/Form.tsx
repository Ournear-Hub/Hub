import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import type { AdminJob } from '@/types/jobs';

type FormProps = {
    job: AdminJob | null;
};

const inputClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function JobForm({ job }: FormProps) {
    const form = useForm({
        title: job?.title ?? '',
        project: job?.project ?? '',
        industry: job?.industry ?? '',
        category: job?.category ?? '',
        location: job?.location ?? '',
        employment_type: job?.employment_type ?? 'Full-time',
        experience: job?.experience ?? '',
        salary: job?.salary ?? '',
        vacancy: job?.vacancy ?? 1,
        posted_date: job?.posted_date ?? new Date().toISOString().slice(0, 10),
        closing_date: job?.closing_date ?? '',
        featured: job?.featured ?? false,
        active: job?.active ?? true,
        publishing_status: job?.publishing_status ?? 'draft',
        description: job?.description ?? '',
        responsibilities: job?.responsibilities ?? '',
        requirements: job?.requirements ?? '',
        what_we_offer: job?.what_we_offer ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (job) {
            form.put(`/admin/jobs/${job.slug}`);
            return;
        }
        form.post('/admin/jobs');
    };

    const fieldError = (name: keyof typeof form.errors) =>
        form.errors[name] ? <p className="mt-1 text-sm text-danger">{form.errors[name]}</p> : null;

    return (
        <AdminLayout title={job ? 'Edit job' : 'Create job'}>
            <h1 className="text-2xl font-semibold tracking-tight text-ink">{job ? 'Edit job' : 'Create job'}</h1>
            <form onSubmit={submit} className="mt-6 space-y-8">
                <section className="rounded-xl border border-line bg-white p-6">
                    <h2 className="text-lg font-semibold text-ink">Basic details</h2>
                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <label htmlFor="title" className="mb-1 block text-sm font-medium">
                                Job title
                            </label>
                            <input
                                id="title"
                                className={inputClass}
                                value={form.data.title}
                                onChange={(event) => form.setData('title', event.target.value)}
                            />
                            {fieldError('title')}
                        </div>
                        <div>
                            <label htmlFor="project" className="mb-1 block text-sm font-medium">
                                Project / company
                            </label>
                            <input
                                id="project"
                                className={inputClass}
                                value={form.data.project}
                                onChange={(event) => form.setData('project', event.target.value)}
                            />
                            {fieldError('project')}
                        </div>
                        <div>
                            <label htmlFor="industry" className="mb-1 block text-sm font-medium">
                                Industry
                            </label>
                            <input
                                id="industry"
                                className={inputClass}
                                value={form.data.industry}
                                onChange={(event) => form.setData('industry', event.target.value)}
                            />
                            {fieldError('industry')}
                        </div>
                        <div>
                            <label htmlFor="category" className="mb-1 block text-sm font-medium">
                                Job category
                            </label>
                            <input
                                id="category"
                                className={inputClass}
                                value={form.data.category}
                                onChange={(event) => form.setData('category', event.target.value)}
                            />
                            {fieldError('category')}
                        </div>
                        <div>
                            <label htmlFor="location" className="mb-1 block text-sm font-medium">
                                Location
                            </label>
                            <input
                                id="location"
                                className={inputClass}
                                value={form.data.location}
                                onChange={(event) => form.setData('location', event.target.value)}
                            />
                            {fieldError('location')}
                        </div>
                        <div>
                            <label htmlFor="employment_type" className="mb-1 block text-sm font-medium">
                                Employment type
                            </label>
                            <select
                                id="employment_type"
                                className={inputClass}
                                value={form.data.employment_type}
                                onChange={(event) => form.setData('employment_type', event.target.value)}
                            >
                                <option>Full-time</option>
                                <option>Part-time</option>
                                <option>Contract</option>
                                <option>Temporary</option>
                            </select>
                            {fieldError('employment_type')}
                        </div>
                        <div>
                            <label htmlFor="experience" className="mb-1 block text-sm font-medium">
                                Experience
                            </label>
                            <input
                                id="experience"
                                className={inputClass}
                                value={form.data.experience}
                                onChange={(event) => form.setData('experience', event.target.value)}
                            />
                            {fieldError('experience')}
                        </div>
                        <div>
                            <label htmlFor="salary" className="mb-1 block text-sm font-medium">
                                Salary
                            </label>
                            <input
                                id="salary"
                                className={inputClass}
                                value={form.data.salary ?? ''}
                                onChange={(event) => form.setData('salary', event.target.value)}
                            />
                            {fieldError('salary')}
                        </div>
                        <div>
                            <label htmlFor="vacancy" className="mb-1 block text-sm font-medium">
                                Vacancies
                            </label>
                            <input
                                id="vacancy"
                                type="number"
                                min={1}
                                className={inputClass}
                                value={form.data.vacancy}
                                onChange={(event) => form.setData('vacancy', Number(event.target.value))}
                            />
                            {fieldError('vacancy')}
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-line bg-white p-6">
                    <h2 className="text-lg font-semibold text-ink">Dates</h2>
                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <label htmlFor="posted_date" className="mb-1 block text-sm font-medium">
                                Posted date
                            </label>
                            <input
                                id="posted_date"
                                type="date"
                                className={inputClass}
                                value={form.data.posted_date ?? ''}
                                onChange={(event) => form.setData('posted_date', event.target.value)}
                            />
                            {fieldError('posted_date')}
                        </div>
                        <div>
                            <label htmlFor="closing_date" className="mb-1 block text-sm font-medium">
                                Closing date
                            </label>
                            <input
                                id="closing_date"
                                type="date"
                                className={inputClass}
                                value={form.data.closing_date ?? ''}
                                onChange={(event) => form.setData('closing_date', event.target.value)}
                            />
                            {fieldError('closing_date')}
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-line bg-white p-6">
                    <h2 className="text-lg font-semibold text-ink">Publishing</h2>
                    <div className="mt-4 grid gap-4 md:grid-cols-3">
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.active}
                                onChange={(event) => form.setData('active', event.target.checked)}
                            />
                            Active
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.featured}
                                onChange={(event) => form.setData('featured', event.target.checked)}
                            />
                            Featured
                        </label>
                        <div>
                            <label htmlFor="publishing_status" className="mb-1 block text-sm font-medium">
                                Publishing status
                            </label>
                            <select
                                id="publishing_status"
                                className={inputClass}
                                value={form.data.publishing_status}
                                onChange={(event) =>
                                    form.setData('publishing_status', event.target.value as 'draft' | 'published')
                                }
                            >
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                            {fieldError('publishing_status')}
                        </div>
                    </div>
                </section>

                <section className="rounded-xl border border-line bg-white p-6">
                    <h2 className="text-lg font-semibold text-ink">Job content</h2>
                    <div className="mt-4 space-y-4">
                        <div>
                            <label htmlFor="description" className="mb-1 block text-sm font-medium">
                                Description
                            </label>
                            <textarea
                                id="description"
                                className={`${inputClass} min-h-32`}
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                            />
                            {fieldError('description')}
                        </div>
                        <div>
                            <label htmlFor="responsibilities" className="mb-1 block text-sm font-medium">
                                Responsibilities
                            </label>
                            <textarea
                                id="responsibilities"
                                className={`${inputClass} min-h-28`}
                                value={form.data.responsibilities ?? ''}
                                onChange={(event) => form.setData('responsibilities', event.target.value)}
                            />
                            {fieldError('responsibilities')}
                        </div>
                        <div>
                            <label htmlFor="requirements" className="mb-1 block text-sm font-medium">
                                Requirements
                            </label>
                            <textarea
                                id="requirements"
                                className={`${inputClass} min-h-28`}
                                value={form.data.requirements ?? ''}
                                onChange={(event) => form.setData('requirements', event.target.value)}
                            />
                            {fieldError('requirements')}
                        </div>
                        <div>
                            <label htmlFor="what_we_offer" className="mb-1 block text-sm font-medium">
                                What we offer
                            </label>
                            <textarea
                                id="what_we_offer"
                                className={`${inputClass} min-h-28`}
                                value={form.data.what_we_offer ?? ''}
                                onChange={(event) => form.setData('what_we_offer', event.target.value)}
                            />
                            {fieldError('what_we_offer')}
                        </div>
                    </div>
                </section>

                <div className="flex gap-3">
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                    >
                        {job ? 'Save job' : 'Create job'}
                    </button>
                    <Link href="/admin/jobs" className="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink">
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
