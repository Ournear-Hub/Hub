import { Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatCard from '@/Components/Admin/StatCard';
import type { DashboardMetric, DashboardPipelineStage, DashboardStats } from '@/types/page';

type DashboardProps = {
    stats: DashboardStats;
    recentApplications: Array<Record<string, unknown>>;
    recentEmployerEnquiries: Array<Record<string, unknown>>;
};

function metric(stats: DashboardStats['platform'] | DashboardStats['recruitment'], key: string): DashboardMetric {
    const item = stats?.[key as keyof NonNullable<typeof stats>] as DashboardMetric | undefined;
    return item ?? { value: null, href: null };
}

export default function Dashboard({
    stats,
    recentApplications,
    recentEmployerEnquiries,
}: DashboardProps) {
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
        { label: 'Employer Submissions', key: 'employer_submissions' },
        { label: 'Selected', key: 'selected' },
        { label: 'Placements', key: 'placements' },
        { label: 'Joined Employees', key: 'joined_employees' },
        { label: 'Replacement Cases', key: 'replacement_cases' },
    ];
    const pipeline = stats.recruitment_pipeline ?? [];
    const pending = stats.pending_actions ?? [];

    return (
        <AdminLayout title="Dashboard">
            <div className="space-y-8">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Dashboard</h1>
                    <p className="mt-1 text-sm text-muted">Live counts from existing records. Cards open the matching Admin workspace.</p>
                </div>

                <section>
                    <h2 className="text-base font-semibold text-ink">Platform</h2>
                    <div className="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                        {platformCards.map((card) => {
                            const item = metric(stats.platform, card.key);
                            return <StatCard key={card.key} label={card.label} value={item.value} href={item.href} />;
                        })}
                    </div>
                </section>

                <section>
                    <h2 className="text-base font-semibold text-ink">Recruitment</h2>
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
                        <div className="mt-3 flex flex-wrap items-stretch gap-2">
                            {pipeline.map((stage: DashboardPipelineStage, index) => (
                                <div key={stage.key} className="flex items-stretch gap-2">
                                    <StatCard label={stage.label} value={stage.value} href={stage.href} />
                                    {index < pipeline.length - 1 ? (
                                        <p className="hidden self-center text-lg text-muted xl:block" aria-hidden>
                                            →
                                        </p>
                                    ) : null}
                                </div>
                            ))}
                        </div>
                    </section>
                ) : null}

                {pending.length > 0 ? (
                    <section className="rounded-xl border border-line bg-white p-5">
                        <h2 className="text-base font-semibold text-ink">Pending actions</h2>
                        <ul className="mt-3 divide-y divide-line">
                            {pending.map((item) => (
                                <li key={item.key} className="flex items-center justify-between gap-3 py-2 text-sm">
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

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <section className="overflow-x-auto rounded-xl border border-line bg-white p-5">
                        <h2 className="text-base font-semibold text-ink">Recent applications</h2>
                        {recentApplications.length === 0 ? (
                            <p className="mt-3 text-sm text-muted">No applications yet.</p>
                        ) : (
                            <ul className="mt-3 space-y-2 text-sm text-ink">
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
                        <h2 className="text-base font-semibold text-ink">Recent employer enquiries</h2>
                        {recentEmployerEnquiries.length === 0 ? (
                            <p className="mt-3 text-sm text-muted">No employer enquiries yet.</p>
                        ) : (
                            <ul className="mt-3 space-y-2 text-sm text-ink">
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