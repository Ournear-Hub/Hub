import { Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Card from '@/Components/atoms/Card';
import DetailHeader from '@/Components/organisms/DetailHeader';

type Props = {
    recruiter_workspace_scoped: boolean;
    overview: {
        active_assignments_count: number;
        submissions_count: number;
        overdue_assignment_follow_ups: number;
        overdue_operational_next_actions: number;
    };
    assigned_candidates: {
        id: number;
        candidate_name: string | null;
        status_label: string;
        follow_up_overdue: boolean;
    }[];
    submissions: { id: number; candidate_name: string | null; company_name: string | null; status_label: string; show_href: string }[];
    links: { assignments: string; messages: string };
};

export default function TalentPoolWorkspaceIndex({
    recruiter_workspace_scoped,
    overview,
    assigned_candidates,
    submissions,
    links,
}: Props) {
    return (
        <AdminLayout title="Talent Pool workspace">
            <DetailHeader
                title="OurNear recruiter workspace"
                description={
                    recruiter_workspace_scoped
                        ? 'Overview, Talent Pool submissions, progress, follow-ups, and authorized staffing work.'
                        : 'Operational overview for internal Talent Pool coordination.'
                }
            />

            <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {[
                    ['Active assignments', overview.active_assignments_count],
                    ['Talent Pool submissions', overview.submissions_count],
                    ['Overdue follow-ups', overview.overdue_assignment_follow_ups + overview.overdue_operational_next_actions],
                ].map(([label, value]) => (
                    <Card key={label as string} className="p-4">
                        <p className="text-sm text-muted">{label}</p>
                        <p className="text-2xl font-semibold text-ink">{value}</p>
                    </Card>
                ))}
            </div>

            <div className="mt-8 grid gap-6 lg:grid-cols-2">
                <section>
                    <h2 className="text-base font-semibold text-ink">Candidate assignments</h2>
                    <ul className="mt-2 space-y-2 text-sm">
                        {assigned_candidates.map((row) => (
                            <li key={row.id}>
                                <Link href={links.assignments + '?assignment=' + row.id} className="text-brand hover:underline">
                                    {row.candidate_name ?? 'Candidate'}
                                </Link>
                                <span className="text-muted"> · {row.status_label}</span>
                                {row.follow_up_overdue ? <span className="text-danger"> · overdue</span> : null}
                            </li>
                        ))}
                    </ul>
                </section>
            </div>

            <section className="mt-8">
                <h2 className="text-base font-semibold text-ink">Talent Pool submissions</h2>
                <ul className="mt-2 space-y-2 text-sm">
                    {submissions.map((row) => (
                        <li key={row.id}>
                            <Link href={row.show_href} className="font-medium text-brand hover:underline">
                                {row.candidate_name ?? 'Candidate'} → {row.company_name ?? 'Company'}
                            </Link>
                            <span className="text-muted"> · {row.status_label}</span>
                        </li>
                    ))}
                </ul>
            </section>

            <p className="mt-8 text-sm text-muted">
                <Link href={links.assignments} className="text-brand hover:underline">
                    Assignments & coordination
                </Link>
                {' · '}
                <Link href={links.messages} className="text-brand hover:underline">
                    Admin messages
                </Link>
            </p>
        </AdminLayout>
    );
}
