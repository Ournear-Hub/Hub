import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AccountLayout from '@/Layouts/AccountLayout';
import JobForm from '@/Components/Admin/JobForm';
import BackNavLink from '@/Components/molecules/BackNavLink';
import type { JobFormLookups } from '@/types/masterData';
import type { SharedPageProps } from '@/types/page';

type Props = {
    job: null;
    company_name: string;
    lookups: JobFormLookups;
    listing_scope_options: { value: string; label: string }[];
    eligibility: { eligible: boolean; reason: string | null; next_action: string | null } | null;
};

export default function TalentPoolJobForm({ company_name, lookups, listing_scope_options, eligibility }: Props) {
    const errors = usePage<SharedPageProps>().props.errors ?? {};
    const [listingScope, setListingScope] = useState(listing_scope_options[0]?.value ?? 'talent_pool_internal');
    const [publishNow, setPublishNow] = useState(false);

    return (
        <AccountLayout title="Create Talent Pool Job" hideShellHeader>
            <BackNavLink href="/account/talent-pool/jobs" label="Back to Talent Pool jobs" className="mb-4" />
            <section className="mb-4 rounded-xl border border-line bg-white p-4 text-sm">
                <p className="font-semibold text-ink">Publication destination</p>
                <p className="mt-1 text-muted">
                    OurNear Talent Pool jobs stay internal unless you explicitly choose public publication and meet
                    eligibility rules.
                </p>
                <select
                    className="mt-3 w-full rounded-md border border-line px-3 py-2 text-sm"
                    value={listingScope}
                    onChange={(event) => setListingScope(event.target.value)}
                >
                    {listing_scope_options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
                {listingScope === 'talent_pool_and_public' ? (
                    <label className="mt-2 flex items-center gap-2 text-sm text-ink">
                        <input
                            type="checkbox"
                            checked={publishNow}
                            onChange={(event) => setPublishNow(event.target.checked)}
                            disabled={eligibility?.eligible === false}
                        />
                        Publish to public Jobs now
                    </label>
                ) : null}
            </section>
            {errors.posting ? (
                <p className="mb-4 rounded-md bg-danger/10 px-3 py-2 text-sm text-danger">{errors.posting}</p>
            ) : null}
            <section className="rounded-xl border border-line bg-white p-6">
                <JobForm
                    job={null}
                    lookups={lookups}
                    scope="employer"
                    companyName={company_name}
                    companyIndustryId={null}
                    companyIndustryName={null}
                    submitBasePath="/account/talent-pool/jobs"
                    formExtras={{
                        listing_scope: listingScope,
                        publish_now: publishNow,
                        publishing_status: 'draft',
                    }}
                    onCancel={() => router.get('/account/talent-pool/jobs')}
                />
            </section>
        </AccountLayout>
    );
}
