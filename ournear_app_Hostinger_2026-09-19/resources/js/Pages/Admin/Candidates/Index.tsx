import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useCallback, useEffect, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import {
    ActivityFeed,
    Button,
    DataTable,
    Drawer,
    EmptyState,
    FilterBar,
    Input,
    Label,
    SearchBar,
    Select,
    StatusBadge,
    Tabs,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { AdminCandidate, CandidateLibraryFile, CandidateWorkspaceProps } from '@/types/candidates';

function recorded(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return 'Not recorded yet';
    }

    return String(value);
}

export default function CandidatesIndex({
    candidates,
    selected,
    applications,
    recruitment,
    documents,
    activities,
    filters,
    lookups,
    jobs = [],
}: CandidateWorkspaceProps) {
    const [tab, setTab] = useState('identity');
    const form = useForm({
        name: selected?.name ?? '',
        mobile: selected?.mobile ?? '',
        location_id: selected?.location_id ? String(selected.location_id) : '',
        experience_range_id: selected?.experience_range_id ? String(selected.experience_range_id) : '',
        qualification_id: selected?.qualification_id ? String(selected.qualification_id) : '',
        current_designation: selected?.current_designation ?? '',
        current_company: selected?.current_company ?? '',
        industry_id: selected?.industry_id ? String(selected.industry_id) : '',
        job_category_id: selected?.job_category_id ? String(selected.job_category_id) : '',
        current_salary: selected?.current_salary ?? '',
        expected_salary: selected?.expected_salary ?? '',
        notice_period: selected?.notice_period_value ?? '',
    });

    useEffect(() => {
        form.setData({
            name: selected?.name ?? '',
            mobile: selected?.mobile ?? '',
            location_id: selected?.location_id ? String(selected.location_id) : '',
            experience_range_id: selected?.experience_range_id ? String(selected.experience_range_id) : '',
            qualification_id: selected?.qualification_id ? String(selected.qualification_id) : '',
            current_designation: selected?.current_designation ?? '',
            current_company: selected?.current_company ?? '',
            industry_id: selected?.industry_id ? String(selected.industry_id) : '',
            job_category_id: selected?.job_category_id ? String(selected.job_category_id) : '',
            current_salary: selected?.current_salary ?? '',
            expected_salary: selected?.expected_salary ?? '',
            notice_period: selected?.notice_period_value ?? '',
        });
        form.clearErrors();
        setTab('identity');
    }, [selected?.id]);

    const visit = (overrides: Record<string, string | undefined> = {}, replaceAll = false) => {
        const next = replaceAll ? overrides : { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get('/admin/candidates', params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ candidate: '' }), [filters]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        visit({
            search: String(data.get('search') ?? ''),
            location_id: String(data.get('location_id') ?? ''),
            experience_range_id: String(data.get('experience_range_id') ?? ''),
            qualification_id: String(data.get('qualification_id') ?? ''),
            date_from: String(data.get('date_from') ?? ''),
            date_to: String(data.get('date_to') ?? ''),
            job_id: String(data.get('job_id') ?? ''),
            status: String(data.get('status') ?? ''),
            verification: String(data.get('verification') ?? ''),
            candidate: filters.candidate,
        });
    };

    const saveProfile = (event: FormEvent) => {
        event.preventDefault();
        if (!selected) {
            return;
        }
        form.put(`/admin/candidates/${selected.id}`, { preserveScroll: true });
    };

    const fieldError = (name: keyof typeof form.errors) =>
        form.errors[name] ? <p className="mt-1 text-sm text-danger">{form.errors[name]}</p> : null;

    const columns: Array<DataTableColumn<AdminCandidate>> = [
        { key: 'id', header: 'Candidate ID', className: 'text-ink', render: (row) => row.business_id ?? '—' },
        { key: 'name', header: 'Name', className: 'text-ink', render: (row) => row.name ?? '—' },
        { key: 'contact', header: 'Mobile / email', render: (row) => [row.mobile, row.email].filter(Boolean).join(' · ') || '—' },
        { key: 'location', header: 'Location', render: (row) => row.location ?? '—' },
        { key: 'designation', header: 'Current designation', render: (row) => row.current_designation ?? '—' },
        { key: 'company', header: 'Current company', render: (row) => row.current_company ?? '—' },
        { key: 'experience', header: 'Experience', render: (row) => row.experience ?? '—' },
        { key: 'skills', header: 'Skills', render: (row) => row.skills ?? '—' },
        {
            key: 'status',
            header: 'Status',
            render: (row) => (row.account_status_label ? <StatusBadge status={row.account_status_label} /> : '—'),
        },
        {
            key: 'verified',
            header: 'Verification',
            render: (row) => (row.email_verified ? 'Verified' : 'Unverified'),
        },
        { key: 'apps', header: 'Applications', render: (row) => row.applications_count },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <Button
                    variant="secondary"
                    onClick={(event) => {
                        event.stopPropagation();
                        visit({ candidate: String(row.id) });
                    }}
                >
                    Open
                </Button>
            ),
        },
    ];

    return (
        <AdminLayout title="Candidates">
            <div
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Candidates</h1>
                    <p className="mt-1 text-sm text-muted">
                        Candidate profiles attached to platform users. Guest applications remain in Applications until they have an account.
                    </p>
                </div>

                <FilterBar className="md:grid-cols-4 xl:grid-cols-6" onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Candidate ID, name, mobile, email" />
                    <Select name="job_id" defaultValue={filters.job_id} aria-label="Job">
                        <option value="">All jobs</option>
                        {jobs.map((job) => (
                            <option key={job.id} value={job.id}>
                                {job.business_id ? `${job.business_id} · ${job.title}` : job.title}
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
                    <Select name="experience_range_id" defaultValue={filters.experience_range_id} aria-label="Experience">
                        <option value="">All experience</option>
                        {lookups.experience_ranges.map((range) => (
                            <option key={range.id} value={range.id}>
                                {range.name}
                            </option>
                        ))}
                    </Select>
                    <Select name="qualification_id" defaultValue={filters.qualification_id} aria-label="Qualification">
                        <option value="">All qualifications</option>
                        {lookups.qualifications.map((qualification) => (
                            <option key={qualification.id} value={qualification.id}>
                                {qualification.name}
                            </option>
                        ))}
                    </Select>
                    <Select name="status" defaultValue={filters.status} aria-label="Account status">
                        <option value="">Any status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="blocked">Blocked</option>
                        <option value="suspended">Suspended</option>
                    </Select>
                    <Select name="verification" defaultValue={filters.verification} aria-label="Verification">
                        <option value="">Any verification</option>
                        <option value="verified">Verified</option>
                        <option value="unverified">Unverified</option>
                    </Select>
                    <Input type="date" name="date_from" defaultValue={filters.date_from} aria-label="Registered from" />
                    <Input type="date" name="date_to" defaultValue={filters.date_to} aria-label="Registered to" />
                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit" variant="secondary">
                            Apply
                        </Button>
                        <Button type="button" variant="secondary" onClick={() => visit({}, true)}>
                            Reset filters
                        </Button>
                    </div>
                </FilterBar>

                <DataTable
                    columns={columns}
                    rows={candidates.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No candidates match these filters"
                    emptyDescription="Registered candidates will appear here. Reset filters to see the full list."
                    onRowClick={(row) => visit({ candidate: String(row.id) })}
                    selectedKey={selected?.id ?? null}
                />
                <Pagination links={candidates.links} />
            </div>

            <Drawer open={selected !== null} title={selected?.business_id ?? 'Candidate'} size="xl" onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-4">
                        <div className="flex flex-wrap gap-3 text-sm">
                            <Link href={selected.links?.applications ?? `/admin/applications?candidate=${selected.id}`} className="font-medium text-brand">
                                Applications
                            </Link>
                            <Link href={selected.links?.jobs ?? `/admin/applications?candidate=${selected.id}`} className="font-medium text-brand">
                                Jobs
                            </Link>
                            <Link href={selected.links?.screening ?? `/admin/screening?candidate=${selected.id}`} className="font-medium text-brand">
                                Screening
                            </Link>
                            {selected.user_360_url ? (
                                <Link href={selected.user_360_url} className="font-medium text-brand">
                                    User 360
                                </Link>
                            ) : null}
                        </div>
                        <Tabs
                            tabs={[
                                { id: 'identity', label: 'Identity' },
                                { id: 'professional', label: 'Professional' },
                                { id: 'recruitment', label: 'Recruitment' },
                                { id: 'documents', label: 'Documents' },
                                { id: 'profile', label: 'Edit profile' },
                                { id: 'activity', label: 'Activity' },
                            ]}
                            active={tab}
                            onChange={setTab}
                        />

                        {tab === 'identity' ? (
                            <div className="space-y-4">
                                <div className="flex items-start gap-4">
                                    {selected.photo_url ? (
                                        <img src={selected.photo_url} alt="" className="h-20 w-20 rounded-full object-cover" />
                                    ) : (
                                        <div className="flex h-20 w-20 items-center justify-center rounded-full bg-surface text-sm text-muted">
                                            No photo
                                        </div>
                                    )}
                                    <dl className="grid flex-1 gap-2 text-sm">
                                        {[
                                            ['Candidate ID', selected.business_id ?? '—'],
                                            ['User ID', selected.user_business_id ?? '—'],
                                            ['Name', selected.name ?? '—'],
                                            ['Contact', [selected.mobile, selected.email].filter(Boolean).join(' · ') || '—'],
                                            ['Location', selected.location ?? '—'],
                                            ['Status', selected.account_status_label ?? '—'],
                                            ['Verification', selected.email_verified ? 'Verified' : 'Unverified'],
                                        ].map(([label, value]) => (
                                            <div key={label} className="grid grid-cols-3 gap-2">
                                                <dt className="text-muted">{label}</dt>
                                                <dd className="col-span-2 text-ink">{value}</dd>
                                            </div>
                                        ))}
                                    </dl>
                                </div>
                            </div>
                        ) : null}

                        {tab === 'professional' ? (
                            <div className="space-y-5">
                                <dl className="grid gap-2 text-sm">
                                    {[
                                        ['Current company', recorded(selected.current_company)],
                                        ['Current designation', recorded(selected.current_designation)],
                                        ['Industry', recorded(selected.industry)],
                                        ['Functional area', recorded(selected.functional_area)],
                                        ['Current salary', recorded(selected.current_salary)],
                                        ['Expected salary', recorded(selected.expected_salary)],
                                        ['Experience', selected.experience ?? 'Not recorded yet'],
                                        ['Qualification', selected.qualification ?? 'Not recorded yet'],
                                        ['Skills', recorded(selected.skills)],
                                        ['Notice period', recorded(selected.notice_period)],
                                        ['Present position', recorded(selected.present_position)],
                                        ['Employment preferences', recorded(selected.employment_preference)],
                                        [
                                            'Preferred locations',
                                            recorded((selected.preferred_locations ?? []).join(', ') || null),
                                        ],
                                        [
                                            'Preferred job types',
                                            recorded((selected.preferred_employment_types ?? []).join(', ') || null),
                                        ],
                                    ].map(([label, value]) => (
                                        <div key={label} className="grid grid-cols-3 gap-2">
                                            <dt className="text-muted">{label}</dt>
                                            <dd className="col-span-2 text-ink">{value}</dd>
                                        </div>
                                    ))}
                                </dl>
                                <section>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">
                                        Employment history
                                    </h3>
                                    {(selected.employment_histories ?? []).length === 0 ? (
                                        <p className="mt-2 text-sm text-muted">Not recorded yet.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2 text-sm">
                                            {(selected.employment_histories ?? []).map((history) => (
                                                <li key={history.id} className="rounded-md bg-surface px-3 py-2">
                                                    <p className="font-medium text-ink">
                                                        {history.designation} · {history.company}
                                                        {history.is_current ? ' · Current' : ''}
                                                    </p>
                                                    <p className="text-muted">
                                                        {[
                                                            history.started_on,
                                                            history.is_current ? 'Present' : history.ended_on,
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' – ')}
                                                        {history.industry ? ` · ${history.industry}` : ''}
                                                    </p>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>
                            </div>
                        ) : null}

                        {tab === 'recruitment' ? (
                            <div className="space-y-5">
                                <section>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Jobs applied</h3>
                                    {(recruitment?.jobs.length ?? 0) === 0 ? (
                                        <p className="mt-2 text-sm text-muted">No jobs linked to this candidate yet.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2">
                                            {recruitment?.jobs.map((job) => (
                                                <li key={job.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                                    <Link href={job.workspace_url} className="font-medium text-brand">
                                                        {job.business_id ? `${job.business_id} · ${job.title}` : job.title}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>
                                <section>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Applications</h3>
                                    {applications.length === 0 ? (
                                        <p className="mt-2 text-sm text-muted">This candidate has no account-linked applications yet.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2">
                                            {applications.map((application) => (
                                                <li key={application.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                                    <p className="font-medium text-ink">{application.job?.title ?? 'Application'}</p>
                                                    <p className="text-muted">
                                                        {application.business_id ?? '—'} · {application.status}
                                                        {application.screening_decision_label ? ` · ${application.screening_decision_label}` : ''}
                                                    </p>
                                                    <Link href={`/admin/applications?application=${application.id}`} className="mt-1 inline-block font-medium text-brand">
                                                        Open application
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>
                                <section>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Screening</h3>
                                    {(recruitment?.screening.length ?? 0) === 0 ? (
                                        <p className="mt-2 text-sm text-muted">No screening records yet.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2 text-sm">
                                            {recruitment?.screening.map((item) => (
                                                <li key={item.id}>
                                                    <Link href={item.href} className="font-medium text-brand">
                                                        {item.job_title ?? item.business_id} · {item.decision}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>
                                <section>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Shortlisted</h3>
                                    {(recruitment?.shortlisted.length ?? 0) === 0 ? (
                                        <p className="mt-2 text-sm text-muted">Not shortlisted for any job yet.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2 text-sm">
                                            {recruitment?.shortlisted.map((item) => (
                                                <li key={item.id}>
                                                    <Link href={item.href} className="font-medium text-brand">
                                                        {item.job_title ?? item.business_id}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>
                                <section>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Interviews</h3>
                                    {(recruitment?.interviews.length ?? 0) === 0 ? (
                                        <p className="mt-2 text-sm text-muted">No interviews recorded.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2 text-sm">
                                            {recruitment?.interviews.map((item) => (
                                                <li key={`${item.application_id}-${item.id}`}>
                                                    <Link href={item.href} className="font-medium text-brand">
                                                        {item.job_title ?? `Application ${item.application_id}`}
                                                        {item.scheduled_at ? ` · ${item.scheduled_at}` : ''}
                                                        {item.outcome ? ` · ${item.outcome}` : ''}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>
                                <section>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Employer submissions</h3>
                                    {(recruitment?.submissions.length ?? 0) === 0 ? (
                                        <p className="mt-2 text-sm text-muted">No employer submissions yet.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2 text-sm">
                                            {recruitment?.submissions.map((item, index) => (
                                                <li key={String(item.id ?? index)} className="text-ink">
                                                    {String(item.business_id ?? `Submission ${item.id ?? index + 1}`)} · {String(item.status ?? '')}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>
                                <section>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Selection</h3>
                                    {(recruitment?.selections.length ?? 0) === 0 ? (
                                        <p className="mt-2 text-sm text-muted">No employer selections recorded.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2 text-sm">
                                            {recruitment?.selections.map((item, index) => (
                                                <li key={String(item.id ?? index)}>
                                                    {String(item.business_id ?? `Selection ${item.id ?? index + 1}`)} · {String(item.status ?? 'Selected')}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>
                                <section>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Placement</h3>
                                    {(recruitment?.placements.length ?? 0) === 0 ? (
                                        <p className="mt-2 text-sm text-muted">No placements recorded.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2 text-sm">
                                            {recruitment?.placements.map((item, index) => (
                                                <li key={String(item.id ?? index)}>
                                                    {String(item.business_id ?? `Placement ${item.id ?? index + 1}`)} · {String(item.status ?? '')}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>
                            </div>
                        ) : null}

                        {tab === 'documents' ? (
                            <div className="space-y-5">
                                <section>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Application resumes</h3>
                                    {(documents?.resumes.length ?? 0) === 0 ? (
                                        <p className="mt-2 text-sm text-muted">No resume is stored on this candidate’s applications.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2">
                                            {documents?.resumes.map((resume) => (
                                                <li key={resume.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                                    <p className="font-medium text-ink">{resume.label}</p>
                                                    <p className="text-muted">{resume.application_id ?? `Application ${resume.id}`}</p>
                                                    <div className="mt-2 flex flex-wrap gap-3">
                                                        <a href={resume.view_url} className="font-medium text-brand" target="_blank" rel="noreferrer">
                                                            View Resume
                                                        </a>
                                                        <a href={resume.download_url} className="font-medium text-brand">
                                                            Download Resume
                                                        </a>
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>
                                <section>
                                    <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">Library resumes</h3>
                                    {(documents?.library_resumes?.length ?? 0) === 0 ? (
                                        <p className="mt-2 text-sm text-muted">No library resumes uploaded yet.</p>
                                    ) : (
                                        <ul className="mt-2 space-y-2">
                                            {documents?.library_resumes?.map((resume) => (
                                                <li key={resume.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                                    <p className="font-medium text-ink">{resume.title ?? resume.label}</p>
                                                    <p className="text-muted">
                                                        {resume.original_filename} · {resume.status}
                                                        {resume.is_primary ? ' · Primary' : ''}
                                                    </p>
                                                    <div className="mt-2 flex flex-wrap gap-3">
                                                        <a href={resume.view_url} className="font-medium text-brand" target="_blank" rel="noreferrer">
                                                            View
                                                        </a>
                                                        <a href={resume.download_url} className="font-medium text-brand">
                                                            Download
                                                        </a>
                                                    </div>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </section>
                                {[
                                    ['Certificates', documents?.certificates ?? []],
                                    ['Licences', documents?.licences ?? []],
                                    ['Qualifications', documents?.qualifications ?? []],
                                    ['Payslips', documents?.payslips ?? []],
                                    ['Experience documents', documents?.experience ?? []],
                                    ['Other documents', documents?.other ?? []],
                                ].map(([label, items]) => (
                                    <section key={String(label)}>
                                        <h3 className="text-sm font-semibold uppercase tracking-wide text-muted">{String(label)}</h3>
                                        {(items as { id: number }[]).length === 0 ? (
                                            <p className="mt-2 text-sm text-muted">Not recorded yet.</p>
                                        ) : (
                                            <ul className="mt-2 space-y-2">
                                                {(items as CandidateLibraryFile[]).map((item) => (
                                                    <li key={item.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                                        <p className="font-medium text-ink">{item.title ?? item.label}</p>
                                                        <p className="text-muted">
                                                            {item.original_filename} · {item.status} · {item.verification_status}
                                                        </p>
                                                        <div className="mt-2 flex flex-wrap gap-3">
                                                            <a href={item.view_url} className="font-medium text-brand" target="_blank" rel="noreferrer">
                                                                View
                                                            </a>
                                                            <a href={item.download_url} className="font-medium text-brand">
                                                                Download
                                                            </a>
                                                        </div>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </section>
                                ))}
                            </div>
                        ) : null}

                        {tab === 'profile' ? (
                            <form onSubmit={saveProfile} className="space-y-4">
                                <div>
                                    <Label htmlFor="name">Name</Label>
                                    <Input id="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                                    {fieldError('name')}
                                </div>
                                <div>
                                    <Label htmlFor="email">Email</Label>
                                    <Input id="email" value={selected.email ?? ''} disabled />
                                    <p className="mt-1 text-xs text-muted">Email is the account login and is not edited here.</p>
                                </div>
                                <div>
                                    <Label htmlFor="mobile">Mobile</Label>
                                    <Input id="mobile" value={form.data.mobile ?? ''} onChange={(event) => form.setData('mobile', event.target.value)} />
                                    {fieldError('mobile')}
                                </div>
                                <div>
                                    <Label htmlFor="location_id">Location</Label>
                                    <Select
                                        id="location_id"
                                        value={form.data.location_id}
                                        onChange={(event) => form.setData('location_id', event.target.value)}
                                    >
                                        <option value="">Not classified</option>
                                        {lookups.locations
                                            .filter((location) => location.selectable !== false || String(location.id) === form.data.location_id)
                                            .map((location) => (
                                                <option key={location.id} value={location.id}>
                                                    {location.name}
                                                </option>
                                            ))}
                                    </Select>
                                    {fieldError('location_id')}
                                </div>
                                <div>
                                    <Label htmlFor="experience_range_id">Experience</Label>
                                    <Select
                                        id="experience_range_id"
                                        value={form.data.experience_range_id}
                                        onChange={(event) => form.setData('experience_range_id', event.target.value)}
                                    >
                                        <option value="">Not classified</option>
                                        {lookups.experience_ranges
                                            .filter((range) => range.selectable !== false || String(range.id) === form.data.experience_range_id)
                                            .map((range) => (
                                                <option key={range.id} value={range.id}>
                                                    {range.name}
                                                </option>
                                            ))}
                                    </Select>
                                    {fieldError('experience_range_id')}
                                </div>
                                <div>
                                    <Label htmlFor="qualification_id">Qualification</Label>
                                    <Select
                                        id="qualification_id"
                                        value={form.data.qualification_id}
                                        onChange={(event) => form.setData('qualification_id', event.target.value)}
                                    >
                                        <option value="">Not classified</option>
                                        {lookups.qualifications
                                            .filter((item) => item.selectable !== false || String(item.id) === form.data.qualification_id)
                                            .map((item) => (
                                                <option key={item.id} value={item.id}>
                                                    {item.name}
                                                </option>
                                            ))}
                                    </Select>
                                    {fieldError('qualification_id')}
                                </div>
                                <div>
                                    <Label htmlFor="current_designation">Current designation</Label>
                                    <Input
                                        id="current_designation"
                                        value={form.data.current_designation}
                                        onChange={(event) => form.setData('current_designation', event.target.value)}
                                    />
                                    {fieldError('current_designation')}
                                </div>
                                <div>
                                    <Label htmlFor="current_company">Current company</Label>
                                    <Input
                                        id="current_company"
                                        value={form.data.current_company}
                                        onChange={(event) => form.setData('current_company', event.target.value)}
                                    />
                                    {fieldError('current_company')}
                                </div>
                                <div>
                                    <Label htmlFor="industry_id">Industry</Label>
                                    <Select
                                        id="industry_id"
                                        value={form.data.industry_id}
                                        onChange={(event) => form.setData('industry_id', event.target.value)}
                                    >
                                        <option value="">Not classified</option>
                                        {lookups.industries
                                            .filter((item) => item.selectable !== false || String(item.id) === form.data.industry_id)
                                            .map((item) => (
                                                <option key={item.id} value={item.id}>
                                                    {item.name}
                                                </option>
                                            ))}
                                    </Select>
                                    {fieldError('industry_id')}
                                </div>
                                <div>
                                    <Label htmlFor="job_category_id">Functional area</Label>
                                    <Select
                                        id="job_category_id"
                                        value={form.data.job_category_id}
                                        onChange={(event) => form.setData('job_category_id', event.target.value)}
                                    >
                                        <option value="">Not classified</option>
                                        {lookups.categories
                                            .filter((item) => item.selectable !== false || String(item.id) === form.data.job_category_id)
                                            .map((item) => (
                                                <option key={item.id} value={item.id}>
                                                    {item.name}
                                                </option>
                                            ))}
                                    </Select>
                                    {fieldError('job_category_id')}
                                </div>
                                <div>
                                    <Label htmlFor="current_salary">Current salary</Label>
                                    <Input
                                        id="current_salary"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.data.current_salary}
                                        onChange={(event) => form.setData('current_salary', event.target.value)}
                                    />
                                    {fieldError('current_salary')}
                                </div>
                                <div>
                                    <Label htmlFor="expected_salary">Expected salary</Label>
                                    <Input
                                        id="expected_salary"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={form.data.expected_salary}
                                        onChange={(event) => form.setData('expected_salary', event.target.value)}
                                    />
                                    {fieldError('expected_salary')}
                                </div>
                                <div>
                                    <Label htmlFor="notice_period">Notice period</Label>
                                    <Select
                                        id="notice_period"
                                        value={form.data.notice_period}
                                        onChange={(event) => form.setData('notice_period', event.target.value)}
                                    >
                                        <option value="">Not recorded</option>
                                        {(lookups.notice_periods ?? []).map((period) => (
                                            <option key={period.value} value={period.value}>
                                                {period.label}
                                            </option>
                                        ))}
                                    </Select>
                                    {fieldError('notice_period')}
                                </div>
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing ? 'Saving…' : 'Save profile'}
                                </Button>
                            </form>
                        ) : null}

                        {tab === 'activity' ? (
                            <ActivityFeed items={activities} empty="No recruitment activity is linked to this candidate yet." />
                        ) : null}
                    </div>
                ) : null}
            </Drawer>
        </AdminLayout>
    );
}
