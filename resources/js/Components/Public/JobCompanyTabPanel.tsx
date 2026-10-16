import { Link } from '@inertiajs/react';
import CompanyVerificationIndicator from '@/Components/Public/CompanyVerificationIndicator';
import EmptyState from '@/Components/UI/EmptyState';
import type { PublicJobCompanyProfile } from '@/types/jobs';
import { publicJobsWorkspaceWithJob } from '@/utils/publicJobsUrl';

type Props = {
    company: PublicJobCompanyProfile | null | undefined;
    openJobsLinkMode?: 'job_page' | 'jobs_index';
};

function openJobHref(slug: string, mode: 'job_page' | 'jobs_index'): string {
    return mode === 'jobs_index' ? publicJobsWorkspaceWithJob(slug) : `/jobs/${slug}`;
}

const SOCIAL_LABELS: Record<string, string> = {
    linkedin: 'LinkedIn',
    facebook: 'Facebook',
    twitter: 'X (Twitter)',
    instagram: 'Instagram',
    youtube: 'YouTube',
};

export default function JobCompanyTabPanel({ company, openJobsLinkMode = 'job_page' }: Props) {
    if (!company) {
        return (
            <EmptyState
                title="Company information unavailable"
                description="This employer has not published a public company profile for this job."
            />
        );
    }

    const locations = (company.locations ?? []).filter(Boolean).join(', ') || null;
    const displayName = company.company_name?.trim() || 'Company';
    const initials =
        displayName
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0])
            .join('')
            .toUpperCase() || 'CO';
    const socials = Object.entries(company.socials ?? {}).filter(([, url]) => Boolean(url));

    return (
        <div className="min-w-0 space-y-6 text-sm">
            <div className="flex min-w-0 items-center gap-3">
                {company.logo_url ? (
                    <img
                        src={company.logo_url}
                        alt=""
                        className="h-12 w-12 shrink-0 rounded-md border border-line object-cover"
                    />
                ) : (
                    <div
                        aria-hidden
                        className="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-brand/10 text-sm font-semibold text-brand"
                    >
                        {initials}
                    </div>
                )}
                <div className="min-w-0">
                    <p className="truncate text-base font-semibold text-ink">{displayName}</p>
                    <CompanyVerificationIndicator verified={company.company_verified === true} className="mt-0.5" />
                </div>
            </div>

            {company.about_company ? (
                <section>
                    <h3 className="text-base font-semibold text-ink">About company</h3>
                    <p className="mt-2 whitespace-pre-line leading-6 text-muted">{company.about_company}</p>
                </section>
            ) : null}

            {company.company_details ? (
                <section>
                    <h3 className="text-base font-semibold text-ink">Company details</h3>
                    <p className="mt-2 whitespace-pre-line leading-6 text-muted">{company.company_details}</p>
                </section>
            ) : null}

            {company.industry || company.company_size ? (
                <section>
                    <h3 className="text-base font-semibold text-ink">Overview</h3>
                    <p className="mt-2 text-muted">
                        {[company.industry, company.company_size].filter(Boolean).join(' · ')}
                    </p>
                </section>
            ) : null}

            {locations ? (
                <section>
                    <h3 className="text-base font-semibold text-ink">Locations</h3>
                    <p className="mt-2 text-muted">{locations}</p>
                </section>
            ) : null}

            {(company.benefits ?? []).length > 0 ? (
                <section>
                    <h3 className="text-base font-semibold text-ink">Benefits</h3>
                    <ul className="mt-2 list-disc space-y-1 pl-5 text-muted">
                        {company.benefits?.map((benefit) => (
                            <li key={benefit}>{benefit}</li>
                        ))}
                    </ul>
                </section>
            ) : null}

            {socials.length > 0 ? (
                <section>
                    <h3 className="text-base font-semibold text-ink">Social</h3>
                    <ul className="mt-2 flex flex-wrap gap-3">
                        {socials.map(([network, url]) => (
                            <li key={network}>
                                <a
                                    href={url as string}
                                    target="_blank"
                                    rel="noopener noreferrer nofollow"
                                    className="font-semibold text-brand hover:underline"
                                >
                                    {SOCIAL_LABELS[network] ?? network}
                                </a>
                            </li>
                        ))}
                    </ul>
                </section>
            ) : null}

            {company.company_website ? (
                <p>
                    <a
                        href={company.company_website}
                        target="_blank"
                        rel="noopener noreferrer nofollow"
                        className="font-semibold text-brand hover:underline"
                    >
                        Visit company website
                    </a>
                </p>
            ) : null}

            {company.profile_available === false &&
            !company.about_company &&
            !company.company_details &&
            !locations &&
            (company.benefits ?? []).length === 0 &&
            socials.length === 0 ? (
                <p className="text-muted">
                    {displayName} has not published a full public profile. Essential details appear on the job listing.
                </p>
            ) : null}

            {(company.open_jobs ?? []).length > 0 ? (
                <section className="border-t border-line pt-4">
                    <div className="flex flex-wrap items-end justify-between gap-2">
                        <h3 className="text-base font-semibold text-ink">Open jobs</h3>
                        {company.public_jobs_url ? (
                            <Link href={company.public_jobs_url} className="text-sm font-semibold text-brand hover:underline">
                                View all
                            </Link>
                        ) : null}
                    </div>
                    <ul className="mt-3 space-y-2">
                        {company.open_jobs?.map((openJob) => (
                            <li key={openJob.slug} className="rounded-md border border-line bg-canvas/40 px-3 py-2">
                                <Link
                                    href={openJobHref(openJob.slug, openJobsLinkMode)}
                                    className="font-medium text-brand hover:underline"
                                >
                                    {openJob.title}
                                </Link>
                                <p className="text-xs text-muted">
                                    {[openJob.location, openJob.employment_type].filter(Boolean).join(' · ') || 'See job page'}
                                </p>
                            </li>
                        ))}
                    </ul>
                </section>
            ) : company.public_jobs_url ? (
                <p>
                    <Link href={company.public_jobs_url} className="font-semibold text-brand hover:underline">
                        Browse jobs from this company
                    </Link>
                </p>
            ) : null}
        </div>
    );
}
