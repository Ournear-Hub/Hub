import { Link, router, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/atoms/Button';
import CompanyLogo from '@/Components/Public/CompanyLogo';
import PublicShareMenu from '@/Components/Public/PublicShareMenu';
import JobCard from '@/Components/Public/JobCard';
import PageContainer from '@/Components/Public/PageContainer';
import EmptyState from '@/Components/UI/EmptyState';
import OurNearIcon from '@/icons/OurNearIcon';
import type { SiteContent } from '@/types/public';
import type { PublicJobCard } from '@/types/jobs';
import { publicJobsForEmployer } from '@/utils/publicJobsUrl';

type CompanyTab = 'overview' | 'jobs' | 'benefits';

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
        public_jobs_url?: string;
        share_url?: string;
    };
    open_job_cards: PublicJobCard[];
};

function tabHref(companyId: number, tab: CompanyTab): string {
    if (tab === 'overview') {
        return `/companies/${companyId}`;
    }
    return `/companies/${companyId}?tab=${tab}`;
}

export default function PublicCompanyProfile({ company, open_job_cards }: Props) {
    const { url } = usePage();
    const socials = Object.entries(company.socials ?? {}).filter(([, url]) => Boolean(url));
    const locationLine = (company.locations ?? []).filter(Boolean).join(', ') || null;
    const jobsBoardUrl = company.public_jobs_url ?? publicJobsForEmployer(company.id);
    const benefits = company.benefits ?? [];
    const tab = useMemo((): CompanyTab => {
        const query = url.includes('?') ? url.split('?')[1] : '';
        const value = new URLSearchParams(query).get('tab');
        if (value === 'jobs' || value === 'benefits') {
            return value;
        }
        return 'overview';
    }, [url]);

    const tabs = useMemo(() => {
        const items: Array<{ id: CompanyTab; label: string; href: string }> = [
            { id: 'overview', label: 'Overview', href: tabHref(company.id, 'overview') },
            { id: 'jobs', label: 'Jobs', href: tabHref(company.id, 'jobs') },
        ];
        if (benefits.length > 0) {
            items.push({ id: 'benefits', label: 'Benefits', href: tabHref(company.id, 'benefits') });
        }
        return items;
    }, [benefits.length, company.id]);

    const selectTab = (href: string) => {
        router.get(href, {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    const identityMeta = [company.industry, company.company_size, locationLine].filter(Boolean);

    return (
        <PublicLayout title={company.company_name}>
            <section className="border-b border-line bg-white py-6 md:py-8">
                <PageContainer className="max-w-5xl min-w-0">
                    <nav className="text-sm text-muted" aria-label="Breadcrumb">
                        <ol className="flex flex-wrap items-center gap-1">
                            <li>
                                <Link href="/" className="hover:text-brand">
                                    Home
                                </Link>
                            </li>
                            <li aria-hidden="true">/</li>
                            <li>
                                <Link href="/companies" className="hover:text-brand">
                                    Companies
                                </Link>
                            </li>
                            <li aria-hidden="true">/</li>
                            <li className="font-medium text-ink">{company.company_name}</li>
                        </ol>
                    </nav>

                    <div className="mt-6 rounded-xl border border-line bg-white p-5 shadow-sm sm:p-6">
                        <div className="flex flex-col gap-5 sm:flex-row sm:items-start">
                            <div className="mx-auto flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-line bg-surface/60 p-2 sm:mx-0 sm:h-28 sm:w-28">
                                <CompanyLogo
                                    name={company.company_name}
                                    src={company.logo_url}
                                    sizeClass="h-full w-full max-h-24 max-w-24 sm:max-h-28 sm:max-w-28"
                                    shape="rounded-lg"
                                />
                            </div>
                            <div className="min-w-0 flex-1 text-center sm:text-left">
                                <div className="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                                    <h1 className="break-words text-2xl font-semibold text-brand">{company.company_name}</h1>
                                    {company.ournear_verified ? (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-brand/10 px-2.5 py-1 text-xs font-semibold text-brand">
                                            <OurNearIcon name="verified-badge" size="sm" decorative />
                                            OurNear verified
                                        </span>
                                    ) : null}
                                </div>
                                {identityMeta.length > 0 ? (
                                    <p className="mt-2 text-sm text-muted">{identityMeta.join(' · ')}</p>
                                ) : null}
                                {company.company_website ? (
                                    <a
                                        href={company.company_website}
                                        target="_blank"
                                        rel="noopener noreferrer nofollow"
                                        className="mt-3 inline-flex text-sm font-semibold text-brand hover:underline"
                                    >
                                        Visit website
                                    </a>
                                ) : null}
                                {company.business_id ? (
                                    <p className="mt-2 text-xs text-muted">OurNear ID {company.business_id}</p>
                                ) : null}
                            </div>
                            <div className="flex shrink-0 flex-wrap items-center justify-center gap-2 sm:justify-end">
                                {company.share_url ? (
                                    <PublicShareMenu
                                        label="Share company profile"
                                        context={{
                                            type: 'company',
                                            title: company.company_name,
                                            url: company.share_url,
                                            subtitle: company.industry,
                                            location: locationLine,
                                        }}
                                    />
                                ) : null}
                                <Button surface="public" href={jobsBoardUrl} variant="primary">
                                    View all jobs
                                </Button>
                            </div>
                        </div>

                        <nav className="mt-6 flex flex-wrap gap-2 border-t border-line pt-4" aria-label="Company profile sections">
                            {tabs.map((item) => {
                                const active = tab === item.id;
                                return (
                                    <button
                                        key={item.id}
                                        type="button"
                                        onClick={() => selectTab(item.href)}
                                        className={`rounded-md px-3 py-1.5 text-sm font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 ${
                                            active ? 'bg-brand text-white' : 'text-muted hover:bg-surface hover:text-ink'
                                        }`}
                                        aria-current={active ? 'page' : undefined}
                                    >
                                        {item.label}
                                    </button>
                                );
                            })}
                        </nav>
                    </div>
                </PageContainer>
            </section>

            <section className="py-8 md:py-10">
                <PageContainer className="max-w-5xl min-w-0 space-y-8">
                    {tab === 'overview' ? (
                        <>
                            <article className="rounded-xl border border-line bg-white p-6 sm:p-8">
                                <h2 className="text-lg font-semibold text-ink">Company overview</h2>
                                {company.about_company ? (
                                    <p className="mt-3 whitespace-pre-line text-sm leading-6 text-muted">{company.about_company}</p>
                                ) : (
                                    <p className="mt-3 text-sm text-muted">No company description has been published yet.</p>
                                )}
                            </article>

                            {company.company_details ? (
                                <article className="rounded-xl border border-line bg-white p-6 sm:p-8">
                                    <h2 className="text-lg font-semibold text-ink">Company details</h2>
                                    <p className="mt-3 whitespace-pre-line text-sm leading-6 text-muted">{company.company_details}</p>
                                </article>
                            ) : null}

                            {(company.industry || locationLine) ? (
                                <article className="rounded-xl border border-line bg-white p-6 sm:p-8">
                                    <h2 className="text-lg font-semibold text-ink">Industry & location</h2>
                                    <ul className="mt-3 space-y-1 text-sm text-muted">
                                        {company.industry ? <li>Industry: {company.industry}</li> : null}
                                        {locationLine ? <li>Locations: {locationLine}</li> : null}
                                    </ul>
                                </article>
                            ) : null}

                            {socials.length > 0 ? (
                                <article className="rounded-xl border border-line bg-white p-6 sm:p-8">
                                    <h2 className="text-lg font-semibold text-ink">Online presence</h2>
                                    <ul className="mt-3 flex flex-wrap gap-3 text-sm">
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
                                </article>
                            ) : null}

                            <article className="rounded-xl border border-line bg-white p-6 sm:p-8">
                                <div className="flex flex-wrap items-end justify-between gap-3">
                                    <h2 className="text-lg font-semibold text-ink">Open jobs</h2>
                                    {open_job_cards.length > 0 ? (
                                        <button
                                            type="button"
                                            onClick={() => selectTab(tabHref(company.id, 'jobs'))}
                                            className="text-sm font-semibold text-brand hover:underline"
                                        >
                                            View all jobs
                                        </button>
                                    ) : null}
                                </div>
                                {open_job_cards.length > 0 ? (
                                    <ul className="mt-4 space-y-4">
                                        {open_job_cards.slice(0, 3).map((job) => (
                                            <li key={job.slug}>
                                                <JobCard job={job} compact hideActions linkable={false} />
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <EmptyState
                                        className="mt-4"
                                        title="No open jobs"
                                        description="This company has no published vacancies right now."
                                        action={
                                            <Button surface="public" href="/jobs" variant="outline">
                                                Browse all jobs
                                            </Button>
                                        }
                                    />
                                )}
                            </article>
                        </>
                    ) : null}

                    {tab === 'jobs' ? (
                        <article className="space-y-4">
                            <div className="flex flex-wrap items-end justify-between gap-3">
                                <h2 className="text-lg font-semibold text-ink">Open jobs</h2>
                                <Link href={jobsBoardUrl} className="text-sm font-semibold text-brand hover:underline">
                                    View all on job board
                                </Link>
                            </div>
                            {open_job_cards.length > 0 ? (
                                <ul className="space-y-4">
                                    {open_job_cards.map((job) => (
                                        <li key={job.slug}>
                                            <JobCard job={job} compact openInWorkspace />
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <EmptyState
                                    title="No open jobs"
                                    description="This company has no published vacancies right now."
                                    action={
                                        <Button surface="public" href="/jobs" variant="outline">
                                            Browse all jobs
                                        </Button>
                                    }
                                />
                            )}
                        </article>
                    ) : null}

                    {tab === 'benefits' ? (
                        <article className="rounded-xl border border-line bg-white p-6 sm:p-8">
                            <h2 className="text-lg font-semibold text-ink">Benefits</h2>
                            {benefits.length > 0 ? (
                                <ul className="mt-4 list-disc space-y-1 pl-5 text-sm text-muted">
                                    {benefits.map((benefit) => (
                                        <li key={benefit}>{benefit}</li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="mt-3 text-sm text-muted">No benefits have been published for this company.</p>
                            )}
                        </article>
                    ) : null}

                    <p className="text-xs text-muted">Private company documents are not displayed publicly.</p>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
