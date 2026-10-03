import { Link, router } from '@inertiajs/react';
import { FormEvent, useEffect, useState, type ReactNode } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatCard from '@/Components/Admin/StatCard';
import { Button, Card, DataTable, EmptyState, FilterBar, Input, Label, StatusBadge } from '@/Components/Admin/ui';
import type { DataTableColumn } from '@/Components/Admin/ui';

type Metric = {
    value: number | string | null;
    kind: 'period' | 'snapshot';
    definition: string;
};

type Conversion = {
    label: string;
    formula: string;
    rate: number | null;
    numerator: number;
    denominator: number;
    numerator_label: string;
    denominator_label: string;
    kind: string;
    empty_reason: string | null;
    definition?: string;
};

type TimeMetric = {
    average_days: number | null;
    eligible_count: number;
    kind: string;
    definition: string;
    empty_reason: string | null;
};

type NamedCount = {
    kind: string;
    definition: string;
    rows: Array<{ id: number; name: string; count: number }>;
};

type Period = {
    from: string;
    to: string;
    timezone: string;
    is_default: boolean;
    max_span_days: number;
    definition: string;
    presets: Array<{ id: string; label: string; from: string; to: string }>;
};

type Permissions = Record<string, boolean>;

type AnalyticsPageProps = {
    period: Period;
    permissions: Permissions;
    overview?: Record<string, Metric> | null;
    recruitment?: Record<string, Metric | string> | null;
    conversions?: Record<string, Conversion | string> | null;
    timeMetrics?: Record<string, TimeMetric | string> | null;
    jobs?: Record<string, unknown> | null;
    employers?: Record<string, unknown> | null;
    candidates?: Record<string, unknown> | null;
    screening?: Record<string, unknown> | null;
    interviews?: Record<string, unknown> | null;
    submissions?: Record<string, unknown> | null;
    placements?: Record<string, unknown> | null;
    replacements?: Record<string, unknown> | null;
    crm?: Record<string, unknown> | null;
    partners?: Record<string, unknown> | null;
    investors?: Record<string, unknown> | null;
    campaigns?: Record<string, unknown> | null;
    documents?: Record<string, unknown> | null;
    reviews?: Record<string, unknown> | null;
    finance?: Record<string, unknown> | null;
};

function isMetric(value: unknown): value is Metric {
    return Boolean(value) && typeof value === 'object' && 'value' in (value as object) && 'kind' in (value as object);
}

function isConversion(value: unknown): value is Conversion {
    return Boolean(value) && typeof value === 'object' && 'formula' in (value as object) && 'rate' in (value as object);
}

function isTimeMetric(value: unknown): value is TimeMetric {
    return Boolean(value) && typeof value === 'object' && 'average_days' in (value as object) && 'eligible_count' in (value as object);
}

function isNamedCount(value: unknown): value is NamedCount {
    return Boolean(value) && typeof value === 'object' && Array.isArray((value as NamedCount).rows);
}

function kindLabel(kind: string | undefined): string {
    return kind === 'snapshot' ? 'Current snapshot' : 'Selected period';
}

function formatRate(rate: number | null): string | null {
    return rate === null ? null : `${rate}%`;
}

function MetricCard({ metric, label }: { metric: Metric; label: string }) {
    return (
        <StatCard
            label={label}
            value={metric.value}
            hint={kindLabel(metric.kind)}
            emptyHint="No data for selected period"
        />
    );
}

function ConversionCard({ conversion }: { conversion: Conversion }) {
    return (
        <article className="rounded-xl border border-line bg-white p-4 sm:p-5">
            <p className="text-sm text-muted">{conversion.label}</p>
            <p className="mt-2 text-2xl font-semibold text-ink">{formatRate(conversion.rate) ?? '—'}</p>
            <p className="mt-1 text-xs text-muted">{conversion.formula}</p>
            <p className="mt-1 text-xs text-muted">
                {conversion.numerator_label}: {conversion.numerator} · {conversion.denominator_label}: {conversion.denominator}
            </p>
            {conversion.rate === null ? (
                <p className="mt-1 text-xs text-disabled">{conversion.empty_reason ?? 'No data for selected period'}</p>
            ) : (
                <p className="mt-1 text-xs text-muted">{kindLabel(conversion.kind)}</p>
            )}
        </article>
    );
}

function TimeCard({ metric, label }: { metric: TimeMetric; label: string }) {
    const empty = metric.eligible_count === 0 || metric.average_days === null;

    return (
        <article className="rounded-xl border border-line bg-white p-4 sm:p-5">
            <p className="text-sm text-muted">{label}</p>
            <p className="mt-2 text-2xl font-semibold text-ink">{empty ? '—' : `${metric.average_days} days`}</p>
            <p className="mt-1 text-xs text-muted">Eligible records: {metric.eligible_count}</p>
            {empty ? (
                <p className="mt-1 text-xs text-disabled">{metric.empty_reason ?? 'No data for selected period'}</p>
            ) : (
                <p className="mt-1 text-xs text-muted">{kindLabel(metric.kind)}</p>
            )}
        </article>
    );
}

function Section({ title, note, children }: { title: string; note?: string; children: ReactNode }) {
    return (
        <section className="space-y-3">
            <div>
                <h2 className="text-base font-semibold text-ink">{title}</h2>
                {note ? <p className="mt-1 text-sm text-muted">{note}</p> : null}
            </div>
            {children}
        </section>
    );
}

function NamedTable({ named, emptyTitle }: { named: NamedCount; emptyTitle: string }) {
    const columns: Array<DataTableColumn<NamedCount['rows'][number]>> = [
        { key: 'name', header: 'Name', render: (row) => row.name },
        { key: 'count', header: 'Count', render: (row) => row.count },
    ];

    return (
        <div>
            <p className="mb-2 text-xs text-muted">
                {kindLabel(named.kind)} · {named.definition}
            </p>
            <DataTable
                columns={columns}
                rows={named.rows}
                rowKey={(row) => row.id}
                emptyTitle={emptyTitle}
                emptyDescription="No data for selected period"
            />
        </div>
    );
}

function SnapshotGrid({ title, values }: { title: string; values: Array<{ label: string; value: number | string | null }> }) {
    return (
        <div>
            <div className="mb-2 flex items-center gap-2">
                <h3 className="text-sm font-semibold text-ink">{title}</h3>
                <StatusBadge status="Current snapshot" />
            </div>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {values.map((item) => (
                    <StatCard key={item.label} label={item.label} value={item.value} hint="Current snapshot" emptyHint="—" />
                ))}
            </div>
        </div>
    );
}

function RatingBars({ rows }: { rows: Array<{ rating: number; count: number }> }) {
    const max = Math.max(1, ...rows.map((row) => row.count));

    if (rows.every((row) => row.count === 0)) {
        return <EmptyState title="No data for selected period" description="Ratings appear after reviews are submitted in this period." />;
    }

    return (
        <ul className="space-y-2">
            {rows.map((row) => (
                <li key={row.rating}>
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-ink">{row.rating} star</span>
                        <span className="font-semibold text-ink">{row.count}</span>
                    </div>
                    <div className="mt-1 h-2 overflow-hidden rounded-full bg-surface" aria-hidden>
                        <div className="h-full rounded-full bg-brand" style={{ width: `${Math.round((row.count / max) * 100)}%` }} />
                    </div>
                </li>
            ))}
        </ul>
    );
}

export default function AnalyticsIndex(props: AnalyticsPageProps) {
    const { period, permissions } = props;
    const [from, setFrom] = useState(period.from);
    const [to, setTo] = useState(period.to);

    useEffect(() => {
        setFrom(period.from);
        setTo(period.to);
    }, [period.from, period.to]);

    const apply = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get('/admin/analytics', { from, to }, { preserveState: true, preserveScroll: true });
    };

    const reset = () => {
        router.get('/admin/analytics', {}, { preserveState: false });
    };

    const applyPreset = (preset: Period['presets'][number]) => {
        setFrom(preset.from);
        setTo(preset.to);
        router.get('/admin/analytics', { from: preset.from, to: preset.to }, { preserveState: true, preserveScroll: true });
    };

    const hasAnySection = [
        props.overview,
        props.recruitment,
        props.conversions,
        props.timeMetrics,
        props.jobs,
        props.employers,
        props.candidates,
        props.screening,
        props.interviews,
        props.submissions,
        props.placements,
        props.replacements,
        props.crm,
        props.partners,
        props.investors,
        props.campaigns,
        props.documents,
        props.reviews,
        props.finance,
    ].some(Boolean);

    const jobs = props.jobs as Record<string, unknown> | undefined;
    const employers = props.employers as Record<string, unknown> | undefined;
    const candidates = props.candidates as Record<string, unknown> | undefined;
    const screening = props.screening as Record<string, unknown> | undefined;
    const interviews = props.interviews as Record<string, unknown> | undefined;
    const submissions = props.submissions as Record<string, unknown> | undefined;
    const placements = props.placements as Record<string, unknown> | undefined;
    const replacements = props.replacements as Record<string, unknown> | undefined;
    const crm = props.crm as Record<string, unknown> | undefined;
    const partners = props.partners as Record<string, unknown> | undefined;
    const investors = props.investors as Record<string, unknown> | undefined;
    const campaigns = props.campaigns as Record<string, unknown> | undefined;
    const documents = props.documents as Record<string, unknown> | undefined;
    const reviews = props.reviews as Record<string, unknown> | undefined;
    const finance = props.finance as Record<string, unknown> | undefined;

    return (
        <AdminLayout title="Analytics" breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Analytics' }]}>
            <div className="space-y-8">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Operational analytics</h1>
                    <p className="mt-1 text-sm text-muted">
                        Date-bounded aggregates from existing records. This is not a single-cohort hiring funnel. Match % is not used as a hiring KPI.
                    </p>
                    <p className="mt-1 text-xs text-muted">
                        {period.definition} Timezone: {period.timezone}.
                    </p>
                </div>

                <FilterBar onSubmit={apply} className="md:grid-cols-6">
                    <div>
                        <Label htmlFor="analytics-from">From</Label>
                        <Input id="analytics-from" type="date" value={from} onChange={(event) => setFrom(event.target.value)} required />
                    </div>
                    <div>
                        <Label htmlFor="analytics-to">To</Label>
                        <Input id="analytics-to" type="date" value={to} onChange={(event) => setTo(event.target.value)} required />
                    </div>
                    <div className="flex items-end gap-2">
                        <Button type="submit">Apply</Button>
                        <Button type="button" variant="secondary" onClick={reset}>
                            Reset
                        </Button>
                    </div>
                    <div className="flex flex-wrap items-end gap-2 md:col-span-3">
                        {period.presets.map((preset) => (
                            <Button key={preset.id} type="button" variant="ghost" onClick={() => applyPreset(preset)}>
                                {preset.label}
                            </Button>
                        ))}
                    </div>
                </FilterBar>

                {!hasAnySection ? (
                    <Card>
                        <EmptyState
                            title="No authorized analytics domains"
                            description="Metrics appear for areas you can already view. No new analytics permission is required."
                        />
                    </Card>
                ) : null}

                {props.overview ? (
                    <Section title="Overview" note="Period counts for the selected dates.">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
                            {isMetric(props.overview.applications) ? <MetricCard label="Applications" metric={props.overview.applications} /> : null}
                            {isMetric(props.overview.submissions) ? <MetricCard label="Submissions" metric={props.overview.submissions} /> : null}
                            {isMetric(props.overview.selected) ? <MetricCard label="Selected" metric={props.overview.selected} /> : null}
                            {isMetric(props.overview.placements) ? <MetricCard label="Placements" metric={props.overview.placements} /> : null}
                            {isMetric(props.overview.joined_employees) ? (
                                <MetricCard label="Joined employees" metric={props.overview.joined_employees} />
                            ) : null}
                            {isMetric(props.overview.replacements) ? <MetricCard label="Replacements" metric={props.overview.replacements} /> : null}
                        </div>
                    </Section>
                ) : null}

                {props.conversions ? (
                    <Section title="Recruitment conversion" note={typeof props.conversions.note === 'string' ? props.conversions.note : undefined}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {isConversion(props.conversions.submission_to_selection) ? (
                                <ConversionCard conversion={props.conversions.submission_to_selection} />
                            ) : null}
                            {isConversion(props.conversions.placement_to_join) ? (
                                <ConversionCard conversion={props.conversions.placement_to_join} />
                            ) : null}
                        </div>
                    </Section>
                ) : null}

                {props.timeMetrics ? (
                    <Section title="Time metrics" note={typeof props.timeMetrics.note === 'string' ? props.timeMetrics.note : undefined}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {isTimeMetric(props.timeMetrics.time_to_join) ? (
                                <TimeCard label="Application → Join" metric={props.timeMetrics.time_to_join} />
                            ) : null}
                            {isTimeMetric(props.timeMetrics.time_to_submission) ? (
                                <TimeCard label="Application → Submission" metric={props.timeMetrics.time_to_submission} />
                            ) : null}
                            {isTimeMetric(props.timeMetrics.time_to_interview) ? (
                                <TimeCard label="Application → First interview" metric={props.timeMetrics.time_to_interview} />
                            ) : null}
                            {isTimeMetric(props.timeMetrics.time_to_selection) ? (
                                <TimeCard label="Application → Selection" metric={props.timeMetrics.time_to_selection} />
                            ) : null}
                            {isTimeMetric(props.timeMetrics.time_to_placement) ? (
                                <TimeCard label="Application → Placement" metric={props.timeMetrics.time_to_placement} />
                            ) : null}
                        </div>
                    </Section>
                ) : null}

                {jobs ? (
                    <Section title="Jobs">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {isMetric(jobs.created) ? <MetricCard label="Jobs created" metric={jobs.created} /> : null}
                        </div>
                        {jobs.snapshot && typeof jobs.snapshot === 'object' ? (
                            <SnapshotGrid
                                title="Job status"
                                values={[
                                    { label: 'Active', value: Number((jobs.snapshot as Record<string, unknown>).active ?? 0) },
                                    { label: 'Published', value: Number((jobs.snapshot as Record<string, unknown>).published ?? 0) },
                                    { label: 'Draft', value: Number((jobs.snapshot as Record<string, unknown>).draft ?? 0) },
                                    { label: 'Paused', value: Number((jobs.snapshot as Record<string, unknown>).paused ?? 0) },
                                    { label: 'Closed', value: Number((jobs.snapshot as Record<string, unknown>).closed ?? 0) },
                                    { label: 'Archived', value: Number((jobs.snapshot as Record<string, unknown>).archived ?? 0) },
                                ]}
                            />
                        ) : null}
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            {isNamedCount(jobs.applications_per_job) ? (
                                <NamedTable named={jobs.applications_per_job} emptyTitle="No applications in this period" />
                            ) : null}
                            {isNamedCount(jobs.submissions_per_job) ? (
                                <NamedTable named={jobs.submissions_per_job} emptyTitle="No submissions in this period" />
                            ) : null}
                            {isNamedCount(jobs.placements_per_job) ? (
                                <NamedTable named={jobs.placements_per_job} emptyTitle="No placements in this period" />
                            ) : null}
                            {isNamedCount(jobs.joins_per_job) ? (
                                <NamedTable named={jobs.joins_per_job} emptyTitle="No joins in this period" />
                            ) : null}
                        </div>
                    </Section>
                ) : null}

                {screening ? (
                    <Section title="Screening" note={typeof screening.note === 'string' ? screening.note : undefined}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                            {isMetric(screening.volume) ? <MetricCard label="Screening volume" metric={screening.volume} /> : null}
                            {isMetric(screening.pending) ? <MetricCard label="Pending" metric={screening.pending} /> : null}
                            {isMetric(screening.suitable) ? <MetricCard label="Suitable" metric={screening.suitable} /> : null}
                            {isMetric(screening.not_suitable) ? <MetricCard label="Not suitable" metric={screening.not_suitable} /> : null}
                            {isMetric(screening.shortlisted) ? <MetricCard label="Shortlisted" metric={screening.shortlisted} /> : null}
                        </div>
                        {isTimeMetric(screening.turnaround) ? (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <TimeCard label="Screening turnaround" metric={screening.turnaround} />
                            </div>
                        ) : null}
                        {isNamedCount(screening.screened_by) ? (
                            <NamedTable named={screening.screened_by} emptyTitle="No screening workload in this period" />
                        ) : null}
                    </Section>
                ) : null}

                {interviews ? (
                    <Section title="Interviews" note={typeof interviews.note === 'string' ? interviews.note : undefined}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {isMetric(interviews.scheduled) ? <MetricCard label="Scheduled" metric={interviews.scheduled} /> : null}
                            {isMetric(interviews.confirmed) ? <MetricCard label="Confirmed" metric={interviews.confirmed} /> : null}
                            {isMetric(interviews.cancelled) ? <MetricCard label="Cancelled" metric={interviews.cancelled} /> : null}
                        </div>
                        {interviews.outcomes && typeof interviews.outcomes === 'object' ? (
                            <SnapshotGrid
                                title="Outcomes for interviews scheduled in period"
                                values={[
                                    { label: 'Pending', value: Number((interviews.outcomes as Record<string, unknown>).pending ?? 0) },
                                    { label: 'Attended', value: Number((interviews.outcomes as Record<string, unknown>).attended ?? 0) },
                                    { label: 'No-show', value: Number((interviews.outcomes as Record<string, unknown>).no_show ?? 0) },
                                    { label: 'Passed', value: Number((interviews.outcomes as Record<string, unknown>).passed ?? 0) },
                                    { label: 'Failed', value: Number((interviews.outcomes as Record<string, unknown>).failed ?? 0) },
                                ]}
                            />
                        ) : null}
                        {interviews.scorecards && typeof interviews.scorecards === 'object' ? (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {isMetric((interviews.scorecards as Record<string, unknown>).submitted) ? (
                                    <MetricCard label="Scorecards submitted" metric={(interviews.scorecards as Record<string, unknown>).submitted as Metric} />
                                ) : null}
                                {isMetric((interviews.scorecards as Record<string, unknown>).pending) ? (
                                    <MetricCard label="Scorecards pending" metric={(interviews.scorecards as Record<string, unknown>).pending as Metric} />
                                ) : null}
                            </div>
                        ) : null}
                        {isNamedCount(interviews.interviewer_workload) ? (
                            <NamedTable named={interviews.interviewer_workload} emptyTitle="No interviewer workload in this period" />
                        ) : null}
                    </Section>
                ) : null}

                {submissions ? (
                    <Section title="Submissions" note={typeof submissions.note === 'string' ? submissions.note : undefined}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                            {isMetric(submissions.submitted) ? <MetricCard label="Submitted" metric={submissions.submitted} /> : null}
                            {isMetric(submissions.under_review) ? <MetricCard label="Under review" metric={submissions.under_review} /> : null}
                            {isMetric(submissions.selected) ? <MetricCard label="Selected" metric={submissions.selected} /> : null}
                            {isMetric(submissions.rejected) ? <MetricCard label="Rejected" metric={submissions.rejected} /> : null}
                            {isMetric(submissions.on_hold) ? <MetricCard label="On hold" metric={submissions.on_hold} /> : null}
                        </div>
                        {isConversion(submissions.decision_rate) ? (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <ConversionCard conversion={submissions.decision_rate} />
                            </div>
                        ) : null}
                    </Section>
                ) : null}

                {placements ? (
                    <Section title="Placements / Employees" note={typeof placements.joining_conversion_note === 'string' ? placements.joining_conversion_note : undefined}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            {isMetric(placements.count) ? <MetricCard label="Placements" metric={placements.count} /> : null}
                            {isMetric(placements.joined) ? <MetricCard label="Joined" metric={placements.joined} /> : null}
                        </div>
                        {isConversion(placements.joining_conversion) ? (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <ConversionCard conversion={placements.joining_conversion} />
                            </div>
                        ) : null}
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            {isNamedCount(placements.joins_by_employer) ? (
                                <NamedTable named={placements.joins_by_employer} emptyTitle="No joins by employer" />
                            ) : null}
                            {isNamedCount(placements.joins_by_job) ? (
                                <NamedTable named={placements.joins_by_job} emptyTitle="No joins by job" />
                            ) : null}
                        </div>
                    </Section>
                ) : null}

                {replacements ? (
                    <Section title="Replacements">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {isMetric(replacements.cases) ? <MetricCard label="Cases" metric={replacements.cases} /> : null}
                            {isMetric(replacements.completed) ? <MetricCard label="Completed" metric={replacements.completed} /> : null}
                            {isMetric(replacements.cancelled) ? <MetricCard label="Cancelled" metric={replacements.cancelled} /> : null}
                        </div>
                        {isConversion(replacements.replacement_rate) ? (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <ConversionCard conversion={replacements.replacement_rate} />
                            </div>
                        ) : null}
                        {isTimeMetric(replacements.turnaround) ? (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <TimeCard label="Average turnaround" metric={replacements.turnaround} />
                            </div>
                        ) : null}
                    </Section>
                ) : null}

                {employers ? (
                    <Section title="Employers">
                        {employers.snapshot && typeof employers.snapshot === 'object' ? (
                            <SnapshotGrid
                                title="Employer snapshot"
                                values={[
                                    { label: 'Total employers', value: Number((employers.snapshot as Record<string, unknown>).total ?? 0) },
                                    { label: 'Verified', value: Number((employers.snapshot as Record<string, unknown>).verified ?? 0) },
                                    { label: 'Restricted', value: Number((employers.snapshot as Record<string, unknown>).restricted ?? 0) },
                                ]}
                            />
                        ) : null}
                        {isMetric(employers.created) ? (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <MetricCard label="Employers created" metric={employers.created} />
                            </div>
                        ) : null}
                    </Section>
                ) : null}

                {candidates ? (
                    <Section title="Candidates" note="Aggregate counts only. Contact details, documents, salary, and notes are not included.">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {isMetric(candidates.registrations) ? <MetricCard label="Registrations" metric={candidates.registrations} /> : null}
                            {isMetric(candidates.applications) ? <MetricCard label="Applications" metric={candidates.applications} /> : null}
                            {isMetric(candidates.placements) ? <MetricCard label="Placements" metric={candidates.placements} /> : null}
                            {isMetric(candidates.joined) ? <MetricCard label="Joined" metric={candidates.joined} /> : null}
                        </div>
                    </Section>
                ) : null}

                {crm ? (
                    <Section title="CRM">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {isMetric(crm.leads) ? <MetricCard label="Leads" metric={crm.leads} /> : null}
                            {isMetric(crm.opportunities) ? <MetricCard label="Opportunities" metric={crm.opportunities} /> : null}
                            {isMetric(crm.active_opportunities) ? <MetricCard label="Active opportunities" metric={crm.active_opportunities} /> : null}
                            {isMetric(crm.converted_opportunities) ? (
                                <MetricCard label="Converted opportunities" metric={crm.converted_opportunities} />
                            ) : null}
                            {isMetric(crm.overdue_tasks) ? <MetricCard label="Overdue CRM tasks" metric={crm.overdue_tasks} /> : null}
                        </div>
                    </Section>
                ) : null}

                {partners ? (
                    <Section title="Partners" note={typeof partners.note === 'string' ? partners.note : undefined}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {isMetric(partners.partners) ? <MetricCard label="Partners created" metric={partners.partners} /> : null}
                            {isMetric(partners.attributions) ? <MetricCard label="Attributions" metric={partners.attributions} /> : null}
                            {isMetric(partners.confirmed_attributions) ? (
                                <MetricCard label="Confirmed attributions" metric={partners.confirmed_attributions} />
                            ) : null}
                            {isMetric(partners.partner_generated_employers) ? (
                                <MetricCard label="Partner-generated employers" metric={partners.partner_generated_employers} />
                            ) : null}
                        </div>
                    </Section>
                ) : null}

                {investors ? (
                    <Section title="Investors">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {isMetric(investors.investors) ? <MetricCard label="Investors created" metric={investors.investors} /> : null}
                            {isMetric(investors.opportunities) ? <MetricCard label="Opportunities" metric={investors.opportunities} /> : null}
                        </div>
                        {investors.indicated_amount && typeof investors.indicated_amount === 'object' ? (
                            <StatCard
                                label="Indicated amount"
                                value={String((investors.indicated_amount as Record<string, unknown>).value ?? '0.00')}
                                hint="Non-binding indicated intent. Not invested capital, revenue, or capital raised."
                            />
                        ) : null}
                    </Section>
                ) : null}

                {campaigns ? (
                    <Section title="Campaigns / Referrals" note={typeof campaigns.note === 'string' ? campaigns.note : undefined}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                            {isMetric(campaigns.campaigns) ? <MetricCard label="Campaigns created" metric={campaigns.campaigns} /> : null}
                            {isMetric(campaigns.active) ? <MetricCard label="Active campaigns" metric={campaigns.active} /> : null}
                            {isMetric(campaigns.share_events) ? <MetricCard label="Share events" metric={campaigns.share_events} /> : null}
                            {isMetric(campaigns.registrations) ? <MetricCard label="Registrations" metric={campaigns.registrations} /> : null}
                            {isMetric(campaigns.applications) ? <MetricCard label="Applications" metric={campaigns.applications} /> : null}
                        </div>
                    </Section>
                ) : null}

                {documents ? (
                    <Section title="Employer documents">
                        {documents.snapshot && typeof documents.snapshot === 'object' ? (
                            <SnapshotGrid
                                title="Document snapshot"
                                values={[
                                    { label: 'Total', value: Number((documents.snapshot as Record<string, unknown>).total ?? 0) },
                                    { label: 'Active', value: Number((documents.snapshot as Record<string, unknown>).active ?? 0) },
                                    { label: 'Archived', value: Number((documents.snapshot as Record<string, unknown>).archived ?? 0) },
                                    { label: 'Unverified', value: Number((documents.snapshot as Record<string, unknown>).unverified ?? 0) },
                                    { label: 'Pending', value: Number((documents.snapshot as Record<string, unknown>).pending ?? 0) },
                                    { label: 'Verified', value: Number((documents.snapshot as Record<string, unknown>).verified ?? 0) },
                                    { label: 'Rejected', value: Number((documents.snapshot as Record<string, unknown>).rejected ?? 0) },
                                    { label: 'Expired', value: Number((documents.snapshot as Record<string, unknown>).expired ?? 0) },
                                    { label: 'Expiring soon', value: Number((documents.snapshot as Record<string, unknown>).expiring_soon ?? 0) },
                                ]}
                            />
                        ) : null}
                    </Section>
                ) : null}

                {reviews ? (
                    <Section title="Relationship reviews" note={typeof reviews.note === 'string' ? reviews.note : undefined}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {isMetric(reviews.total) ? <MetricCard label="Reviews" metric={reviews.total} /> : null}
                            {isMetric(reviews.employer_to_worker) ? (
                                <MetricCard label="Employer → Worker" metric={reviews.employer_to_worker} />
                            ) : null}
                            {isMetric(reviews.worker_to_employer) ? (
                                <MetricCard label="Worker → Employer" metric={reviews.worker_to_employer} />
                            ) : null}
                            {isMetric(reviews.hidden) ? <MetricCard label="Hidden" metric={reviews.hidden} /> : null}
                        </div>
                        {reviews.average_rating && typeof reviews.average_rating === 'object' ? (
                            <StatCard
                                label="Average rating"
                                value={(reviews.average_rating as { value: number | null }).value}
                                hint="Selected period"
                                emptyHint="No data for selected period"
                            />
                        ) : null}
                        {reviews.rating_distribution && typeof reviews.rating_distribution === 'object' ? (
                            <Card>
                                <h3 className="mb-3 text-sm font-semibold text-ink">Rating distribution</h3>
                                <RatingBars rows={((reviews.rating_distribution as { rows?: Array<{ rating: number; count: number }> }).rows ?? [])} />
                            </Card>
                        ) : null}
                    </Section>
                ) : null}

                {finance ? (
                    <Section title="Finance" note={typeof finance.note === 'string' ? finance.note : undefined}>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {finance.billed_amount && typeof finance.billed_amount === 'object' ? (
                                <StatCard
                                    label="Billed amount"
                                    value={String((finance.billed_amount as Record<string, unknown>).value ?? '0.00')}
                                    hint="Selected period"
                                />
                            ) : null}
                            {finance.received_amount && typeof finance.received_amount === 'object' ? (
                                <StatCard
                                    label="Received amount"
                                    value={String((finance.received_amount as Record<string, unknown>).value ?? '0.00')}
                                    hint="Selected period"
                                />
                            ) : null}
                            {finance.outstanding_amount && typeof finance.outstanding_amount === 'object' ? (
                                <StatCard
                                    label="Outstanding amount"
                                    value={String((finance.outstanding_amount as Record<string, unknown>).value ?? '0.00')}
                                    hint="Current snapshot"
                                />
                            ) : null}
                            {finance.overdue_amount && typeof finance.overdue_amount === 'object' ? (
                                <StatCard
                                    label="Overdue amount"
                                    value={String((finance.overdue_amount as Record<string, unknown>).value ?? '0.00')}
                                    hint="Current snapshot"
                                />
                            ) : null}
                            {isMetric(finance.invoice_count) ? <MetricCard label="Invoices" metric={finance.invoice_count} /> : null}
                            {isMetric(finance.payment_count) ? <MetricCard label="Payments" metric={finance.payment_count} /> : null}
                        </div>
                    </Section>
                ) : null}

                {!permissions.finance || !permissions.employers || !permissions.campaigns || !permissions.investors ? (
                    <p className="text-xs text-muted">
                        Sections you cannot view are omitted from this page. Missing domain view permission never returns those aggregates.
                    </p>
                ) : null}

                <p className="text-xs text-muted">
                    <Link href="/admin" className="font-medium text-brand hover:underline">
                        Back to Dashboard
                    </Link>
                </p>
            </div>
        </AdminLayout>
    );
}
