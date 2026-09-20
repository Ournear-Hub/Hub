import { Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatCard from '@/Components/Admin/StatCard';
import type { DashboardStats } from '@/types/page';

type DashboardProps = {
    stats: DashboardStats;
    recentApplications: Array<Record<string, unknown>>;
    recentEmployerEnquiries: Array<Record<string, unknown>>;
};

export default function Dashboard({
    stats,
    recentApplications,
    recentEmployerEnquiries,
}: DashboardProps) {
    return (
        <AdminLayout title="Dashboard">
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Dashboard</h1>
                    <p className="mt-1 text-sm text-muted">Live counts from the database. Empty modules show an em dash.</p>
                </div>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <StatCard label="Total jobs" value={stats.jobs} />
                    <StatCard label="Active jobs" value={stats.active_jobs} />
                    <StatCard label="Applications" value={stats.applications} />
                    <StatCard label="Employer enquiries" value={stats.employer_enquiries} />
                    <StatCard label="Contact enquiries" value={stats.contact_enquiries} />
                </div>
                {stats.pipeline && (
                    <div>
                        <h2 className="text-base font-semibold text-ink">Recruitment pipeline</h2>
                        <div className="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <StatCard label="New" value={stats.pipeline.new} />
                            <StatCard label="Reviewing" value={stats.pipeline.reviewing} />
                            <StatCard label="Shortlisted" value={stats.pipeline.shortlisted} />
                            <StatCard label="Interview" value={stats.pipeline.interview} />
                            <StatCard label="Selected" value={stats.pipeline.selected} />
                            <StatCard label="Placed" value={stats.pipeline.placed} />
                            <StatCard label="Open published jobs" value={stats.pipeline.published_open_jobs} />
                            <StatCard label="Jobs closing in 7 days" value={stats.pipeline.jobs_closing_soon} />
                            <StatCard label="Employer enquiries · New" value={stats.pipeline.employer_enquiries_new} />
                            <StatCard
                                label="Employer enquiries · Contacted"
                                value={stats.pipeline.employer_enquiries_contacted}
                            />
                            <StatCard
                                label="Employer enquiries · In progress"
                                value={stats.pipeline.employer_enquiries_in_progress}
                            />
                        </div>
                    </div>
                )}
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
