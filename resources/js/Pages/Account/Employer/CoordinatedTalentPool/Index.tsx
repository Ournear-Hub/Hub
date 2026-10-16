import { Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import AccountLayout from '@/Layouts/AccountLayout';
import RecruiterCandidateSummary, {
    type RecruiterCandidateSummaryData,
} from '@/Components/Account/Recruiter/RecruiterCandidateSummary';
import Button from '@/Components/atoms/Button';
import Pagination from '@/Components/Pagination';
import DetailHeader from '@/Components/organisms/DetailHeader';
import type { Paginator } from '@/types/jobs';

type Row = RecruiterCandidateSummaryData & {
    submission_id?: number;
    submitted_at: string | null;
    source_label?: string;
};

type Props = {
    company: { name: string; business_id: string | null };
    submissions: Paginator<Row>;
    filters: { q: string; status: string };
    status_options: { value: string; label: string }[];
};

export default function CoordinatedTalentPoolIndex({ company, submissions, filters, status_options }: Props) {
    const [q, setQ] = useState(filters.q);

    const search = (event: FormEvent) => {
        event.preventDefault();
        router.get('/account/coordinated-talent-pool', { q, status: filters.status }, { preserveState: true });
    };

    return (
        <AccountLayout title="OurNear submissions">
            <DetailHeader
                title="OurNear Talent Pool submissions"
                description={`Candidates coordinated by OurNear for ${company.name}. These are not job applications.`}
            />

            <form onSubmit={search} className="mb-4 flex flex-wrap gap-2">
                <input
                    className="rounded-md border border-line px-3 py-2 text-sm"
                    placeholder="Search candidate name"
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                />
                <select
                    className="rounded-md border border-line px-3 py-2 text-sm"
                    value={filters.status}
                    onChange={(e) =>
                        router.get('/account/coordinated-talent-pool', { q, status: e.target.value }, { preserveState: true })
                    }
                >
                    <option value="">All statuses</option>
                    {status_options.map((opt) => (
                        <option key={opt.value} value={opt.value}>
                            {opt.label}
                        </option>
                    ))}
                </select>
                <Button type="submit" surface="public" variant="secondary">
                    Search
                </Button>
            </form>

            {submissions.data.length === 0 ? (
                <p className="text-sm text-muted">No OurNear Talent Pool submissions yet.</p>
            ) : (
                <ul className="space-y-3">
                    {submissions.data.map((row) => (
                        <li key={row.id}>
                            <Link href={row.submission_href ?? `/account/coordinated-talent-pool/${row.id}`}>
                                <RecruiterCandidateSummary candidate={row} mode="submission" />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
            <Pagination links={submissions.links} />
        </AccountLayout>
    );
}
