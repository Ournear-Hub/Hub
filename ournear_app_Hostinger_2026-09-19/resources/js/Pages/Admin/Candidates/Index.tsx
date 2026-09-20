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
    FilterBar,
    Input,
    Label,
    SearchBar,
    Select,
    Tabs,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type { AdminCandidate, CandidateWorkspaceProps } from '@/types/candidates';

export default function CandidatesIndex({
    candidates,
    selected,
    applications,
    activities,
    filters,
    lookups,
}: CandidateWorkspaceProps) {
    const [tab, setTab] = useState('overview');
    const form = useForm({
        name: selected?.name ?? '',
        mobile: selected?.mobile ?? '',
        location_id: selected?.location_id ? String(selected.location_id) : '',
        experience_range_id: selected?.experience_range_id ? String(selected.experience_range_id) : '',
        qualification_id: selected?.qualification_id ? String(selected.qualification_id) : '',
    });

    useEffect(() => {
        form.setData({
            name: selected?.name ?? '',
            mobile: selected?.mobile ?? '',
            location_id: selected?.location_id ? String(selected.location_id) : '',
            experience_range_id: selected?.experience_range_id ? String(selected.experience_range_id) : '',
            qualification_id: selected?.qualification_id ? String(selected.qualification_id) : '',
        });
        form.clearErrors();
        setTab('overview');
    }, [selected?.id]);

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
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
        { key: 'name', header: 'Candidate Name', className: 'text-ink', render: (row) => row.name ?? '—' },
        { key: 'mobile', header: 'Mobile', render: (row) => row.mobile ?? '—' },
        { key: 'email', header: 'Email', render: (row) => row.email ?? '—' },
        { key: 'location', header: 'Location', render: (row) => row.location ?? '—' },
        { key: 'experience', header: 'Experience', render: (row) => row.experience ?? '—' },
        { key: 'qualification', header: 'Qualification', render: (row) => row.qualification ?? '—' },
        { key: 'apps', header: 'Applications', render: (row) => row.applications_count },
        { key: 'created', header: 'Created', render: (row) => row.created_at ?? '—' },
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
                        Registered candidate accounts. Guest applications remain in Applications until they have an account.
                    </p>
                </div>

                <FilterBar className="md:grid-cols-4 xl:grid-cols-6" onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Candidate ID, name, mobile, email" />
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
                    <Input type="date" name="date_from" defaultValue={filters.date_from} aria-label="Registered from" />
                    <Input type="date" name="date_to" defaultValue={filters.date_to} aria-label="Registered to" />
                    <Button type="submit" variant="secondary">
                        Apply
                    </Button>
                </FilterBar>

                <DataTable
                    columns={columns}
                    rows={candidates.data}
                    rowKey={(row) => row.id}
                    emptyTitle="No candidates match these filters"
                    emptyDescription="Registered candidates will appear here."
                    onRowClick={(row) => visit({ candidate: String(row.id) })}
                    selectedKey={selected?.id ?? null}
                />
                <Pagination links={candidates.links} />
            </div>

            <Drawer open={selected !== null} title={selected?.business_id ?? 'Candidate'} size="xl" onClose={closeDrawer}>
                {selected ? (
                    <div className="space-y-4">
                        <Tabs
                            tabs={[
                                { id: 'overview', label: 'Overview' },
                                { id: 'profile', label: 'Profile' },
                                { id: 'resume', label: 'Resume' },
                                { id: 'applications', label: `Applications (${selected.applications_count})` },
                                { id: 'activity', label: 'Activity' },
                            ]}
                            active={tab}
                            onChange={setTab}
                        />

                        {tab === 'overview' ? (
                            <dl className="grid gap-2 text-sm">
                                {[
                                    ['Candidate ID', selected.business_id ?? '—'],
                                    ['Name', selected.name ?? '—'],
                                    ['Mobile', selected.mobile ?? '—'],
                                    ['Email', selected.email ?? '—'],
                                    ['Location', selected.location ?? '—'],
                                    ['Experience', selected.experience ?? '—'],
                                    ['Qualification', selected.qualification ?? '—'],
                                    ['Applications', String(selected.applications_count)],
                                    ['Created', selected.created_at ?? '—'],
                                    ['Updated', selected.updated_at ?? '—'],
                                ].map(([label, value]) => (
                                    <div key={label} className="grid grid-cols-3 gap-2">
                                        <dt className="text-muted">{label}</dt>
                                        <dd className="col-span-2 text-ink">{value}</dd>
                                    </div>
                                ))}
                            </dl>
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
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing ? 'Saving…' : 'Save profile'}
                                </Button>
                            </form>
                        ) : null}

                        {tab === 'resume' ? (
                            <div className="space-y-3">
                                {applications.filter((application) => application.has_resume).length === 0 ? (
                                    <p className="text-sm text-muted">No resume is stored on this candidate’s applications.</p>
                                ) : (
                                    applications
                                        .filter((application) => application.has_resume)
                                        .map((application) => (
                                            <div key={application.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                                <p className="font-medium text-ink">{application.job?.title ?? 'Application'}</p>
                                                <p className="text-muted">{application.business_id ?? `Application ${application.id}`}</p>
                                                {application.resume_url ? (
                                                    <a href={application.resume_url} className="mt-1 inline-block font-medium text-brand">
                                                        Download resume
                                                    </a>
                                                ) : null}
                                            </div>
                                        ))
                                )}
                            </div>
                        ) : null}

                        {tab === 'applications' ? (
                            <div className="space-y-3">
                                {applications.length === 0 ? (
                                    <p className="text-sm text-muted">This candidate has no account-linked applications yet.</p>
                                ) : (
                                    <ul className="space-y-2">
                                        {applications.map((application) => (
                                            <li key={application.id} className="rounded-md bg-surface px-3 py-2 text-sm">
                                                <p className="font-medium text-ink">{application.job?.title ?? 'Application'}</p>
                                                <p className="text-muted">
                                                    {application.business_id ?? '—'} · {application.status}
                                                    {application.screening_decision_label
                                                        ? ` · ${application.screening_decision_label}`
                                                        : ''}
                                                    {application.applied_at ? ` · ${application.applied_at}` : ''}
                                                </p>
                                                <Link
                                                    href={`/admin/applications?application=${application.id}`}
                                                    className="mt-1 inline-block font-medium text-brand"
                                                >
                                                    Open application
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                {selected.email ? (
                                    <Link
                                        href={`/admin/applications?search=${encodeURIComponent(selected.email)}`}
                                        className="inline-block text-sm font-medium text-brand"
                                    >
                                        Open in Applications
                                    </Link>
                                ) : null}
                            </div>
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
