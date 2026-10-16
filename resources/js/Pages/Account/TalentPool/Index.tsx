import { Link, router } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/atoms/StatusBadge';

type AssignmentRow = {
    id: number;
    candidate_name: string;
    candidate_ournear_id: string | null;
    assignment_type_label: string;
    status: string;
    progress_stage_label: string;
    requirement_label: string | null;
    assigned_at: string | null;
    follow_up_at: string | null;
    updated_at: string | null;
};

type Props = {
    company_name: string;
    assignments: {
        data: AssignmentRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { status: string };
    statuses: { value: string; label: string }[];
    progress_stages: { value: string; label: string }[];
};

export default function RecruiterTalentPoolIndex({ company_name, assignments, filters, statuses, progress_stages }: Props) {
    return (
        <AccountLayout title="Assigned Candidates" hideShellHeader>
            <div className="space-y-4">
                <div>
                    <h1 className="text-xl font-semibold text-ink">Assigned Candidates</h1>
                    <p className="mt-1 text-sm text-muted">
                        {company_name} — candidates OurNear assigned for recruitment support. Separate from your
                        Hiring ATS applicant pipeline.
                    </p>
                    <div className="mt-4 rounded-lg border border-line bg-surface px-4 py-3 text-sm text-ink">
                        <p className="font-semibold">Welcome to your recruiter workspace</p>
                        <p className="mt-1 text-muted">
                            Encourage candidates to complete personal and professional profiles so you can assess
                            qualifications accurately. Profile completion improves visibility for suitable roles but{' '}
                            <span className="font-medium text-ink">does not guarantee shortlisting, selection, or hire</span>
                            — those decisions stay with employers and OurNear process rules.
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button
                        surface="public"
                        type="button"
                        variant={filters.status === '' ? 'primary' : 'outline'}
                        onClick={() => router.get('/account/talent-pool', {}, { preserveState: true })}
                    >
                        All
                    </Button>
                    {statuses.map((status) => (
                        <Button
                            key={status.value}
                            surface="public"
                            type="button"
                            variant={filters.status === status.value ? 'primary' : 'outline'}
                            onClick={() =>
                                router.get('/account/talent-pool', { status: status.value }, { preserveState: true })
                            }
                        >
                            {status.label}
                        </Button>
                    ))}
                </div>

                {assignments.data.length === 0 ? (
                    <Card padding="standard">
                        <p className="text-sm text-muted">
                            No assigned candidates yet. Submit a staffing requirement or wait for OurNear to assign
                            candidates to your company.
                        </p>
                        <Link
                            href="/account/enquiries"
                            className="mt-3 inline-flex text-sm font-semibold text-brand hover:underline"
                        >
                            Open Staffing Requirements
                        </Link>
                    </Card>
                ) : (
                    <ul className="space-y-3">
                        {assignments.data.map((row) => (
                            <li key={row.id}>
                                <Card padding="standard" className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="min-w-0">
                                        <Link
                                            href={`/account/talent-pool/${row.id}`}
                                            className="block text-base font-semibold text-brand hover:text-accent"
                                        >
                                            {row.candidate_name ?? 'Assignment details'}
                                        </Link>
                                        {row.candidate_ournear_id ? (
                                            <p className="text-xs text-muted">{row.candidate_ournear_id}</p>
                                        ) : null}
                                        <p className="mt-1 text-sm text-muted">
                                            {row.assignment_type_label}
                                            {row.requirement_label ? ` · ${row.requirement_label}` : ''}
                                        </p>
                                        {row.follow_up_at ? (
                                            <p className="mt-1 text-xs text-muted">Follow-up {row.follow_up_at}</p>
                                        ) : null}
                                        {row.assigned_at ? (
                                            <p className="mt-1 text-xs text-muted">Assigned {row.assigned_at}</p>
                                        ) : null}
                                    </div>
                                    <StatusBadge tone="neutral">{row.progress_stage_label}</StatusBadge>
                                </Card>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={assignments.links} />
            </div>
        </AccountLayout>
    );
}
