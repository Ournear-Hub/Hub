import { Link } from '@inertiajs/react';
import AccountLayout from '@/Layouts/AccountLayout';
import Button from '@/Components/atoms/Button';
import Card from '@/Components/atoms/Card';
import Pagination from '@/Components/Pagination';

type JobRow = {
    id: number;
    title: string;
    publishing_status_label?: string;
    listing_scope?: string;
    applications_count?: number;
};

type Props = {
    jobs: {
        data: JobRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
};

export default function TalentPoolJobsIndex({ jobs }: Props) {
    return (
        <AccountLayout title="Talent Pool Job Listings" hideShellHeader>
            <div className="space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-ink">Talent Pool Job Listings</h1>
                        <p className="mt-1 text-sm text-muted">
                            Internal recruitment jobs for Talent Pool sourcing. Public visibility depends on the
                            publication destination you choose when creating a job.
                        </p>
                    </div>
                    <Button surface="public" href="/account/talent-pool/jobs/create" variant="primary">
                        Create Talent Pool Job
                    </Button>
                </div>

                {jobs.data.length === 0 ? (
                    <Card padding="standard">
                        <p className="text-sm text-muted">No Talent Pool jobs yet.</p>
                        <Link href="/account/talent-pool" className="mt-2 inline-flex text-sm font-semibold text-brand hover:underline">
                            Back to Assigned Candidates
                        </Link>
                    </Card>
                ) : (
                    <ul className="space-y-2">
                        {jobs.data.map((job) => (
                            <li key={job.id}>
                                <Card padding="standard">
                                    <p className="font-semibold text-ink">{job.title}</p>
                                    <p className="text-sm text-muted">
                                        {job.publishing_status_label ?? 'Draft'} · scope {job.listing_scope ?? 'talent_pool_internal'}
                                        {typeof job.applications_count === 'number'
                                            ? ` · ${job.applications_count} applications`
                                            : ''}
                                    </p>
                                </Card>
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination links={jobs.links} />
            </div>
        </AccountLayout>
    );
}
