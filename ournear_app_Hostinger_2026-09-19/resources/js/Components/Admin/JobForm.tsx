import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useMemo } from 'react';
import { Button, Input, Label, Select, Textarea } from '@/Components/Admin/ui';
import type { AdminJob } from '@/types/jobs';
import type { JobFormLookups, MasterOption } from '@/types/masterData';

type JobFormProps = {
    job: AdminJob | null;
    lookups: JobFormLookups;
    onCancel: () => void;
    scope?: 'admin' | 'employer';
    companyName?: string;
};

const visibleOptions = (options: MasterOption[], selectedId: string) =>
    options.filter((option) => option.selectable !== false || String(option.id) === selectedId);

export default function JobForm({ job, lookups, onCancel, scope = 'admin', companyName = '' }: JobFormProps) {
    const employerScope = scope === 'employer';
    const form = useForm({
        title: job?.title ?? '',
        project: job?.project ?? companyName,
        industry_id: job?.industry_id ? String(job.industry_id) : '',
        job_category_id: job?.job_category_id ? String(job.job_category_id) : '',
        job_title_id: job?.job_title_id ? String(job.job_title_id) : '',
        location_id: job?.location_id ? String(job.location_id) : '',
        employment_type_id: job?.employment_type_id ? String(job.employment_type_id) : '',
        experience_range_id: job?.experience_range_id ? String(job.experience_range_id) : '',
        employer_id: job?.employer_id ? String(job.employer_id) : '',
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

    const categories = useMemo(
        () =>
            visibleOptions(lookups.categories, form.data.job_category_id).filter(
                (category) => !form.data.industry_id || String(category.industry_id) === form.data.industry_id,
            ),
        [lookups.categories, form.data.industry_id, form.data.job_category_id],
    );

    const titles = useMemo(
        () =>
            visibleOptions(lookups.job_titles, form.data.job_title_id).filter(
                (title) => !form.data.job_category_id || String(title.job_category_id) === form.data.job_category_id,
            ),
        [lookups.job_titles, form.data.job_category_id, form.data.job_title_id],
    );

    const fieldError = (name: keyof typeof form.errors) =>
        form.errors[name] ? <p className="mt-1 text-sm text-danger">{form.errors[name]}</p> : null;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const basePath = employerScope ? '/account/jobs' : '/admin/jobs';
        if (job) {
            form.put(`${basePath}/${job.slug}`, { preserveScroll: true });
            return;
        }
        form.post(basePath, { preserveScroll: true });
    };

    return (
        <form id="admin-job-form" onSubmit={submit} className="space-y-5">
            {job && !job.job_category_id && job.legacy_category ? (
                <p className="rounded-md bg-surface px-3 py-2 text-sm text-muted">
                    Legacy V1 category “{job.legacy_category}” is kept for history. Choose a Job Category such as Civil
                    or Electrical — do not treat Permanent/Contract as a job category.
                </p>
            ) : null}

            <div className="grid gap-4 md:grid-cols-2">
                <div>
                    <Label htmlFor="title">Job title</Label>
                    <Input id="title" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} />
                    {fieldError('title')}
                </div>
                <div>
                    <Label htmlFor="project">Project / company</Label>
                    <Input
                        id="project"
                        value={form.data.project}
                        readOnly={employerScope}
                        onChange={(event) => form.setData('project', event.target.value)}
                    />
                    {fieldError('project')}
                </div>
                {!employerScope ? <div className="md:col-span-2">
                    <Label htmlFor="employer_id">Employer</Label>
                    <Select
                        id="employer_id"
                        value={form.data.employer_id}
                        onChange={(event) => form.setData('employer_id', event.target.value)}
                    >
                        <option value="">Unassigned</option>
                        {(lookups.employers ?? []).map((employer) => (
                            <option key={employer.id} value={employer.id}>
                                {employer.label}
                            </option>
                        ))}
                    </Select>
                    {fieldError('employer_id')}
                    <p className="mt-1 text-xs text-muted">Existing jobs can remain unassigned. Submissions require an employer.</p>
                </div> : null}
                <div>
                    <Label htmlFor="industry_id">Industry</Label>
                    <Select
                        id="industry_id"
                        value={form.data.industry_id}
                        onChange={(event) => {
                            form.setData('industry_id', event.target.value);
                            form.setData('job_category_id', '');
                            form.setData('job_title_id', '');
                        }}
                    >
                        <option value="">{job ? 'Leave unclassified' : 'Select industry'}</option>
                        {visibleOptions(lookups.industries, form.data.industry_id).map((industry) => (
                            <option key={industry.id} value={industry.id}>
                                {industry.name}
                            </option>
                        ))}
                    </Select>
                    {fieldError('industry_id')}
                </div>
                <div>
                    <Label htmlFor="job_category_id">Job category</Label>
                    <Select
                        id="job_category_id"
                        value={form.data.job_category_id}
                        onChange={(event) => {
                            form.setData('job_category_id', event.target.value);
                            form.setData('job_title_id', '');
                        }}
                    >
                        <option value="">{job ? 'Leave unclassified' : 'Select category'}</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </Select>
                    {fieldError('job_category_id')}
                </div>
                <div>
                    <Label htmlFor="job_title_id">Classified job title</Label>
                    <Select
                        id="job_title_id"
                        value={form.data.job_title_id}
                        onChange={(event) => {
                            const value = event.target.value;
                            form.setData('job_title_id', value);
                            const selected = lookups.job_titles.find((title) => String(title.id) === value);
                            if (!job && selected && form.data.title === '') {
                                form.setData('title', selected.name);
                            }
                        }}
                    >
                        <option value="">{job ? 'Leave unclassified' : 'Select job title'}</option>
                        {titles.map((title) => (
                            <option key={title.id} value={title.id}>
                                {title.name}
                            </option>
                        ))}
                    </Select>
                    {fieldError('job_title_id')}
                </div>
                <div>
                    <Label htmlFor="location_id">Location</Label>
                    <Select
                        id="location_id"
                        value={form.data.location_id}
                        onChange={(event) => form.setData('location_id', event.target.value)}
                    >
                        <option value="">{job ? 'Leave unclassified' : 'Select location'}</option>
                        {visibleOptions(lookups.locations, form.data.location_id).map((location) => (
                            <option key={location.id} value={location.id}>
                                {location.name}
                            </option>
                        ))}
                    </Select>
                    {fieldError('location_id')}
                </div>
                <div>
                    <Label htmlFor="employment_type_id">Employment type</Label>
                    <Select
                        id="employment_type_id"
                        value={form.data.employment_type_id}
                        onChange={(event) => form.setData('employment_type_id', event.target.value)}
                    >
                        <option value="">{job ? 'Leave unclassified' : 'Select employment type'}</option>
                        {visibleOptions(lookups.employment_types, form.data.employment_type_id).map((type) => (
                            <option key={type.id} value={type.id}>
                                {type.name}
                            </option>
                        ))}
                    </Select>
                    {fieldError('employment_type_id')}
                </div>
                <div>
                    <Label htmlFor="experience_range_id">Experience</Label>
                    <Select
                        id="experience_range_id"
                        value={form.data.experience_range_id}
                        onChange={(event) => form.setData('experience_range_id', event.target.value)}
                    >
                        <option value="">{job ? 'Leave unclassified' : 'Select experience'}</option>
                        {visibleOptions(lookups.experience_ranges, form.data.experience_range_id).map((range) => (
                            <option key={range.id} value={range.id}>
                                {range.name}
                            </option>
                        ))}
                    </Select>
                    {fieldError('experience_range_id')}
                </div>
                <div>
                    <Label htmlFor="salary">Salary</Label>
                    <Input id="salary" value={form.data.salary ?? ''} onChange={(event) => form.setData('salary', event.target.value)} />
                    {fieldError('salary')}
                </div>
                <div>
                    <Label htmlFor="vacancy">Vacancies</Label>
                    <Input
                        id="vacancy"
                        type="number"
                        min={1}
                        value={form.data.vacancy}
                        onChange={(event) => form.setData('vacancy', Number(event.target.value))}
                    />
                    {fieldError('vacancy')}
                </div>
                <div>
                    <Label htmlFor="posted_date">Posted date</Label>
                    <Input
                        id="posted_date"
                        type="date"
                        value={form.data.posted_date ?? ''}
                        onChange={(event) => form.setData('posted_date', event.target.value)}
                    />
                    {fieldError('posted_date')}
                </div>
                <div>
                    <Label htmlFor="closing_date">Closing date</Label>
                    <Input
                        id="closing_date"
                        type="date"
                        value={form.data.closing_date ?? ''}
                        onChange={(event) => form.setData('closing_date', event.target.value)}
                    />
                    {fieldError('closing_date')}
                </div>
            </div>

            {!employerScope ? <div className="grid gap-4 md:grid-cols-3">
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
                    <Label htmlFor="publishing_status">Published</Label>
                    <Select
                        id="publishing_status"
                        value={form.data.publishing_status}
                        onChange={(event) => form.setData('publishing_status', event.target.value as 'draft' | 'published')}
                    >
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </Select>
                    {fieldError('publishing_status')}
                </div>
            </div> : (
                <p className="rounded-md border border-line bg-surface px-3 py-2 text-sm text-muted">
                    Employer jobs are active when posted. Basic and trial jobs are never marked Featured without a verified payment.
                </p>
            )}

            <div>
                <Label htmlFor="description">Description</Label>
                <Textarea
                    id="description"
                    value={form.data.description}
                    onChange={(event) => form.setData('description', event.target.value)}
                />
                {fieldError('description')}
            </div>
            <div>
                <Label htmlFor="requirements">Requirements</Label>
                <Textarea
                    id="requirements"
                    value={form.data.requirements ?? ''}
                    onChange={(event) => form.setData('requirements', event.target.value)}
                />
                {fieldError('requirements')}
            </div>
            <div>
                <Label htmlFor="responsibilities">Responsibilities</Label>
                <Textarea
                    id="responsibilities"
                    value={form.data.responsibilities ?? ''}
                    onChange={(event) => form.setData('responsibilities', event.target.value)}
                />
                {fieldError('responsibilities')}
            </div>
            <div>
                <Label htmlFor="what_we_offer">What we offer</Label>
                <Textarea
                    id="what_we_offer"
                    value={form.data.what_we_offer ?? ''}
                    onChange={(event) => form.setData('what_we_offer', event.target.value)}
                />
                {fieldError('what_we_offer')}
            </div>

            <div className="flex gap-2">
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? 'Saving…' : job ? 'Save job' : 'Create job'}
                </Button>
                <Button type="button" variant="secondary" onClick={onCancel}>
                    Cancel
                </Button>
            </div>
        </form>
    );
}
