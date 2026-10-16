import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
import { Button, Input, Label, Select, Textarea } from '@/Components/Admin/ui';
import Combobox from '@/Components/molecules/Combobox';
import MultiSelect from '@/Components/molecules/MultiSelect';
import type { AdminJob } from '@/types/jobs';
import type { JobFormLookups, MasterOption } from '@/types/masterData';

type JobFormProps = {
    job: AdminJob | null;
    lookups: JobFormLookups;
    onCancel: () => void;
    scope?: 'admin' | 'employer';
    companyName?: string;
    companyIndustryId?: number | null;
    companyIndustryName?: string | null;
    submitBasePath?: string;
    formExtras?: Record<string, string | boolean | number>;
};

const visibleOptions = (options: MasterOption[], selectedId: string) =>
    options.filter((option) => option.selectable !== false || String(option.id) === selectedId);

function MasterDataDropdownFooter({
    label,
    placeholder,
    busy,
    onAdd,
}: {
    label: string;
    placeholder: string;
    busy: boolean;
    onAdd: (name: string) => void | Promise<void>;
}) {
    const [draft, setDraft] = useState('');

    return (
        <div className="space-y-1.5" onMouseDown={(event) => event.preventDefault()}>
            <p className="text-xs font-semibold text-ink">{label}</p>
            <div className="flex gap-1.5">
                <input
                    type="text"
                    value={draft}
                    placeholder={placeholder}
                    disabled={busy}
                    className="min-w-0 flex-1 rounded-md border border-line px-2 py-1.5 text-sm text-ink"
                    onChange={(event) => setDraft(event.target.value)}
                    onKeyDown={(event) => {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            const trimmed = draft.trim();
                            if (trimmed) {
                                void onAdd(trimmed);
                                setDraft('');
                            }
                        }
                    }}
                />
                <button
                    type="button"
                    disabled={busy || !draft.trim()}
                    className="shrink-0 rounded-md bg-brand px-2.5 py-1.5 text-xs font-semibold text-white disabled:opacity-50"
                    onClick={() => {
                        const trimmed = draft.trim();
                        if (!trimmed) {
                            return;
                        }
                        void onAdd(trimmed);
                        setDraft('');
                    }}
                >
                    {busy ? 'Adding…' : 'Add'}
                </button>
            </div>
        </div>
    );
}

export default function JobForm({
    job,
    lookups,
    onCancel,
    scope = 'admin',
    companyName = '',
    companyIndustryId = null,
    companyIndustryName = null,
    submitBasePath,
    formExtras,
}: JobFormProps) {
    const employerScope = scope === 'employer';
    const employerIndustryKey = companyIndustryId ? String(companyIndustryId) : '';
    const form = useForm({
        title: job?.title ?? '',
        project: employerScope ? (job?.project ?? '') : (job?.project ?? companyName),
        industry_id: employerScope
            ? employerIndustryKey
            : job?.industry_id
              ? String(job.industry_id)
              : '',
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
        urgent: job?.urgent ?? false,
        listing_priority:
            (job as { listing_priority?: string } | null)?.listing_priority ??
            (job?.urgent ? 'urgent' : 'standard'),
        active: job?.active ?? (employerScope ? false : true),
        publishing_status: job?.publishing_status ?? 'draft',
        joining_date: job?.joining_date ?? '',
        accommodation: job?.accommodation ?? '',
        description: job?.description ?? '',
        responsibilities: job?.responsibilities ?? '',
        requirements: job?.requirements ?? '',
        what_we_offer: job?.what_we_offer ?? '',
        qualification_ids: job?.qualification_ids ?? [],
        skill_ids: job?.skill_ids ?? [],
        ...(formExtras ?? {}),
    });

    const activeIndustryId = employerScope ? employerIndustryKey : form.data.industry_id;

    const categories = useMemo(
        () =>
            visibleOptions(lookups.categories, form.data.job_category_id).filter(
                (category) => !activeIndustryId || String(category.industry_id) === activeIndustryId,
            ),
        [lookups.categories, activeIndustryId, form.data.job_category_id],
    );

    const categoryOptions = useMemo(
        () =>
            categories.map((category) => ({
                value: String(category.id),
                label: category.name,
                disabled: category.selectable === false,
            })),
        [categories],
    );

    const [qualificationOptions, setQualificationOptions] = useState(() =>
        visibleOptions(lookups.qualifications, '').map((option) => ({
            value: String(option.id),
            label: option.name,
            disabled: option.selectable === false,
        })),
    );

    const [skillOptions, setSkillOptions] = useState(() =>
        (lookups.skills ?? []).map((option) => ({
            value: String(option.id),
            label: option.name,
            disabled: option.selectable === false,
        })),
    );

    const [qualificationCreateBusy, setQualificationCreateBusy] = useState(false);
    const [skillCreateBusy, setSkillCreateBusy] = useState(false);

    const createQualification = async (name: string) => {
        if (!name.trim() || qualificationCreateBusy) {
            return;
        }
        setQualificationCreateBusy(true);
        const response = await fetch('/account/jobs/master-data/qualifications', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ name: name.trim() }),
        });
        setQualificationCreateBusy(false);
        if (!response.ok) {
            return;
        }
        const payload = (await response.json()) as { id: number; name: string };
        setQualificationOptions((current) => [
            ...current.filter((option) => option.value !== String(payload.id)),
            { value: String(payload.id), label: payload.name, disabled: false },
        ]);
        form.setData('qualification_ids', [payload.id]);
    };

    const createSkill = async (name: string) => {
        if (!name.trim() || skillCreateBusy) {
            return;
        }
        setSkillCreateBusy(true);
        const response = await fetch('/account/jobs/master-data/skills', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ name: name.trim() }),
        });
        setSkillCreateBusy(false);
        if (!response.ok) {
            return;
        }
        const payload = (await response.json()) as { id: number; name: string };
        setSkillOptions((current) => [
            ...current.filter((option) => option.value !== String(payload.id)),
            { value: String(payload.id), label: payload.name, disabled: false },
        ]);
        const nextIds = [...form.data.skill_ids, payload.id].filter(
            (id, index, all) => all.indexOf(id) === index,
        );
        form.setData('skill_ids', nextIds);
    };

    const titles = useMemo(
        () =>
            visibleOptions(lookups.job_titles, form.data.job_title_id).filter(
                (title) => !form.data.job_category_id || String(title.job_category_id) === form.data.job_category_id,
            ),
        [lookups.job_titles, form.data.job_category_id, form.data.job_title_id],
    );

    const fieldError = (name: keyof typeof form.errors | string) => {
        const direct = form.errors[name as keyof typeof form.errors];
        if (direct) {
            return <p className="mt-1 text-sm text-danger">{direct}</p>;
        }

        const prefix = `${name}.`;
        const nested = Object.entries(form.errors).find(([key]) => key.startsWith(prefix));
        if (! nested) {
            return null;
        }

        return <p className="mt-1 text-sm text-danger">{nested[1]}</p>;
    };

    const arrayFieldError = (prefix: 'qualification_ids' | 'skill_ids') => {
        const messages = [
            form.errors[prefix],
            ...Object.entries(form.errors)
                .filter(([key]) => key.startsWith(`${prefix}.`))
                .map(([, message]) => message),
        ].filter(Boolean);

        if (messages.length === 0) {
            return null;
        }

        return (
            <ul className="mt-1 list-inside list-disc text-sm text-danger">
                {messages.map((message, index) => (
                    <li key={`${prefix}-error-${index}`}>{message}</li>
                ))}
            </ul>
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const basePath = submitBasePath ?? (employerScope ? '/account/jobs' : '/admin/jobs');
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
                    <Label htmlFor="project">{employerScope ? 'Project name (optional)' : 'Project / company'}</Label>
                    <Input
                        id="project"
                        value={form.data.project}
                        placeholder={employerScope ? 'Leave blank to use your company name' : undefined}
                        onChange={(event) => form.setData('project', event.target.value)}
                    />
                    {fieldError('project')}
                </div>
                {employerScope ? (
                    <div>
                        <Label>Company industry</Label>
                        <p className="mt-1 rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink">
                            {companyIndustryName?.trim() || 'Set your industry on Company Profile before publishing jobs.'}
                        </p>
                        {fieldError('industry_id')}
                    </div>
                ) : null}
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
                {!employerScope ? (
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
                ) : null}
                <div>
                    <Label htmlFor="job_category_id">Job category</Label>
                    <Combobox
                        id="job_category_id"
                        surface={employerScope ? 'public' : 'admin'}
                        placeholder={categories.length === 0 ? 'No categories for this industry' : 'Search categories…'}
                        emptyMessage="No matching categories"
                        options={categoryOptions}
                        value={form.data.job_category_id}
                        onChange={(value) => {
                            form.setData('job_category_id', value);
                            form.setData('job_title_id', '');
                        }}
                        error={Boolean(form.errors.job_category_id)}
                    />
                    {employerScope && categories.length === 0 ? (
                        <p className="mt-1 text-xs text-muted">
                            Set your company industry on Company Profile, then choose from active job categories in master
                            data.
                        </p>
                    ) : null}
                    {fieldError('job_category_id')}
                </div>
                {!employerScope ? (
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
                ) : null}
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
                <div>
                    <Label htmlFor="joining_date">Joining date</Label>
                    <Input
                        id="joining_date"
                        type="date"
                        value={form.data.joining_date ?? ''}
                        onChange={(event) => form.setData('joining_date', event.target.value)}
                    />
                    {fieldError('joining_date')}
                </div>
                {!employerScope ? (
                    <div className="md:col-span-2">
                        <Label htmlFor="accommodation">Accommodation</Label>
                        <Textarea
                            id="accommodation"
                            value={form.data.accommodation ?? ''}
                            onChange={(event) => form.setData('accommodation', event.target.value)}
                        />
                        {fieldError('accommodation')}
                    </div>
                ) : null}
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
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={form.data.urgent}
                        onChange={(event) => form.setData('urgent', event.target.checked)}
                    />
                    Urgent
                </label>
                <div>
                    <Label htmlFor="publishing_status">Lifecycle</Label>
                    <Select
                        id="publishing_status"
                        value={form.data.publishing_status}
                        onChange={(event) => form.setData('publishing_status', event.target.value as AdminJob['publishing_status'])}
                    >
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="paused">Paused</option>
                        <option value="closed">Closed</option>
                        <option value="archived">Archived</option>
                    </Select>
                    {fieldError('publishing_status')}
                </div>
            </div> : (
                <div className="space-y-3">
                    <div>
                        <Label htmlFor="listing_priority">Job classification</Label>
                        <Select
                            id="listing_priority"
                            value={form.data.listing_priority}
                            onChange={(event) => form.setData('listing_priority', event.target.value)}
                        >
                            <option value="standard">Standard</option>
                            <option value="urgent">Urgent</option>
                            <option value="immediate_requirement">Immediate requirement</option>
                        </Select>
                    </div>
                    <p className="rounded-md border border-line bg-surface px-3 py-2 text-sm text-muted">
                        New jobs are saved as drafts by default. Publish from Job management when you are eligible. Your first published job may use a one-hour trial tier. Basic jobs are never Featured without verified payment.
                    </p>
                </div>
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
            <fieldset className="space-y-3">
                <legend className="text-sm font-medium text-ink">Qualification (optional)</legend>
                <p className="text-xs text-muted">
                    Search and select a qualification from master data. Shown on the public job detail page.
                </p>
                {employerScope ? (
                    <Combobox
                        id="qualification_ids"
                        surface="public"
                        placeholder="Search qualifications…"
                        emptyMessage="No matching qualifications"
                        options={qualificationOptions}
                        value={form.data.qualification_ids[0] ? String(form.data.qualification_ids[0]) : ''}
                        onChange={(value) =>
                            form.setData(
                                'qualification_ids',
                                value ? [Number(value)].filter((id) => id > 0) : [],
                            )
                        }
                        error={Boolean(form.errors.qualification_ids)}
                        listFooter={
                            <MasterDataDropdownFooter
                                label="Add qualification"
                                placeholder="New qualification name"
                                busy={qualificationCreateBusy}
                                onAdd={(value) => createQualification(value)}
                            />
                        }
                    />
                ) : null}
                {!employerScope ? (
                    <MultiSelect
                        id="qualification_ids"
                        surface="admin"
                        placeholder="Search qualifications…"
                        emptyMessage="No matching qualifications"
                        options={qualificationOptions}
                        value={form.data.qualification_ids.map(String)}
                        onChange={(values) =>
                            form.setData(
                                'qualification_ids',
                                values.map((value) => Number(value)).filter((id) => id > 0),
                            )
                        }
                        error={Boolean(form.errors.qualification_ids)}
                    />
                ) : null}
                {arrayFieldError('qualification_ids')}
            </fieldset>
            <fieldset className="space-y-3">
                <legend className="text-sm font-medium text-ink">Skills (optional)</legend>
                <p className="text-xs text-muted">
                    Search and select multiple skills from the shared skills library.
                </p>
                <MultiSelect
                    id="skill_ids"
                    surface={employerScope ? 'public' : 'admin'}
                    placeholder="Search skills…"
                    emptyMessage="No matching skills"
                    options={skillOptions}
                    value={form.data.skill_ids.map(String)}
                    onChange={(values) =>
                        form.setData(
                            'skill_ids',
                            values.map((value) => Number(value)).filter((id) => id > 0),
                        )
                    }
                    error={Boolean(form.errors.skill_ids)}
                    listFooter={
                        employerScope ? (
                            <MasterDataDropdownFooter
                                label="Add skill"
                                placeholder="New skill name"
                                busy={skillCreateBusy}
                                onAdd={(value) => createSkill(value)}
                            />
                        ) : undefined
                    }
                />
                {arrayFieldError('skill_ids')}
            </fieldset>
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
                    {form.processing ? 'Saving…' : job ? 'Save job' : employerScope ? 'Save draft' : 'Create job'}
                </Button>
                <Button type="button" variant="secondary" onClick={onCancel}>
                    Cancel
                </Button>
            </div>
        </form>
    );
}
