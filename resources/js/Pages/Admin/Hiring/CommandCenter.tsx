import type { FormEvent } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@/Components/Public/Button';
import UiCard from '@/Components/UI/Card';
import type { SharedPageProps } from '@/types/page';
import StatCard from '@/Components/Admin/StatCard';
import { Card, EmptyState, FilterBar, Label, Select } from '@/Components/Admin/ui';
import type {
    HiringAttentionGroup,
    HiringCommandCenterProps,
    HiringMetricCard,
    HiringQueueItem,
} from '@/types/hiring';

const lines: Array<{ value: string; label: string }> = [
    { value: 'all', label: 'All lines' },
    { value: 'ats', label: 'Direct Hiring / ATS' },
    { value: 'partnership', label: 'Recruitment Partnership' },
    { value: 'staffing', label: 'Staffing' },
    { value: 'jobs', label: 'OurNear Jobs' },
];

function MetricGrid({ cards }: { cards: HiringMetricCard[] }) {
    if (cards.length === 0) {
        return <EmptyState title="No metrics for this module" description="Your role does not include these counts." />;
    }

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {cards.map((card) => (
                <StatCard
                    key={card.label}
                    label={card.label}
                    value={card.value}
                    href={card.href}
                    hint={card.value === 0 ? card.empty : null}
                    emptyHint={card.empty}
                />
            ))}
        </div>
    );
}

function QueueList({ items, empty }: { items: HiringQueueItem[]; empty: string }) {
    if (items.length === 0) {
        return <EmptyState title={empty} />;
    }

    return (
        <ul className="divide-y divide-line">
            {items.map((item) => (
                <li key={`${item.href}-${item.title}`}>
                    <Link href={item.href} className="block px-1 py-3 hover:bg-canvas/80">
                        <p className="text-sm font-medium text-ink">{item.title}</p>
                        {item.subtitle ? <p className="mt-0.5 text-xs text-muted">{item.subtitle}</p> : null}
                    </Link>
                </li>
            ))}
        </ul>
    );
}

function staffGreeting(): string {
    const hour = new Date().getHours();
    if (hour < 12) {
        return 'Good morning';
    }
    if (hour < 17) {
        return 'Good afternoon';
    }
    return 'Good evening';
}

function staffFirstName(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    return parts[0] ?? name;
}

function Attention({ groups }: { groups: HiringAttentionGroup[] }) {
    if (groups.length === 0) {
        return (
            <EmptyState
                title="No attention queues for this view"
                description="Choose another product line or a role that can see hiring work."
            />
        );
    }

    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {groups.map((group) => (
                <Card key={group.key}>
                    <div className="mb-3 flex items-center justify-between gap-2">
                        <h3 className="text-sm font-semibold text-ink">{group.label}</h3>
                        <Link href={group.href} className="text-xs font-medium text-brand hover:underline">
                            Open workspace
                        </Link>
                    </div>
                    <QueueList items={group.items} empty={group.empty} />
                </Card>
            ))}
        </div>
    );
}

export default function CommandCenter({
    generated_at,
    filters,
    job_options,
    permissions,
    ownership_note,
    ats,
    partnership,
    staffing,
    jobs_board,
    attention,
    activity,
    automation,
    workload,
    links,
}: HiringCommandCenterProps) {
    const pageProps = usePage<SharedPageProps>().props;
    const adminUser = pageProps.auth.admin ?? pageProps.auth.user;
    const staffName = adminUser?.name ?? 'Recruiter';
    const actionQueues = attention.filter((group) => group.count > 0 || group.items.length > 0);
    const primaryQueue = actionQueues[0] ?? null;
    const summaryMetrics = [...(ats?.metrics ?? []), ...(jobs_board?.metrics ?? [])].slice(0, 4);
    const recentJobs = job_options.slice(0, 5);
    const recentActivity = activity.slice(0, 5);

    const adminModules = [
        {
            key: 'ats',
            title: 'Direct Hiring / ATS',
            description: 'Manage your own hiring pipeline.',
            href: links.applications,
            visible: permissions.ats,
        },
        {
            key: 'partnership',
            title: 'Recruitment Partnership',
            description: 'Work with OurNear recruitment support.',
            href: links.submissions,
            visible: permissions.partnership,
        },
        {
            key: 'staffing',
            title: 'Staffing Services',
            description: 'Request and manage manpower requirements.',
            href: links.placements,
            visible: permissions.staffing,
        },
        {
            key: 'jobs',
            title: 'OurNear Jobs',
            description: 'Access job publishing and candidate acquisition.',
            href: links.jobs,
            visible: permissions.jobs,
        },
    ].filter((module) => module.visible);

    const applyFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        const line = String(form.get('line') ?? 'all');
        const jobId = String(form.get('job_id') ?? '');
        router.get(
            '/admin/hiring',
            {
                line: line === 'all' ? undefined : line,
                job_id: jobId || undefined,
            },
            { preserveScroll: true, replace: true },
        );
    };

    return (
        <AdminLayout
            title="Hiring Command Center"
            breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Hiring Command Center' }]}
        >
            <div className="mx-auto max-w-5xl space-y-6 lg:max-w-none lg:space-y-8">
                <header className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <p className="text-sm text-muted">
                        {staffGreeting()}, {staffFirstName(staffName)}
                    </p>
                    <h1 className="mt-1 text-xl font-semibold text-ink sm:text-2xl">Hiring Command Center</h1>
                    <p className="mt-2 max-w-3xl text-sm text-muted">
                        Manage jobs, candidates, and hiring activity from one workspace. Screening match and shortlist stay on
                        existing workflows—human decisions are not automated.
                    </p>
                    <p className="mt-2 text-xs text-muted">Updated {generated_at}</p>
                    <div className="mt-3 flex flex-wrap gap-2">
                        <Link href={links.work} className="text-sm font-semibold text-brand hover:underline">
                            My Work
                        </Link>
                        {permissions.ats ? (
                            <Link href={links.screening} className="text-sm font-semibold text-brand hover:underline">
                                Screening
                            </Link>
                        ) : null}
                    </div>
                </header>

                <FilterBar onSubmit={applyFilters} className="md:grid-cols-3">
                    <div>
                        <Label htmlFor="line">Product line</Label>
                        <Select id="line" name="line" defaultValue={filters.line}>
                            {lines.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </Select>
                    </div>
                    <div>
                        <Label htmlFor="job_id">Job / requisition</Label>
                        <Select id="job_id" name="job_id" defaultValue={filters.job_id}>
                            <option value="">All jobs</option>
                            {job_options.map((job) => (
                                <option key={job.id} value={String(job.id)}>
                                    {job.title}
                                </option>
                            ))}
                        </Select>
                    </div>
                    <div className="flex items-end">
                        <button type="submit" className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white">
                            Apply filters
                        </button>
                    </div>
                </FilterBar>

                {(workload.open_tasks !== null || workload.overdue_tasks !== null) && (
                    <p className="text-sm text-muted">
                        Your assigned open tasks: {workload.open_tasks ?? '—'}
                        {workload.overdue_tasks !== null ? ` · overdue ${workload.overdue_tasks}` : ''}
                        . {ownership_note}
                    </p>
                )}

                <section
                    className={
                        primaryQueue
                            ? 'rounded-xl border-2 border-accent/30 bg-accent/5 p-5 sm:p-6'
                            : 'rounded-xl border border-line bg-white p-5 sm:p-6'
                    }
                    aria-labelledby="admin-hiring-action-required"
                >
                    <p className="text-xs font-semibold uppercase tracking-wide text-accent">Action required</p>
                    {primaryQueue ? (
                        <>
                            <h2 id="admin-hiring-action-required" className="mt-2 text-lg font-semibold text-ink">
                                {primaryQueue.label}
                                {primaryQueue.count > 0 ? ` (${primaryQueue.count})` : ''}
                            </h2>
                            <ul className="mt-3 space-y-2">
                                {primaryQueue.items.slice(0, 3).map((item) => (
                                    <li key={`${item.href}-${item.title}`} className="text-sm">
                                        <Link href={item.href} className="font-medium text-brand hover:underline">
                                            {item.title}
                                        </Link>
                                        {item.subtitle ? <p className="text-muted">{item.subtitle}</p> : null}
                                    </li>
                                ))}
                            </ul>
                            <Button href={primaryQueue.href} variant="primary" className="mt-4 min-h-11 w-full sm:w-auto">
                                Open workspace
                            </Button>
                        </>
                    ) : (
                        <>
                            <h2 id="admin-hiring-action-required" className="mt-2 text-lg font-semibold text-ink">
                                Hiring is up to date
                            </h2>
                            <p className="mt-2 text-sm text-muted">No urgent queues need attention for this filter.</p>
                            {permissions.jobs ? (
                                <Button href={links.jobs} variant="secondary" className="mt-4 min-h-11 w-full sm:w-auto">
                                    View jobs
                                </Button>
                            ) : null}
                        </>
                    )}
                </section>

                {summaryMetrics.length > 0 ? (
                    <section aria-labelledby="hiring-summary">
                        <h2 id="hiring-summary" className="text-base font-semibold text-ink">
                            Hiring summary
                        </h2>
                        <div className="mt-3 grid grid-cols-2 gap-3 md:grid-cols-4">
                            {summaryMetrics.map((card) => (
                                <UiCard key={card.label} density="compact" as="article">
                                    <p className="text-xs text-muted">{card.label}</p>
                                    <p className="mt-1 text-2xl font-semibold text-ink">{card.value ?? '—'}</p>
                                    {card.href ? (
                                        <Link href={card.href} className="mt-2 inline-block text-xs font-semibold text-brand hover:underline">
                                            View
                                        </Link>
                                    ) : null}
                                </UiCard>
                            ))}
                        </div>
                    </section>
                ) : null}

                <div className="grid gap-4 lg:grid-cols-2">
                    {permissions.jobs && recentJobs.length > 0 ? (
                        <UiCard as="section" density="compact">
                            <div className="mb-2 flex items-center justify-between gap-2">
                                <h2 className="text-base font-semibold text-ink">Active jobs</h2>
                                <Link href={links.jobs} className="text-sm font-semibold text-brand hover:underline">
                                    View all jobs
                                </Link>
                            </div>
                            <ul className="divide-y divide-line">
                                {recentJobs.map((job) => (
                                    <li key={job.id} className="flex items-center justify-between gap-2 py-3 text-sm first:pt-0">
                                        <span className="font-medium text-ink">{job.title}</span>
                                        <Link
                                            href={`/admin/hiring?job_id=${job.id}`}
                                            className="shrink-0 font-semibold text-brand hover:underline"
                                        >
                                            Filter
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </UiCard>
                    ) : null}
                    {permissions.ats && (filters.line === 'all' || filters.line === 'ats') ? (
                        <UiCard as="section" density="compact">
                            <h2 className="text-base font-semibold text-ink">Recent candidate activity</h2>
                            <QueueList items={recentActivity} empty="No recent recruitment activity" />
                        </UiCard>
                    ) : null}
                </div>

                <section>
                    <h2 className="text-base font-semibold text-ink">Attention queues</h2>
                    <p className="mt-1 text-sm text-muted">Queues use existing application, submission, placement, and task records.</p>
                    <div className="mt-3">
                        <Attention groups={attention} />
                    </div>
                </section>

                {ats ? (
                    <section>
                        <div className="mb-3 flex items-center justify-between gap-2">
                            <h2 className="text-base font-semibold text-ink">Direct Hiring / ATS</h2>
                            <Link href={links.applications} className="text-sm font-medium text-brand hover:underline">
                                Applications
                            </Link>
                        </div>
                        <MetricGrid cards={ats.metrics} />
                    </section>
                ) : null}

                {partnership ? (
                    <section>
                        <div className="mb-3 flex items-center justify-between gap-2">
                            <h2 className="text-base font-semibold text-ink">Recruitment Partnership</h2>
                            <Link href={links.submissions} className="text-sm font-medium text-brand hover:underline">
                                Employer submissions
                            </Link>
                        </div>
                        <p className="mb-3 text-xs text-muted">Client review of submitted candidates. This is not the channel-partner commission module.</p>
                        <MetricGrid cards={partnership.metrics} />
                    </section>
                ) : null}

                {staffing ? (
                    <section>
                        <div className="mb-3 flex items-center justify-between gap-2">
                            <h2 className="text-base font-semibold text-ink">Staffing Services</h2>
                            <Link href={links.placements} className="text-sm font-medium text-brand hover:underline">
                                Placements
                            </Link>
                        </div>
                        <MetricGrid cards={staffing.metrics} />
                        <p className="mt-2 text-xs text-muted">{staffing.assignment_end_note}</p>
                    </section>
                ) : null}

                {jobs_board ? (
                    <section>
                        <div className="mb-3 flex items-center justify-between gap-2">
                            <h2 className="text-base font-semibold text-ink">OurNear Jobs</h2>
                            <Link href={links.jobs} className="text-sm font-medium text-brand hover:underline">
                                Jobs
                            </Link>
                        </div>
                        <MetricGrid cards={jobs_board.metrics} />
                        <Card className="mt-4">
                            <h3 className="mb-2 text-sm font-semibold text-ink">Recent applications</h3>
                            <QueueList items={jobs_board.recent} empty="No recent Jobs applications" />
                        </Card>
                    </section>
                ) : null}

                {permissions.automation ? (
                    <UiCard as="section" density="compact">
                        <h2 className="text-base font-semibold text-ink">Automation executions</h2>
                        <p className="mb-2 text-xs text-muted">Advisory only. Match percent is from ScreeningMatchService. Shortlist stays human.</p>
                        <QueueList items={automation.slice(0, 5)} empty="No recent automation executions" />
                    </UiCard>
                ) : null}

                {adminModules.length > 0 ? (
                    <section aria-labelledby="admin-hiring-modules">
                        <h2 id="admin-hiring-modules" className="text-base font-semibold text-ink">
                            Hiring modules
                        </h2>
                        <div className="mt-3 grid gap-3 sm:grid-cols-2">
                            {adminModules.map((module) => (
                                <UiCard key={module.key} as="article" density="compact" className="flex flex-col">
                                    <h3 className="font-semibold text-ink">{module.title}</h3>
                                    <p className="mt-2 flex-1 text-sm text-muted">{module.description}</p>
                                    <Link
                                        href={module.href}
                                        className="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-brand hover:underline"
                                    >
                                        Open
                                    </Link>
                                </UiCard>
                            ))}
                        </div>
                    </section>
                ) : null}
            </div>
        </AdminLayout>
    );
}
