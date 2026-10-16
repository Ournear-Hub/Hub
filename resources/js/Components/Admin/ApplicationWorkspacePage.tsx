import { Link, router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState, type FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import ApplicationDrawer from '@/Components/Admin/ApplicationDrawer';
import {
    Button,
    Card,
    ConfirmDialog,
    Drawer,
    Input,
    SearchBar,
    Select,
    StatusBadge,
} from '@/Components/Admin/ui';
import FilterBar from '@/Components/molecules/FilterBar';
import DataTable from '@/Components/molecules/DataTable';
import type { DataTableColumn } from '@/Components/molecules/DataTable';
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
    const label = row.match?.label ?? row.match_label ?? 'Not enough data';
    if (label === 'Not enough data' || row.match?.state === 'not_enough_data' || row.match_percent == null && !row.match?.label && !row.match_label) {
        return row.match?.percent != null ? `${row.match.percent}%` : 'Incomplete Match Data';
    }

    return label;
}

function compareCandidateLabel(row: AdminApplicationListItem): string {
    const fromFull = row.full_name?.trim();
    if (fromFull) {
        return fromFull;
    }

    const fromCandidate = row.candidate?.name?.trim();
    if (fromCandidate) {
        return fromCandidate;
    }

    return 'Guest applicant';
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
    interviewTypes = [],
    staffUsers = [],
    scorecardTemplates = [],
    rejectionReasons,
    jobSummary,
    can_create_task = false,
}: Props) {
    const [compareRows, setCompareRows] = useState<Record<number, AdminApplicationListItem>>({});
    const [compareOpen, setCompareOpen] = useState(false);
    const [submitId, setSubmitId] = useState<number | null>(null);
    const comparePanelRef = useRef<HTMLDivElement | null>(null);

    const compared = Object.keys(compareRows)
        .map((id) => applications.data.find((row) => row.id === Number(id)) ?? compareRows[Number(id)])
        .filter((row): row is AdminApplicationListItem => row != null);

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

    const toggleCompare = (row: AdminApplicationListItem) => {
        setCompareRows((current) => {
            const next = { ...current };
            if (next[row.id]) {
                delete next[row.id];
            } else {
                next[row.id] = row;
            }

            return next;
        });
    };

    const openCompare = () => {
        setCompareOpen(true);
    };

    useEffect(() => {
        if (!compareOpen || compared.length < 2) {
            return;
        }

        comparePanelRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, [compareOpen, compared.length]);

    const columns: Array<DataTableColumn<AdminApplicationListItem>> = [
        ...(variant === 'screening' && filters.job_id
            ? [
                  {
                      key: 'compare',
                      header: 'Compare',
                      render: (row: AdminApplicationListItem) => (
                          <input
                              type="checkbox"
                              checked={Boolean(compareRows[row.id])}
                              data-compare-candidate-name={compareCandidateLabel(row)}
                              aria-label={`Compare candidate ${compareCandidateLabel(row)}`}
                              onClick={(event) => event.stopPropagation()}
                              onChange={() => toggleCompare(row)}
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
            render: (row) => compareCandidateLabel(row),
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
        {
            key: 'ats_stage',
            header: 'ATS stage',
            render: (row) => <StatusBadge status={row.ats_stage_label ?? row.status} />,
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
        <AdminLayout
            title={title}
            breadcrumbs={[
                { label: 'Admin', href: '/admin' },
                { label: title },
            ]}
        >
            <div>
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
                                    value={filters.job_id ? String(filters.job_id) : ''}
                                    aria-label="Job"
                                    onChange={(event) => {
                                        setCompareRows({});
                                        setCompareOpen(false);
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
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">Experience</p>
                                        <p className="mt-1 text-sm font-medium text-ink">{recorded(jobSummary.experience)}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">Industry</p>
                                        <p className="mt-1 text-sm font-medium text-ink">{recorded(jobSummary.industry)}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wide text-muted">Salary</p>
                                        <p className="mt-1 text-sm font-medium text-ink">{recorded(jobSummary.salary)}</p>
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
                            <div className="mt-5 grid gap-4 lg:grid-cols-2">
                                <div>
                                    <p className="text-xs font-medium uppercase tracking-wide text-muted">Match distribution</p>
                                    <ul className="mt-2 grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
                                        {[
                                            ['90–100%', jobSummary.match_distribution?.['90-100'] ?? 0],
                                            ['80–89%', jobSummary.match_distribution?.['80-89'] ?? 0],
                                            ['70–79%', jobSummary.match_distribution?.['70-79'] ?? 0],
                                            ['60–69%', jobSummary.match_distribution?.['60-69'] ?? 0],
                                            ['Below 60%', jobSummary.match_distribution?.['below-60'] ?? 0],
                                            ['Incomplete', jobSummary.match_distribution?.incomplete ?? 0],
                                        ].map(([label, value]) => (
                                            <li key={label} className="rounded-md bg-surface px-3 py-2">
                                                <span className="text-muted">{label}</span>
                                                <span className="ml-2 font-semibold text-ink">{value}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                                <div className="text-sm text-ink">
                                    <p className="text-xs font-medium uppercase tracking-wide text-muted">Conversion</p>
                                    <ul className="mt-2 space-y-1">
                                        <li>
                                            {jobSummary.matched_count} / {jobSummary.screened_count ?? 0} matched
                                            {jobSummary.conversions?.matched_of_screened != null ? ` — ${jobSummary.conversions.matched_of_screened}%` : ''}
                                        </li>
                                        <li>
                                            {jobSummary.shortlisted_count} / {jobSummary.matched_count} shortlisted
                                            {jobSummary.conversions?.shortlisted_of_matched != null ? ` — ${jobSummary.conversions.shortlisted_of_matched}%` : ''}
                                        </li>
                                        <li>
                                            Selected of shortlisted
                                            {jobSummary.conversions?.selected_of_shortlisted != null ? ` — ${jobSummary.conversions.selected_of_shortlisted}%` : ' — not enough records'}
                                        </li>
                                        <li>
                                            Joined of placed
                                            {jobSummary.conversions?.joined_of_placed != null ? ` — ${jobSummary.conversions.joined_of_placed}%` : ' — not enough records'}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        ) : null}
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
                    <Select name="ats_stage" defaultValue={filters.ats_stage} aria-label="ATS stage">
                        <option value="">All ATS stages</option>
                        {(options.ats_stages ?? []).map((stage) => (
                            <option key={stage.value} value={stage.value}>
                                {stage.label}
                            </option>
                        ))}
                    </Select>
                    <Select name="job_id" defaultValue={filters.job_id ? String(filters.job_id) : ''} aria-label="Job">
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
                            <Select name="scope" defaultValue={filters.scope ?? ''} aria-label="Screening queue">
                                <option value="">All applications</option>
                                <option value="pending">Pending screening</option>
                            </Select>
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
                                <option value="90-100">90–100%</option>
                                <option value="80-89">80–89%</option>
                                <option value="70-79">70–79%</option>
                                <option value="60-69">60–69%</option>
                                <option value="below-60">Below 60%</option>
                                <option value="incomplete">Incomplete</option>
                            </Select>
                            <Input name="match_min" type="number" min={0} max={100} defaultValue={filters.match_min ?? ''} aria-label="Minimum match percent" placeholder="Min %" />
                            <Input name="match_max" type="number" min={0} max={100} defaultValue={filters.match_max ?? ''} aria-label="Maximum match percent" placeholder="Max %" />
                            <Select name="match_sort" defaultValue={filters.match_sort ?? ''} aria-label="Match sort">
                                <option value="">Sort by date</option>
                                <option value="match_desc">Highest to lowest</option>
                                <option value="match_asc">Lowest to highest</option>
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
                        <>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => visit({ job_id: filters.job_id, match_min: '80', match_range: '' })}
                            >
                                Show 80%+ Matches
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                disabled={compared.length < 2}
                                onClick={openCompare}
                            >
                                Compare selected{compared.length > 0 ? ` (${compared.length})` : ''}
                            </Button>
                        </>
                    ) : null}
                </FilterBar>

                {variant === 'screening' && filters.job_id && compared.length >= 2 && !compareOpen ? (
                    <p className="mb-4 text-sm text-muted">
                        {compared.length} candidates selected for comparison. Click <span className="font-medium text-ink">Compare selected</span> above the table filters.
                    </p>
                ) : null}

                {compareOpen && compared.length >= 2 ? (
                    <div
                        id="screening-compare-panel"
                        ref={comparePanelRef}
                        className="mb-6 overflow-x-auto rounded-xl border border-line bg-white p-4"
                    >
                        <div className="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <h2 className="text-sm font-semibold text-ink">Compare candidates for this job</h2>
                                <div className="mt-2 flex flex-col gap-1 sm:flex-row sm:flex-wrap sm:gap-x-3">
                                    {compared.map((row) => (
                                        <span key={row.id} data-compare-candidate="" className="text-sm font-medium text-ink">
                                            {compareCandidateLabel(row)}
                                        </span>
                                    ))}
                                </div>
                                <p className="mt-1 text-xs text-muted">
                                    Existing recorded fields only. Match % compares recorded fields; it does not rank, shortlist, reject, or hire. The human decision remains final.
                                </p>
                                {jobSummary ? (
                                    <p className="mt-2 text-xs text-ink">
                                        Job context:{' '}
                                        {[
                                            jobSummary.title,
                                            jobSummary.location,
                                            jobSummary.experience,
                                            jobSummary.industry,
                                            jobSummary.employment_type,
                                            jobSummary.salary ? `Salary ${jobSummary.salary}` : null,
                                            jobSummary.joining_date ? `Joining ${jobSummary.joining_date}` : null,
                                        ]
                                            .filter((value) => value && String(value).trim() !== '')
                                            .join(' · ') || 'Not recorded yet'}
                                    </p>
                                ) : null}
                            </div>
                            <Button variant="ghost" onClick={() => setCompareOpen(false)}>
                                Hide comparison
                            </Button>
                        </div>
                        <table className="min-w-full text-left text-sm">
                            <thead className="bg-surface text-muted">
                                <tr>
                                    <th className="sticky left-0 bg-surface px-3 py-2 font-medium">Field</th>
                                    {compared.map((row) => (
                                        <th key={row.id} className="min-w-40 px-3 py-2 font-medium text-ink">
                                            {compareCandidateLabel(row)}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {[
                                    ['Candidate ID', (row: AdminApplicationListItem) => recorded(row.candidate.business_id)],
                                    ['Application ID', (row: AdminApplicationListItem) => recorded(row.business_id)],
                                    ['Designation', (row: AdminApplicationListItem) => recorded(row.designation)],
                                    ['Current company', (row: AdminApplicationListItem) => recorded(row.current_company)],
                                    ['Experience', (row: AdminApplicationListItem) => recorded(row.experience)],
                                    ['Qualification', (row: AdminApplicationListItem) => recorded(row.qualification)],
                                    ['Location', (row: AdminApplicationListItem) => recorded(row.current_location)],
                                    ['Skills', (row: AdminApplicationListItem) => recorded(row.skills)],
                                    ['Industry', (row: AdminApplicationListItem) => recorded(row.industry_experience)],
                                    ['Job industry', (row: AdminApplicationListItem) => recorded(row.industry)],
                                    ['Relocation', (row: AdminApplicationListItem) => recorded(row.relocation)],
                                    ['Salary', (row: AdminApplicationListItem) => recorded(row.current_salary ?? row.expected_salary)],
                                    ['Notice period', (row: AdminApplicationListItem) => recorded(row.notice_period)],
                                    ['Match %', (row: AdminApplicationListItem) => matchLabel(row)],
                                    ['Screening status', (row: AdminApplicationListItem) => row.screening_stage_label ?? row.screening_decision_label],
                                    ['Verification status', (row: AdminApplicationListItem) => row.verification_label ?? 'Not recorded yet'],
                                    ['Remarks', (row: AdminApplicationListItem) => recorded(row.screening_notes)],
                                    ['Resume', (row: AdminApplicationListItem) => (row.has_resume ? 'Yes' : 'No')],
                                ].map(([label, value]) => (
                                    <tr key={String(label)} className="border-t border-line">
                                        <th className="sticky left-0 bg-white px-3 py-2 font-medium text-muted">{String(label)}</th>
                                        {compared.map((row) => (
                                            <td key={row.id} className="px-3 py-2 text-ink">
                                                {(value as (row: AdminApplicationListItem) => string)(row)}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : null}

                <div
                    className={selected ? 'pointer-events-none' : undefined}
                    inert={selected ? true : undefined}
                    aria-hidden={selected ? true : undefined}
                >
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
                        interviewTypes={interviewTypes}
                        staffUsers={staffUsers}
                        scorecardTemplates={scorecardTemplates}
                        rejectionReasons={rejectionReasons}
                        can_create_task={can_create_task}
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
