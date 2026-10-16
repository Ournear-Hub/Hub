import { router, usePage } from '@inertiajs/react';
import JobForm from '@/Components/Admin/JobForm';
import AccountLayout from '@/Layouts/AccountLayout';
import BackNavLink from '@/Components/molecules/BackNavLink';
import type { AdminJob } from '@/types/jobs';
import type { JobFormLookups } from '@/types/masterData';
import type { SharedPageProps } from '@/types/page';

type Props = {
    job: AdminJob | null;
    company_name: string;
    company_industry_id: number | null;
    company_industry_name: string | null;
    lookups: JobFormLookups;
    eligibility: {
        eligible: boolean;
        reason: string | null;
        next_action: string | null;
    } | null;
    next_tier: 'trial' | 'basic';
};

export default function EmployerJobForm({
    job,
    company_name,
    company_industry_id,
    company_industry_name,
    lookups,
    eligibility,
    next_tier,
}: Props) {
    const errors = usePage<SharedPageProps>().props.errors ?? {};
    const publishBlocked = eligibility?.eligible === false;

    return (
        <AccountLayout
            title={job ? 'Edit Job' : 'Create Job'}
            description="Save drafts anytime. Unverified employers may publish one trial job for one hour; verify email and mobile for unlimited publishing."
            breadcrumbs={[
                { label: 'Dashboard', href: '/account' },
                { label: 'Jobs', href: '/account/jobs' },
                { label: job ? 'Edit job' : 'Post job' },
            ]}
        >
            <BackNavLink href="/account/jobs" label="Back to jobs" className="mb-4" />
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
            {publishBlocked ? (
                <section className="mb-4 rounded-xl border border-accent/40 bg-accent/10 p-5 text-sm text-ink">
                    <p className="font-semibold">Publishing is not available yet</p>
                    <p className="mt-1">{eligibility?.reason}</p>
                    <p className="mt-1 text-muted">{eligibility?.next_action}</p>
                    <p className="mt-2 text-muted">You can still save this job as a draft and publish later from Job management.</p>
                </section>
            ) : null}
            <section className="rounded-xl border border-line bg-white p-6">
                {errors.posting ? <p className="mb-4 rounded-md bg-danger/10 px-3 py-2 text-sm text-danger">{errors.posting}</p> : null}
                <JobForm
                    job={job}
                    lookups={lookups}
                    scope="employer"
                    companyName={company_name}
                    companyIndustryId={company_industry_id}
                    companyIndustryName={company_industry_name}
                    onCancel={() => router.get('/account/jobs')}
                />
            </section>
        </AccountLayout>
    );
}
