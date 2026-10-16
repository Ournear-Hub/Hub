import { Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Card from '@/Components/atoms/Card';
import DetailHeader from '@/Components/organisms/DetailHeader';
import StatusBadge from '@/Components/Account/StatusBadge';

type Props = {
    metrics: {
        open_requirements: number;
        sourcing_in_progress: number;
        active_submissions: number;
        awaiting_client: number;
        joined_total: number;
    };
    recent_submissions: {
        id: number;
        business_id: string;
        status_label: string;
        candidate_name: string | null;
        requirement_label: string | null;
        submitted_at: string | null;
    }[];
    links: { enquiries: string; allocations: string };
};

export default function StaffingOperationsIndex({ metrics, recent_submissions, links }: Props) {
    return (
        <AdminLayout title="Staffing workspace">
            <DetailHeader
                title="Staffing operations"
                description="Client requirements, candidate submissions, client decisions and joining fulfilment."
            />

            <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                {[
                    ['Open requirements', metrics.open_requirements],
                    ['Sourcing in progress', metrics.sourcing_in_progress],
                    ['Active submissions', metrics.active_submissions],
                    ['Awaiting client', metrics.awaiting_client],
                    ['Confirmed joinings', metrics.joined_total],
                ].map(([label, value]) => (
                    <Card key={label as string} className="p-4">
                        <p className="text-sm text-muted">{label}</p>
                        <p className="text-2xl font-semibold text-ink">{value}</p>
                    </Card>
                ))}
            </div>

            <div className="mt-6 flex flex-wrap gap-3">
                <Link href={links.enquiries} className="text-sm font-medium text-brand hover:underline">
                    Staffing enquiries
                </Link>
                <Link href={links.allocations} className="text-sm font-medium text-brand hover:underline">
                    Candidate allocation (Staffing)
                </Link>
            </div>

            <section className="mt-8">
                <h2 className="text-base font-semibold text-ink">Recent candidate submissions</h2>
                {recent_submissions.length === 0 ? (
                    <p className="mt-2 text-sm text-muted">No staffing candidate submissions yet.</p>
                ) : (
                    <ul className="mt-3 space-y-2">
                        {recent_submissions.map((row) => (
                            <li key={row.id}>
                                <Link
                                    href={`/admin/staffing-candidate-submissions/${row.id}`}
                                    className="flex flex-wrap items-center gap-2 text-sm hover:underline"
                                >
                                    <span className="font-medium text-ink">{row.candidate_name ?? 'Candidate'}</span>
                                    <span className="text-muted">· {row.requirement_label ?? 'Requirement'}</span>
                                    <StatusBadge status={row.status_label} />
                                    <span className="text-xs text-muted">{row.business_id}</span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </AdminLayout>
    );
}
