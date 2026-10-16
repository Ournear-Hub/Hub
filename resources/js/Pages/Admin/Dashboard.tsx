import { Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatCard from '@/Components/Admin/StatCard';
import Button from '@/Components/atoms/Button';
import type { DashboardMetric, DashboardPipelineStage, DashboardStats, SharedPageProps } from '@/types/page';

type DashboardProps = {
    staff_name: string;
    stats: DashboardStats;
    recentApplications: Array<Record<string, unknown>>;
    recentEmployerEnquiries: Array<Record<string, unknown>>;
};

const quickLinks = [
    { label: 'Hiring Command Center', href: '/admin/hiring', permission: 'can_view_hiring' as const },
    { label: 'Jobs', href: '/admin/jobs' },
    { label: 'Applications', href: '/admin/applications' },
    { label: 'Recruitment Partnership', href: '/admin/submissions' },
    { label: 'Staffing Enquiries', href: '/admin/employer-enquiries' },
    { label: 'Website Content', href: '/admin/website-content' },
    { label: 'Settings', href: '/admin/settings' },
];

function metric(stats: DashboardStats['platform'] | DashboardStats['recruitment'], key: string): DashboardMetric {
    const item = stats?.[key as keyof NonNullable<typeof stats>] as DashboardMetric | undefined;
    return item ?? { value: null, href: null };
}

function greeting(): string {
    const hour = new Date().getHours();
    if (hour < 12) {
        return 'Good morning';
    }
    if (hour < 17) {
        return 'Good afternoon';
    }
    return 'Good evening';
}

function firstName(fullName: string): string {
    const parts = fullName.trim().split(/\s+/).filter(Boolean);
    return parts[0] ?? fullName;
}

export default function Dashboard({
    staff_name,
    stats,
    recentApplications,
    recentEmployerEnquiries,
}: DashboardProps) {
    const page = usePage<SharedPageProps>().props;
    const canHiring = page.auth.user?.can_view_hiring === true;

    const platformCards: Array<{ label: string; key: keyof NonNullable<DashboardStats['platform']> }> = [
        { label: 'Total Users', key: 'total_users' },
        { label: 'New Users', key: 'new_users' },
        { label: 'Active Users', key: 'active_users' },
        { label: 'Verified Users', key: 'verified_users' },
        { label: 'Blocked/Suspended Users', key: 'blocked_users' },
    ];
    const recruitmentCards: Array<{ label: string; key: keyof NonNullable<DashboardStats['recruitment']> }> = [
        { label: 'Active Jobs', key: 'active_jobs' },
        { label: 'Applications', key: 'applications' },
        { label: 'Candidates', key: 'candidates' },
        { label: 'Screening Pending', key: 'screening_pending' },
        { label: 'Shortlisted', key: 'shortlisted' },
        { label: 'Recruitment Partnership', key: 'employer_submissions' },
        { label: 'Selected', key: 'selected' },
        { label: 'Placements', key: 'placements' },
        { label: 'Joined Employees', key: 'joined_employees' },
        { label: 'Replacement Cases', key: 'replacement_cases' },
    ];
    const pipeline = stats.recruitment_pipeline ?? [];
    const pending = stats.pending_actions ?? [];

    return (
        <AdminLayout title="Dashboard" breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Dashboard' }]}>
            <div className="space-y-8">
                <header className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-sm text-muted">
                            {greeting()}, {firstName(staff_name)}
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight text-ink">Operations dashboard</h1>
                        <p className="mt-1 text-sm text-muted">
                            Live counts from existing records. Cards open the matching Admin workspace.
                        </p>
                    </div>
                    {page.auth.user?.can_view_analytics ? (
                        <Link
                            href="/admin/analytics"
                            className="inline-flex min-h-11 items-center rounded-md border border-line bg-white px-4 py-2 text-sm font-semibold text-ink hover:border-brand/40"
                        >
                            View analytics
                        </Link>
                    ) : null}
                </header>

                {pending.length > 0 ? (
                    <section
                        className="rounded-xl border-2 border-accent/30 bg-accent/5 p-5"
                        aria-labelledby="admin-action-required"
                    >
                        <h2 id="admin-action-required" className="text-base font-semibold text-ink">
                            Action required
                        </h2>
                        <ul className="mt-3 divide-y divide-line/80">
                            {pending.map((item) => (
                                <li key={item.key} className="flex flex-wrap items-center justify-between gap-3 py-2 text-sm">
                                    {item.href ? (
                                        <Link href={item.href} className="font-medium text-brand hover:underline">
                                            {item.label}
                                        </Link>
                                    ) : (
                                        <span className="text-ink">{item.label}</span>
                                    )}
                                    <span className="font-semibold text-ink">{item.value === null ? '—' : item.value}</span>
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}

                <section aria-labelledby="admin-quick-access">
                    <h2 id="admin-quick-access" className="text-base font-semibold text-ink">
                        Quick access
                    </h2>
                    <div className="mt-3 flex flex-wrap gap-2">
                        {quickLinks.map((link) => {
                            if (link.permission === 'can_view_hiring' && !canHiring) {
                                return null;
                            }
                            return (
                                <Button surface="admin" key={link.href} href={link.href} variant="secondary" className="min-h-11">
                                    {link.label}
                                </Button>
                            );
                        })}
                    </div>
                </section>

                <section>
                    <h2 className="text-base font-semibold text-ink">Operational summary</h2>
                    <h3 className="mt-4 text-sm font-semibold text-muted">Platform</h3>
                    <div className="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        {platformCards.map((card) => {
                            const item = metric(stats.platform, card.key);
                            return <StatCard key={card.key} label={card.label} value={item.value} href={item.href} />;
                        })}
                    </div>
                    <h3 className="mt-6 text-sm font-semibold text-muted">Recruitment</h3>
                    <div className="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        {recruitmentCards.map((card) => {
                            const item = metric(stats.recruitment, card.key);
                            return <StatCard key={card.key} label={card.label} value={item.value} href={item.href} />;
                        })}
                    </div>
                </section>

                {pipeline.length > 0 ? (
                    <section>
                        <h2 className="text-base font-semibold text-ink">Recruitment pipeline</h2>
                        <div className="mt-3 overflow-x-auto rounded-xl border border-line bg-white p-5">
                            {(() => {
                                const max = Math.max(1, ...pipeline.map((stage: DashboardPipelineStage) => Number(stage.value ?? 0)));
                                return (
                                    <ul className="min-w-[280px] space-y-3">
                                        {pipeline.map((stage: DashboardPipelineStage) => {
                                            const value = Number(stage.value ?? 0);
                                            const width = Math.round((value / max) * 100);
                                            return (
                                                <li key={stage.key}>
                                                    <div className="flex items-center justify-between gap-3 text-sm">
                                                        {stage.href ? (
                                                            <Link href={stage.href} className="font-medium text-brand hover:underline">
                                                                {stage.label}
                                                            </Link>
                                                        ) : (
                                                            <span className="font-medium text-ink">{stage.label}</span>
                                                        )}
                                                        <span className="font-semibold text-ink">
                                                            {stage.value === null ? '—' : stage.value}
                                                        </span>
                                                    </div>
                                                    <div className="mt-1 h-2 overflow-hidden rounded-full bg-surface" aria-hidden="true">
                                                        <div className="h-full rounded-full bg-brand" style={{ width: `${width}%` }} />
                                                    </div>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                );
                            })()}
                        </div>
                    </section>
                ) : null}

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <section className="overflow-x-auto rounded-xl border border-line bg-white p-5">
                        <h2 className="text-base font-semibold text-ink">Recent applications</h2>
                        {recentApplications.length === 0 ? (
                            <p className="mt-3 text-sm text-muted">No applications yet.</p>
                        ) : (
                            <ul className="mt-3 min-w-0 space-y-2 text-sm text-ink">
                                {recentApplications.map((row, index) => (
                                    <li key={String(row.id ?? index)} className="rounded-md bg-surface px-3 py-2">
                                        {row.href ? (
                                            <Link href={String(row.href)} className="font-medium text-brand hover:underline">
                                                {String(row.full_name ?? `Application #${row.id ?? index + 1}`)}
                                            </Link>
                                        ) : (
                                            String(row.full_name ?? `Application #${row.id ?? index + 1}`)
                                        )}
                                        {row.job_title ? ` · ${String(row.job_title)}` : ''}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                    <section className="overflow-x-auto rounded-xl border border-line bg-white p-5">
                        <h2 className="text-base font-semibold text-ink">Recent staffing enquiries</h2>
                        {recentEmployerEnquiries.length === 0 ? (
                            <p className="mt-3 text-sm text-muted">No staffing enquiries yet.</p>
                        ) : (
                            <ul className="mt-3 min-w-0 space-y-2 text-sm text-ink">
                                {recentEmployerEnquiries.map((row, index) => (
                                    <li key={String(row.id ?? index)} className="rounded-md bg-surface px-3 py-2">
                                        {row.href ? (
                                            <Link href={String(row.href)} className="font-medium text-brand hover:underline">
                                                {String(row.company_name ?? `Enquiry #${row.id ?? index + 1}`)}
                                            </Link>
                                        ) : (
                                            String(row.company_name ?? `Enquiry #${row.id ?? index + 1}`)
                                        )}
                                        {row.contact_person ? ` · ${String(row.contact_person)}` : ''}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            </div>
        </AdminLayout>
    );
}
