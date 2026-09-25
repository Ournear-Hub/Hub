import { router, usePage } from '@inertiajs/react';
import JobForm from '@/Components/Admin/JobForm';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AdminJob } from '@/types/jobs';
import type { JobFormLookups } from '@/types/masterData';
import type { SharedPageProps } from '@/types/page';

type Props = {
    job: AdminJob | null;
    company_name: string;
    lookups: JobFormLookups;
    eligibility: {
        eligible: boolean;
        reason: string | null;
        next_action: string | null;
    } | null;
    next_tier: 'trial' | 'basic';
};

export default function EmployerJobForm({ job, company_name, lookups, eligibility, next_tier }: Props) {
    const errors = usePage<SharedPageProps>().props.errors ?? {};
    const blocked = eligibility?.eligible === false;

    return (
        <AccountLayout
            title={job ? 'Edit Job' : 'Post Job'}
            description="Uses the same job structure and master data as Admin job posting."
        >
            {!job ? (
                <section className="mb-5 rounded-xl border border-line bg-white p-4 text-sm">
                    <p className="font-semibold text-ink">
                        Posting type: {next_tier === 'trial' ? 'One-hour trial' : 'Basic'}
                    </p>
                    <p className="mt-1 text-muted">
                        {next_tier === 'trial'
                            ? 'Your first job remains publicly available for one hour.'
                            : 'Basic jobs are not Featured.'}
                    </p>
                </section>
            ) : null}
            {blocked ? (
                <section className="rounded-xl border border-accent/40 bg-accent/10 p-5 text-sm text-ink">
                    <p className="font-semibold">You cannot post a job yet.</p>
                    <p className="mt-1">{eligibility?.reason}</p>
                    <p className="mt-1 text-muted">{eligibility?.next_action}</p>
                </section>
            ) : (
                <section className="rounded-xl border border-line bg-white p-6">
                    {errors.posting ? <p className="mb-4 rounded-md bg-danger/10 px-3 py-2 text-sm text-danger">{errors.posting}</p> : null}
                    <JobForm
                        job={job}
                        lookups={lookups}
                        scope="employer"
                        companyName={company_name}
                        onCancel={() => router.get('/account/jobs')}
                    />
                </section>
            )}
        </AccountLayout>
    );
}
