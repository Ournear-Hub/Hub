import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/Public/Button';
import PublicPageHero from '@/Components/Public/PublicPageHero';
import ProfileShell from '@/Components/Profile/ProfileShell';
import PageContainer from '@/Components/Public/PageContainer';
import EmptyState from '@/Components/UI/EmptyState';
import type { SiteContent } from '@/types/public';

type Props = {
    content: SiteContent;
    company: {
        id: number;
        business_id: string | null;
        company_name: string;
        logo_url: string | null;
        ournear_verified?: boolean;
        about_company: string | null;
        company_size: string | null;
        industry: string | null;
        company_details: string | null;
        company_website: string | null;
        locations?: string[];
        benefits?: string[];
        socials?: Record<string, string | null>;
        open_jobs?: Array<{
            title: string;
            slug: string;
            location: string | null;
            employment_type: string | null;
        }>;
    };
};

function companyInitials(name: string): string {
    return (
        name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0])
            .join('')
            .toUpperCase() || 'EP'
    );
}

export default function PublicCompanyProfile({ company }: Props) {
    const socials = Object.entries(company.socials ?? {}).filter(([, url]) => Boolean(url));
    const companyUrl = `/companies/${company.id}`;
    const locationLine = (company.locations ?? []).filter(Boolean).join(', ') || null;
    const openJobs = company.open_jobs ?? [];
    const metaLine =
        [company.industry, company.company_size, locationLine].filter(Boolean).join(' · ') ||
        'Company profile on OurNear Jobs';

    return (
        <PublicLayout title={company.company_name}>
            <PublicPageHero
                title={company.company_name}
                description={metaLine}
                breadcrumbs={[
                    { label: 'Home', href: '/' },
                    { label: 'Companies', href: '/companies' },
                    { label: company.company_name },
                ]}
                actions={
                    <>
                        <Button href="/jobs" variant="outline" className="border-white/30 bg-transparent text-white hover:bg-white/10">
                            Browse jobs
                        </Button>
                        {company.company_website ? (
                            <Button href={company.company_website} variant="primary">
                                Visit website
                            </Button>
                        ) : null}
                    </>
                }
            />

            <section className="py-8 md:py-10">
                <PageContainer className="max-w-4xl min-w-0">
                    <ProfileShell
                        name={company.company_name}
                        photoUrl={company.logo_url}
                        profileHref={companyUrl}
                        businessId={company.business_id}
                        subtitle={[company.industry, company.company_size].filter(Boolean).join(' · ') || null}
                        location={locationLine}
                        badge={company.ournear_verified ? 'OurNear verified' : null}
                        shape="rounded"
                        heading="h2"
                        actions={
                            company.company_website ? (
                                <a
                                    href={company.company_website}
                                    target="_blank"
                                    rel="noopener noreferrer nofollow"
                                    className="inline-flex rounded-md bg-accent px-4 py-2 text-sm font-semibold text-white"
                                >
                                    Visit website
                                </a>
                            ) : null
                        }
                    >
                        <article className="min-w-0 space-y-8 rounded-xl border border-line bg-white p-6 sm:p-8">
                            {!company.logo_url ? (
                                <div
                                    className="flex h-20 w-20 items-center justify-center rounded-xl bg-brand/10 text-lg font-semibold text-brand lg:hidden"
                                    aria-hidden
                                >
                                    {companyInitials(company.company_name)}
                                </div>
                            ) : null}

                            <section>
                                <h2 className="text-lg font-semibold text-ink">About company</h2>
                                {company.about_company ? (
                                    <p className="mt-2 whitespace-pre-line text-sm leading-6 text-muted">{company.about_company}</p>
                                ) : (
                                    <p className="mt-2 text-sm text-muted">No company description has been published yet.</p>
                                )}
                            </section>

                            {company.company_details ? (
                                <section>
                                    <h2 className="text-lg font-semibold text-ink">Company details</h2>
                                    <p className="mt-2 whitespace-pre-line text-sm leading-6 text-muted">{company.company_details}</p>
                                </section>
                            ) : null}

                            {locationLine ? (
                                <section>
                                    <h2 className="text-lg font-semibold text-ink">Locations</h2>
                                    <p className="mt-2 text-sm text-muted">{locationLine}</p>
                                </section>
                            ) : null}

                            {(company.benefits ?? []).length > 0 ? (
                                <section>
                                    <h2 className="text-lg font-semibold text-ink">Benefits</h2>
                                    <ul className="mt-2 list-disc space-y-1 pl-5 text-sm text-muted">
                                        {company.benefits?.map((benefit) => (
                                            <li key={benefit}>{benefit}</li>
                                        ))}
                                    </ul>
                                </section>
                            ) : null}

                            {socials.length > 0 ? (
                                <ul className="flex flex-wrap gap-3 text-sm">
                                    {socials.map(([network, url]) => (
                                        <li key={network}>
                                            <a
                                                href={url ?? undefined}
                                                target="_blank"
                                                rel="noopener noreferrer nofollow"
                                                className="font-medium capitalize text-brand hover:underline"
                                            >
                                                {network}
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            ) : null}

                            <section className="border-t border-line pt-8">
                                <div className="flex flex-wrap items-end justify-between gap-3">
                                    <h2 className="text-lg font-semibold text-ink">Open jobs</h2>
                                    {openJobs.length > 0 ? (
                                        <Link href="/jobs" className="text-sm font-semibold text-brand hover:underline">
                                            View all jobs
                                        </Link>
                                    ) : null}
                                </div>
                                {openJobs.length > 0 ? (
                                    <ul className="mt-4 space-y-3">
                                        {openJobs.map((job) => (
                                            <li key={job.slug} className="min-w-0 rounded-lg border border-line bg-canvas/50 p-4">
                                                <Link href={`/jobs/${job.slug}`} className="font-semibold text-brand hover:underline">
                                                    {job.title}
                                                </Link>
                                                <p className="mt-1 text-sm text-muted">
                                                    {[job.location, job.employment_type].filter(Boolean).join(' · ') ||
                                                        'Details on job page'}
                                                </p>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <EmptyState
                                        className="mt-4"
                                        title="No open jobs"
                                        description="This company has no published vacancies right now."
                                        action={
                                            <Button href="/jobs" variant="outline">
                                                Browse all jobs
                                            </Button>
                                        }
                                    />
                                )}
                            </section>
                        </article>
                    </ProfileShell>
                    <p className="mt-4 text-xs text-muted">Private company documents are not displayed publicly.</p>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
