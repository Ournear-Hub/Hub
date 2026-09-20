import { router } from '@inertiajs/react';
import { useCallback, useState, type FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import ApplicationDrawer from '@/Components/Admin/ApplicationDrawer';
import {
    Button,
    ConfirmDialog,
    DataTable,
    Drawer,
    FilterBar,
    Input,
    SearchBar,
    Select,
    StatusBadge,
} from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';
import type {
    AdminApplicationListItem,
    ApplicationWorkspaceProps,
} from '@/types/applications';

type Variant = 'applications' | 'screening' | 'shortlisted';

type Props = ApplicationWorkspaceProps & {
    title: string;
    description: string;
    basePath: string;
    variant: Variant;
    defaultTab?: string;
};

export default function ApplicationWorkspacePage({
    title,
    description,
    basePath,
    variant,
    defaultTab = 'overview',
    applications,
    selected,
    filters,
    options,
    statuses,
    interviewModes,
    interviewOutcomes,
    rejectionReasons,
}: Props) {
    const [compareIds, setCompareIds] = useState<number[]>([]);
    const [compareOpen, setCompareOpen] = useState(false);
    const [submitId, setSubmitId] = useState<number | null>(null);

    const visit = (overrides: Record<string, string | undefined> = {}) => {
        const next = { ...filters, ...overrides };
        const params: Record<string, string> = {};
        Object.entries(next).forEach(([key, value]) => {
            if (value) {
                params[key] = value;
            }
        });
        router.get(basePath, params, { preserveState: true, preserveScroll: true });
    };

    const closeDrawer = useCallback(() => visit({ application: '' }), [filters, basePath]);

    const filter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        visit({
            ...Object.fromEntries(form.entries()),
            application: filters.application,
        } as Record<string, string>);
    };

    const toggleCompare = (id: number) => {
        setCompareIds((current) =>
            current.includes(id) ? current.filter((item) => item !== id) : [...current, id],
        );
    };

    const compared = applications.data.filter((row) => compareIds.includes(row.id));

    const columns: Array<DataTableColumn<AdminApplicationListItem>> = [
        ...(variant === 'screening' && filters.job_id
            ? [
                  {
                      key: 'compare',
                      header: 'Compare',
                      render: (row: AdminApplicationListItem) => (
                          <input
                              type="checkbox"
                              checked={compareIds.includes(row.id)}
                              aria-label={`Compare ${row.full_name}`}
                              onClick={(event) => event.stopPropagation()}
                              onChange={() => toggleCompare(row.id)}
                          />
                      ),
                  } satisfies DataTableColumn<AdminApplicationListItem>,
              ]
            : []),
        {
            key: 'application',
            header: 'Application ID',
            className: 'font-medium text-ink',
            render: (row) => row.business_id ?? '—',
        },
        {
            key: 'candidate_id',
            header: 'Candidate ID',
            render: (row) => row.candidate.business_id ?? '—',
        },
        {
            key: 'candidate',
            header: 'Candidate',
            className: 'font-medium text-ink',
            render: (row) => row.full_name,
        },
        {
            key: 'job_id',
            header: 'Job ID',
            render: (row) => row.job?.business_id ?? '—',
        },
        {
            key: 'job',
            header: 'Job',
            render: (row) => row.job?.title ?? '—',
        },
        {
            key: 'applied',
            header: variant === 'shortlisted' ? 'Shortlisted' : 'Applied',
            render: (row) => (variant === 'shortlisted' ? row.shortlisted_at ?? '—' : row.applied_at ?? '—'),
        },
        { key: 'experience', header: 'Experience', render: (row) => row.experience || '—' },
        { key: 'location', header: 'Location', render: (row) => row.current_location || '—' },
        { key: 'qualification', header: 'Qualification', render: (row) => row.qualification || '—' },
        ...(variant === 'applications'
            ? []
            : [
                  {
                      key: 'screening',
                      header: 'Screening',
                      render: (row: AdminApplicationListItem) => <StatusBadge status={row.screening_decision_label} />,
                  } satisfies DataTableColumn<AdminApplicationListItem>,
              ]),
        {
            key: 'status',
            header: 'Status',
            render: (row) => <StatusBadge status={row.status} />,
        },
        ...(variant === 'shortlisted'
            ? []
            : [
                  {
                      key: 'updated',
                      header: 'Updated',
                      render: (row: AdminApplicationListItem) => row.updated_at ?? '—',
                  } satisfies DataTableColumn<AdminApplicationListItem>,
              ]),
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-1">
                    <Button variant="ghost" onClick={() => visit({ application: String(row.id) })}>
                        View
                    </Button>
                    {variant === 'shortlisted' ? (
                        <Button
                            variant="ghost"
                            disabled={!row.can_submit_to_employer}
                            title={row.submit_blocked_reason ?? 'Submit to employer'}
                            onClick={(event) => {
                                event.stopPropagation();
                                if (!row.can_submit_to_employer) {
                                    return;
                                }
                                setSubmitId(row.id);
                            }}
                        >
                            Submit
                        </Button>
                    ) : null}
                </div>
            ),
        },
    ];

    return (
        <AdminLayout title={title}>
            <div
                className={selected ? 'pointer-events-none' : undefined}
                inert={selected ? true : undefined}
                aria-hidden={selected ? true : undefined}
            >
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">{title}</h1>
                    <p className="mt-1 text-sm text-muted">{description}</p>
                </div>

                <FilterBar onSubmit={filter}>
                    <SearchBar defaultValue={filters.search} placeholder="Application, candidate, job ID, name, mobile, email" />
                    <Select name="status" defaultValue={filters.status} aria-label="Application status">
                        <option value="">All statuses</option>
                        {statuses.map((status) => (
                            <option key={status} value={status}>
                                {status}
                            </option>
                        ))}
                    </Select>
                    <Select name="job_id" defaultValue={filters.job_id} aria-label="Job">
                        <option value="">All jobs</option>
                        {options.jobs.map((job) => (
                            <option key={job.id} value={job.id}>
                                {job.business_id ? `${job.business_id} · ${job.title}` : job.title}
                            </option>
                        ))}
                    </Select>
                    {(variant === 'screening' || variant === 'shortlisted') && (
                        <Select name="screening_decision" defaultValue={filters.screening_decision} aria-label="Screening decision">
                            <option value="">All screening</option>
                            {options.screening_decisions.map((decision) => (
                                <option key={decision.value} value={decision.value}>
                                    {decision.label}
                                </option>
                            ))}
                        </Select>
                    )}
                    <Select name="location_id" defaultValue={filters.location_id} aria-label="Location">
                        <option value="">All locations</option>
                        {options.locations.map((location) => (
                            <option key={location.id} value={location.id}>
                                {location.name}
                            </option>
                        ))}
                    </Select>
                    <Select name="experience_range_id" defaultValue={filters.experience_range_id} aria-label="Experience">
                        <option value="">All experience</option>
                        {options.experiences.map((range) => (
                            <option key={range.id} value={range.id}>
                                {range.name}
                            </option>
                        ))}
                    </Select>
                    <Select name="qualification_id" defaultValue={filters.qualification_id} aria-label="Qualification">
                        <option value="">All qualifications</option>
                        {options.qualifications.map((item) => (
                            <option key={item.id} value={item.id}>
                                {item.name}
                            </option>
                        ))}
                    </Select>
                    {variant === 'shortlisted' ? (
                        <>
                            <Input name="shortlisted_from" type="date" defaultValue={filters.shortlisted_from} aria-label="Shortlisted from" />
                            <Input name="shortlisted_to" type="date" defaultValue={filters.shortlisted_to} aria-label="Shortlisted to" />
                        </>
                    ) : (
                        <>
                            <Input name="date_from" type="date" defaultValue={filters.date_from} aria-label="Applied from" />
                            <Input name="date_to" type="date" defaultValue={filters.date_to} aria-label="Applied to" />
                        </>
                    )}
                    <Button type="submit">Filter</Button>
                    {variant === 'screening' && filters.job_id ? (
                        <Button
                            type="button"
                            variant="secondary"
                            disabled={compared.length < 2}
                            onClick={() => setCompareOpen(true)}
                        >
                            Compare selected
                        </Button>
                    ) : null}
                </FilterBar>

                {compareOpen && compared.length >= 2 ? (
                    <div className="mb-6 overflow-x-auto rounded-xl border border-line bg-white p-4">
                        <div className="mb-3 flex items-center justify-between gap-3">
                            <h2 className="text-sm font-semibold text-ink">Compare candidates for this job</h2>
                            <Button variant="ghost" onClick={() => setCompareOpen(false)}>
                                Hide comparison
                            </Button>
                        </div>
                        <table className="min-w-full text-left text-sm">
                            <thead className="text-muted">
                                <tr>
                                    <th className="px-2 py-2 font-medium">Field</th>
                                    {compared.map((row) => (
                                        <th key={row.id} className="px-2 py-2 font-medium text-ink">
                                            {row.full_name}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {[
                                    ['Candidate ID', (row: AdminApplicationListItem) => row.candidate.business_id ?? '—'],
                                    ['Experience', (row: AdminApplicationListItem) => row.experience || '—'],
                                    ['Qualification', (row: AdminApplicationListItem) => row.qualification || '—'],
                                    ['Location', (row: AdminApplicationListItem) => row.current_location || '—'],
                                    ['Applied', (row: AdminApplicationListItem) => row.applied_at ?? '—'],
                                    ['Screening', (row: AdminApplicationListItem) => row.screening_decision_label],
                                    ['Resume', (row: AdminApplicationListItem) => (row.has_resume ? 'Yes' : 'No')],
                                ].map(([label, value]) => (
                                    <tr key={String(label)} className="border-t border-line">
                                        <th className="px-2 py-2 font-medium text-muted">{String(label)}</th>
                                        {compared.map((row) => (
                                            <td key={row.id} className="px-2 py-2 text-ink">
                                                {(value as (row: AdminApplicationListItem) => string)(row)}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : null}

                <DataTable
                    columns={columns}
                    rows={applications.data}
                    rowKey={(row) => row.id}
                    emptyTitle={`No ${title.toLowerCase()} match these filters.`}
                    emptyDescription="Existing applications remain available. Guest applications are included."
                    selectedKey={selected?.id ?? null}
                    onRowClick={(row) => visit({ application: String(row.id) })}
                />
                <Pagination links={applications.links} />
            </div>

            <Drawer
                open={selected !== null}
                title={selected?.business_id ?? selected?.full_name ?? title}
                size="xl"
                onClose={closeDrawer}
            >
                {selected ? (
                    <ApplicationDrawer
                        selected={selected}
                        defaultTab={defaultTab}
                        statuses={statuses}
                        interviewModes={interviewModes}
                        interviewOutcomes={interviewOutcomes}
                        rejectionReasons={rejectionReasons}
                    />
                ) : null}
            </Drawer>
            <ConfirmDialog
                open={submitId !== null}
                title="Submit this candidate to the employer?"
                description={
                    applications.data.find((row) => row.id === submitId)?.submit_blocked_reason ??
                    'This creates an employer submission for this job only. It does not select or place the candidate.'
                }
                confirmLabel="Submit"
                onConfirm={() => {
                    if (submitId !== null) {
                        router.post(`/admin/applications/${submitId}/submit`);
                    }
                    setSubmitId(null);
                }}
                onClose={() => setSubmitId(null)}
            />
        </AdminLayout>
    );
}
