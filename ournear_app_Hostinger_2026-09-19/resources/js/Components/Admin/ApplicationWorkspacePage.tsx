import { Link, router } from '@inertiajs/react';
import { useCallback, useState, type FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import ApplicationDrawer from '@/Components/Admin/ApplicationDrawer';
import {
    Button,
    Card,
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

function recorded(value?: string | null): string {
    return value && value.trim() !== '' ? value : 'Not recorded yet';
}

function matchLabel(row: AdminApplicationListItem): string {
    return row.match?.label ?? row.match_label ?? 'Not enough data';
}

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
    jobSummary,
}: Props) {
    const [compareIds, setCompareIds] = useState<number[]>([]);
    const [compareOpen, setCompareOpen] = useState(false);
    const [submitId, setSubmitId] = useState<number | null>(null);

    const visit = (overrides: Record<string, string | undefined> = {}, replaceAll = false) => {
        const next = replaceAll ? overrides : { ...filters, ...overrides };
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
            render: (row) => (variant === 'screening' ? recorded(row.candidate.business_id) : row.candidate.business_id ?? '—'),
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
            key: 'employer',
            header: 'Employer',
            render: (row) => row.employer?.company_name ?? '—',
        },
        {
            key: 'applied',
            header: variant === 'shortlisted' ? 'Shortlisted' : 'Applied',
            render: (row) => (variant === 'shortlisted' ? row.shortlisted_at ?? '—' : row.applied_at ?? '—'),
        },
        { key: 'experience', header: 'Experience', render: (row) => (variant === 'screening' ? recorded(row.experience) : row.experience || '—') },
        { key: 'location', header: 'Location', render: (row) => (variant === 'screening' ? recorded(row.current_location) : row.current_location || '—') },
        { key: 'qualification', header: 'Qualification', render: (row) => (variant === 'screening' ? recorded(row.qualification) : row.qualification || '—') },
        ...(variant === 'screening'
            ? [
                  {
                      key: 'skills',
                      header: 'Skills',
                      render: (row: AdminApplicationListItem) => recorded(row.skills),
                  } satisfies DataTableColumn<AdminApplicationListItem>,
                  {
                      key: 'company',
                      header: 'Company',
                      render: (row: AdminApplicationListItem) => recorded(row.current_company),
                  } satisfies DataTableColumn<AdminApplicationListItem>,
                  {
                      key: 'designation',
                      header: 'Designation',
                      render: (row: AdminApplicationListItem) => recorded(row.designation),
                  } satisfies DataTableColumn<AdminApplicationListItem>,
                  {
                      key: 'current_salary',
                      header: 'Current salary',
                      render: (row: AdminApplicationListItem) => recorded(row.current_salary),
                  } satisfies DataTableColumn<AdminApplicationListItem>,
                  {
                      key: 'expected_salary',
                      header: 'Expected salary',
                      render: (row: AdminApplicationListItem) => recorded(row.expected_salary),
                  } satisfies DataTableColumn<AdminApplicationListItem>,
                  {
                      key: 'notice',
                      header: 'Notice period',
                      render: (row: AdminApplicationListItem) => recorded(row.notice_period),
                  } satisfies DataTableColumn<AdminApplicationListItem>,
                  {
                      key: 'industry_experience',
                      header: 'Industry experience',
                      render: (row: AdminApplicationListItem) => recorded(row.industry_experience),
                  } satisfies DataTableColumn<AdminApplicationListItem>,
                  {
                      key: 'match',
                      header: 'Match %',
                      render: (row: AdminApplicationListItem) => <StatusBadge status={matchLabel(row)} />,
                  } satisfies DataTableColumn<AdminApplicationListItem>,
                  {
                      key: 'stage',
                      header: 'Screening stage',
                      render: (row: AdminApplicationListItem) => (
                          <StatusBadge status={row.screening_stage_label ?? row.screening_decision_label} />
                      ),
                  } satisfies DataTableColumn<AdminApplicationListItem>,
                  {
                      key: 'submission_state',
                      header: 'Employer submission',
                      render: (row: AdminApplicationListItem) => (
                          <StatusBadge status={row.employer_submission_label ?? 'Has not been shortlisted'} />
                      ),
                  } satisfies DataTableColumn<AdminApplicationListItem>,
                  {
                      key: 'last_activity',
                      header: 'Last screening activity',
                      render: (row: AdminApplicationListItem) =>
                          row.last_screening_activity?.at
                              ? `${row.last_screening_activity.action} · ${row.last_screening_activity.at}`
                              : 'Not recorded yet',
                  } satisfies DataTableColumn<AdminApplicationListItem>,
              ]
            : []),
        {
            key: 'screening',
            header: 'Screening',
            render: (row) => <StatusBadge status={row.screening_decision_label} />,
        },
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

                {variant === 'screening' ? (
                    <Card className="mb-6">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div className="xl:col-span-2">
                                <label className="mb-1 block text-xs font-medium uppercase tracking-wide text-muted" htmlFor="screening-job">
                                    Select job
                                </label>
                                <Select
                                    id="screening-job"
                                    value={filters.job_id}
                                    aria-label="Job"
                                    onChange={(event) => {
                                        setCompareIds([]);
                                        visit({ job_id: event.target.value, application: '' });
                                    }}
                                >
                                    <option value="">All jobs — select a job for comparison and pipeline counts</option>
                                    {options.jobs.map((job) => (
                                        <option key={job.id} value={job.id}>
                                            {job.business_id ? `${job.business_id} · ${job.title}` : job.title}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                            {jobSummary ? (
                                <>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">Job ID</p>
                                        <p className="mt-1 text-sm font-medium text-ink">{jobSummary.business_id ?? 'Not recorded yet'}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">Employer</p>
                                        <p className="mt-1 text-sm font-medium text-ink">
                                            {jobSummary.employer?.company_name ?? 'Not recorded yet'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">Location</p>
                                        <p className="mt-1 text-sm font-medium text-ink">{recorded(jobSummary.location)}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">Vacancies</p>
                                        <p className="mt-1 text-sm font-medium text-ink">
                                            {jobSummary.vacancy ?? 'Not recorded yet'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">Applications</p>
                                        <p className="mt-1 text-sm font-medium text-ink">{jobSummary.applications_count}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">Pending screening</p>
                                        <p className="mt-1 text-sm font-medium text-ink">{jobSummary.pending_screening_count}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">Shortlisted</p>
                                        <p className="mt-1 text-sm font-medium text-ink">{jobSummary.shortlisted_count}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">Rejected</p>
                                        <p className="mt-1 text-sm font-medium text-ink">{jobSummary.rejected_count}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">On hold</p>
                                        <p className="mt-1 text-sm font-medium text-ink">{jobSummary.on_hold_count}</p>
                                    </div>
                                </>
                            ) : (
                                <p className="text-sm text-muted md:col-span-2 xl:col-span-2">
                                    Select a job to open the recruitment pipeline, compare candidates, and work through screening stages.
                                </p>
                            )}
                        </div>
                        {jobSummary ? (
                            <div className="mt-5 flex flex-wrap items-center gap-2">
                                {jobSummary.pipeline.map((stage, index) => (
                                    <span key={stage.key} className="flex items-center gap-2">
                                        <Link
                                            href={stage.href}
                                            className="inline-flex min-h-10 items-center rounded-md border border-line bg-white px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface"
                                        >
                                            {stage.label}
                                            <span className="ml-2 rounded-full bg-surface px-2 py-0.5 text-xs text-muted">{stage.value}</span>
                                        </Link>
                                        {index < jobSummary.pipeline.length - 1 ? (
                                            <span className="text-muted" aria-hidden>
                                                ↓
                                            </span>
                                        ) : null}
                                    </span>
                                ))}
                            </div>
                        ) : null}
                    </Card>
                ) : null}

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
                    <Select name="candidate" defaultValue={filters.candidate} aria-label="Candidate">
                        <option value="">All candidates</option>
                        {(options.candidates ?? []).map((candidate) => (
                            <option key={candidate.id} value={candidate.id}>
                                {candidate.label}
                            </option>
                        ))}
                    </Select>
                    <Select name="employer_id" defaultValue={filters.employer_id} aria-label="Employer">
                        <option value="">All employers</option>
                        {(options.employers ?? []).map((employer) => (
                            <option key={employer.id} value={employer.id}>
                                {employer.label}
                            </option>
                        ))}
                    </Select>
                    <Select name="screening_decision" defaultValue={filters.screening_decision} aria-label="Screening decision">
                        <option value="">All screening</option>
                        {options.screening_decisions.map((decision) => (
                            <option key={decision.value} value={decision.value}>
                                {decision.label}
                            </option>
                        ))}
                    </Select>
                    {variant === 'screening' ? (
                        <>
                            <Select name="screening_stage" defaultValue={filters.screening_stage} aria-label="Screening status">
                                <option value="">All screening stages</option>
                                {(options.screening_stages ?? []).map((stage) => (
                                    <option key={stage.value} value={stage.value}>
                                        {stage.label}
                                    </option>
                                ))}
                            </Select>
                            <Select name="match_range" defaultValue={filters.match_range} aria-label="Match range">
                                <option value="">All match scores</option>
                                <option value="0-49">0–49%</option>
                                <option value="50-74">50–74%</option>
                                <option value="75-100">75–100%</option>
                            </Select>
                            <Select name="verification_state" defaultValue={filters.verification_state} aria-label="Verification state">
                                <option value="">All verification</option>
                                <option value="verified">Verified account</option>
                                <option value="unverified">Unverified / guest</option>
                            </Select>
                        </>
                    ) : null}
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
                    <div className="flex flex-wrap gap-2 md:col-span-2">
                        <Button type="submit">Filter</Button>
                        <Button type="button" variant="secondary" onClick={() => visit({}, true)}>
                            Reset filters
                        </Button>
                    </div>
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
                                    ['Candidate ID', (row: AdminApplicationListItem) => recorded(row.candidate.business_id)],
                                    ['Application ID', (row: AdminApplicationListItem) => recorded(row.business_id)],
                                    ['Experience', (row: AdminApplicationListItem) => recorded(row.experience)],
                                    ['Qualification', (row: AdminApplicationListItem) => recorded(row.qualification)],
                                    ['Location', (row: AdminApplicationListItem) => recorded(row.current_location)],
                                    ['Skills', (row: AdminApplicationListItem) => recorded(row.skills)],
                                    ['Industry', (row: AdminApplicationListItem) => recorded(row.industry)],
                                    ['Salary', (row: AdminApplicationListItem) => recorded(row.current_salary ?? row.expected_salary)],
                                    ['Notice period', (row: AdminApplicationListItem) => recorded(row.notice_period)],
                                    ['Match %', (row: AdminApplicationListItem) => matchLabel(row)],
                                    ['Screening status', (row: AdminApplicationListItem) => row.screening_stage_label ?? row.screening_decision_label],
                                    ['Verification status', (row: AdminApplicationListItem) => row.verification_label ?? 'Not recorded yet'],
                                    ['Remarks', (row: AdminApplicationListItem) => recorded(row.screening_notes)],
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
                    emptyTitle={variant === 'screening' ? 'No applications match these screening filters.' : `No ${title.toLowerCase()} match these filters.`}
                    emptyDescription={
                        variant === 'screening'
                            ? 'Existing applications remain available. Guest applications are included. Fields that are not stored show “Not recorded yet”; they are not invented.'
                            : 'Existing applications remain available. Guest applications are included.'
                    }
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
