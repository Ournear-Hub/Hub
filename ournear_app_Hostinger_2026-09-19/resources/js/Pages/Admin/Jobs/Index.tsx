import { Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useMemo, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import JobForm from '@/Components/Admin/JobForm';
import {
    Button,
    ConfirmDialog,
    DataTable,
    Drawer,
    FilterBar,
    SearchBar,
    Select,
    StatusBadge,
    Tabs,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { AdminApplicationListItem, AdminJob, Paginator } from '@/types/jobs';
import type { JobFormLookups } from '@/types/masterData';

type Filters = {
    search: string;
    status: string;
    industry_id: string;
    job_category_id: string;
    job_title_id: string;
    location_id: string;
    employment_type_id: string;
    experience_range_id: string;
    featured: string;
    active: string;
    job: string;
    mode: string;
};

type Props = {
    jobs: Paginator<AdminJob>;
    selected: AdminJob | null;
    applications: AdminApplicationListItem[];
    mode: string;
    filters: Filters;
    lookups: JobFormLookups;
};

export default function JobsIndex({ jobs, selected, applications, mode, filters, lookups }: Props) {
    const [tab, setTab] = useState('overview');
    const [confirm, setConfirm] = useState<'deactivate' | 'delete' | null>(null);

    const creating = mode === 'create';
    const editing = mode === 'edit' && selected !== null;
    const viewing = selected !== null && !editing;

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/jobs', params, { preserveState: true, preserveScroll: true });
    };

    const openJob = (job: AdminJob) => visit({ job: job.slug, mode: '' });
    const closeDrawer = useCallback(() => visit({ job: '', mode: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        visit({
            ...Object.fromEntries(form.entries()),
            job: filters.job,
            mode: filters.mode,
        } as Record<string, string>);
    };

    const categories = lookups.categories.filter(
        (category) => !filters.industry_id || String(category.industry_id) === filters.industry_id,
    );
    const titles = lookups.job_titles.filter(
        (title) => !filters.job_category_id || String(title.job_category_id) === filters.job_category_id,
    );

    const columns: Array<DataTableColumn<AdminJob>> = [
        {
            key: 'id',
            header: 'Job ID',
            className: 'text-ink',
            render: (row) => row.business_id ?? '—',
        },
        {
            key: 'title',
            header: 'Job Title',
            className: 'text-ink',
            render: (row) => (
                <div>
                    <p className="font-medium text-ink">{row.title}</p>
                    <p className="text-xs text-muted">{row.project}</p>
                </div>
            ),
        },
        { key: 'industry', header: 'Industry', render: (row) => row.industry },
        { key: 'category', header: 'Category', render: (row) => row.category ?? '—' },
        { key: 'location', header: 'Location', render: (row) => row.location },
        { key: 'type', header: 'Employment Type', render: (row) => row.employment_type },
        { key: 'experience', header: 'Experience', render: (row) => row.experience },
        {
            key: 'status',
            header: 'Status',
            render: (row) => (
                <div className="flex flex-wrap gap-1">
                    <StatusBadge status={row.status_label} />
                    {row.featured ? <StatusBadge status="Featured" /> : null}
                </div>
            ),
        },
        { key: 'apps', header: 'Applications', render: (row) => row.applications_count },
        { key: 'updated', header: 'Updated', render: (row) => row.updated_at ?? '—' },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button variant="secondary" onClick={(event) => { event.stopPropagation(); openJob(row); }}>
                    Open
                </Button>
            ),
        },
    ];

    const drawerTitle = creating ? 'Create job' : editing ? `Edit ${selected?.business_id ?? 'job'}` : selected?.business_id ?? 'Job';

    const detailRows = useMemo(() => {
        if (!selected) {
            return [];
        }

        return [
            ['Job ID', selected.business_id ?? '—'],
            ['Job title', selected.title],
            ['Project / company', selected.project],
            ['Employer ID', selected.employer?.business_id ?? 'Unassigned'],
            ['Employer', selected.employer?.company_name ?? 'Unassigned'],
            ['Employer contact', selected.employer?.contact_person ?? '—'],
            ['Industry', selected.industry],
            ['Job category', selected.category ?? '—'],
            ['Classified title', selected.classified_title ?? '—'],
            ['Location', selected.location],
            ['Employment type', selected.employment_type],
            ['Experience', selected.experience],
            ['Salary', selected.salary ?? '—'],
            ['Vacancies', String(selected.vacancy)],
            ['Published', selected.publishing_status === 'published' ? 'Published' : 'Draft'],
            ['Active', selected.active ? 'Yes' : 'No'],
            ['Featured', selected.featured ? 'Yes' : 'No'],
            ['Created', selected.created_at ?? '—'],
            ['Updated', selected.updated_at ?? '—'],
        ];
    }, [selected]);

    return (
        <AdminLayout title="Jobs">
            <div
                className={selected || creating ? 'pointer-events-none' : undefined}
                inert={selected || creating ? true : undefined}
                aria-hidden={selected || creating ? true : undefined}
            >
                <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-ink">Jobs</h1>
                        <p className="mt-1 text-sm text-muted">Recruitment listings. Legacy free-text values still display when master data is not linked.</p>
                    </div>
                    <Button onClick={() => visit({ mode: 'create', job: '' })}>Create job</Button>
                </div>

                <FilterBar className="md:grid-cols-4 xl:grid-cols-6" onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Job ID, title, company, location" />
                    <Select name="status" defaultValue={filters.status} aria-label="Published">
                        <option value="">Published: any</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </Select>
                    <Select name="active" defaultValue={filters.active} aria-label="Active">
                        <option value="">Active: any</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </Select>
                    <Select name="featured" defaultValue={filters.featured} aria-label="Featured">
                        <option value="">Featured: any</option>
                        <option value="1">Featured</option>
                        <option value="0">Not featured</option>
                    </Select>
                    <Select name="industry_id" defaultValue={filters.industry_id} aria-label="Industry">
                        <option value="">All industries</option>
                        {lookups.industries.map((industry) => (
                            <option key={industry.id} value={industry.id}>
                                {industry.name}
                            </option>
                        ))}
                    </Select>
                    <Select name="job_category_id" defaultValue={filters.job_category_id} aria-label="Job category">
                        <option value="">All categories</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </Select>
                    <Select name="job_title_id" defaultValue={filters.job_title_id} aria-label="Job title">
                        <option value="">All job titles</option>
                        {titles.map((title) => (
                            <option key={title.id} value={title.id}>
                                {title.name}
                            </option>
                        ))}
                    </Select>
                    <Select name="location_id" defaultValue={filters.location_id} aria-label="Location">
                        <option value="">All locations</option>
                        {lookups.locations.map((location) => (
                            <option key={location.id} value={location.id}>
                                {location.name}
                            </option>
                        ))}
                    </Select>
                    <Select name="employment_type_id" defaultValue={filters.employment_type_id} aria-label="Employment type">
                        <option value="">All employment types</option>
                        {lookups.employment_types.map((type) => (
                            <option key={type.id} value={type.id}>
                                {type.name}
                            </option>
                        ))}
                    </Select>
                    <Select name="experience_range_id" defaultValue={filters.experience_range_id} aria-label="Experience">
                        <option value="">All experience</option>
                        {lookups.experience_ranges.map((range) => (
                            <option key={range.id} value={range.id}>
                                {range.name}
                            </option>
                        ))}
                    </Select>
                    <Button type="submit" variant="secondary">
                        Apply
                    </Button>
                </FilterBar>

                <DataTable
                    columns={columns}
                    rows={jobs.data}
                    rowKey={(row) => row.slug}
                    emptyTitle="No jobs match these filters"
                    emptyDescription="Create a job or clear filters to see listings."
                    onRowClick={openJob}
                    selectedKey={selected?.slug ?? null}
                />
                <Pagination links={jobs.links} />
            </div>

            <Drawer
                open={creating || selected !== null}
                title={drawerTitle}
                size="xl"
                onClose={closeDrawer}
            >
                {creating || editing ? (
                    <JobForm job={editing ? selected : null} lookups={lookups} onCancel={closeDrawer} />
                ) : viewing && selected ? (
                    <div className="space-y-4">
                        <Tabs
                            tabs={[
                                { id: 'overview', label: 'Overview' },
                                { id: 'applications', label: `Applications (${selected.applications_count})` },
                            ]}
                            active={tab}
                            onChange={setTab}
                        />

                        {tab === 'overview' ? (
                            <div className="space-y-4">
                                <dl className="grid gap-2 text-sm">
                                    {detailRows.map(([label, value]) => (
                                        <div key={label} className="grid grid-cols-3 gap-2">
                                            <dt className="text-muted">{label}</dt>
                                            <dd className="col-span-2 text-ink">{value}</dd>
                                        </div>
                                    ))}
                                </dl>
                                {!selected.category && selected.legacy_category ? (
                                    <p className="text-sm text-muted">
                                        Legacy V1 category “{selected.legacy_category}” is not a job category.
                                    </p>
                                ) : null}
                                <div>
                                    <h3 className="text-sm font-medium text-ink">Description</h3>
                                    <p className="mt-1 whitespace-pre-line text-sm text-muted">{selected.description}</p>
                                </div>
                                {selected.requirements ? (
                                    <div>
                                        <h3 className="text-sm font-medium text-ink">Requirements</h3>
                                        <p className="mt-1 whitespace-pre-line text-sm text-muted">{selected.requirements}</p>
                                    </div>
                                ) : null}
                                {selected.responsibilities ? (
                                    <div>
                                        <h3 className="text-sm font-medium text-ink">Responsibilities</h3>
                                        <p className="mt-1 whitespace-pre-line text-sm text-muted">{selected.responsibilities}</p>
                                    </div>
                                ) : null}
                                <div className="flex flex-wrap gap-2">
                                    <Button onClick={() => visit({ job: selected.slug, mode: 'edit' })}>Edit</Button>
                                    <Button variant="secondary" onClick={() => router.patch(`/admin/jobs/${selected.slug}/publish`)}>
                                        {selected.publishing_status === 'published' ? 'Unpublish' : 'Publish'}
                                    </Button>
                                    <Button
                                        variant="secondary"
                                        onClick={() => (selected.active ? setConfirm('deactivate') : router.patch(`/admin/jobs/${selected.slug}/active`))}
                                    >
                                        {selected.active ? 'Deactivate' : 'Activate'}
                                    </Button>
                                    <Button variant="secondary" onClick={() => router.patch(`/admin/jobs/${selected.slug}/featured`)}>
                                        {selected.featured ? 'Unfeature' : 'Feature'}
                                    </Button>
                                    <Button variant="danger" onClick={() => setConfirm('delete')}>
                                        Delete
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {applications.length === 0 ? (
                                    <p className="text-sm text-muted">No applications for this job yet.</p>
                                ) : (
                                    <ul className="space-y-2">
                                        {applications.map((application) => (
                                            <li key={application.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                                <p className="font-medium text-ink">{application.full_name}</p>
                                                <p className="text-muted">
                                                    {application.business_id ?? '—'} · {application.status}
                                                    {application.screening_decision_label
                                                        ? ` · ${application.screening_decision_label}`
                                                        : ''}
                                                    {application.applied_at ? ` · ${application.applied_at}` : ''}
                                                </p>
                                                <Link
                                                    href={`/admin/applications?job_id=${selected.id}&application=${application.id}`}
                                                    className="mt-1 inline-block font-medium text-brand"
                                                >
                                                    Open application
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                <div className="flex flex-wrap gap-3">
                                    <Link href={`/admin/applications?job_id=${selected.id}`} className="inline-block text-sm font-medium text-brand">
                                        View Applications
                                    </Link>
                                    <Link href={`/admin/screening?job_id=${selected.id}`} className="inline-block text-sm font-medium text-brand">
                                        Screen applications
                                    </Link>
                                </div>
                            </div>
                        )}
                    </div>
                ) : null}
            </Drawer>

            <ConfirmDialog
                open={confirm !== null}
                title={confirm === 'delete' ? 'Delete this job?' : 'Deactivate this job?'}
                description={
                    confirm === 'delete'
                        ? 'Jobs with applications cannot be deleted. This cannot be undone.'
                        : 'The job will no longer appear as active. Existing applications stay on file.'
                }
                confirmLabel={confirm === 'delete' ? 'Delete' : 'Deactivate'}
                danger
                onConfirm={() => {
                    if (!selected) {
                        return;
                    }
                    if (confirm === 'delete') {
                        router.delete(`/admin/jobs/${selected.slug}`);
                    } else {
                        router.patch(`/admin/jobs/${selected.slug}/active`);
                    }
                    setConfirm(null);
                }}
                onClose={() => setConfirm(null)}
            />
        </AdminLayout>
    );
}
