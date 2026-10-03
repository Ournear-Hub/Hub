import type { FormEvent } from 'react';
import { Link, router } from '@inertiajs/react';
import JobCard from '@/Components/Public/JobCard';
import Button from '@/Components/Public/Button';
import PageContainer from '@/Components/Public/PageContainer';
import PublicCompanyCard, { type PublicCompanyCardData } from '@/Components/Public/PublicCompanyCard';
import Card from '@/Components/UI/Card';
import EmptyState from '@/Components/UI/EmptyState';
import SectionHeader from '@/Components/UI/SectionHeader';
import PublicLayout from '@/Layouts/PublicLayout';
import { controlBase } from '@/design-system/classNames';
import { hireTalentCtaLabel } from '@/utils/publicCtaLabels';
import type { PublicJobCard } from '@/types/jobs';
import { cmsSection, type CmsPage } from '@/types/cms';
import type { SiteContent } from '@/types/public';

type HomeProps = {
    content: SiteContent;
    cms: CmsPage;
    featuredJobs: PublicJobCard[];
    latestJobs?: PublicJobCard[];
    companies?: PublicCompanyCardData[];
};

const whyPoints = [
    'Search and apply to published jobs from one candidate profile.',
    'Build and maintain resume information for applications.',
    'Employers can post roles and review applicants through OurNear Hire.',
    'Discover talent through the public Talent Pool where profiles allow it.',
    'Staffing enquiries use the same platform contact and enquiry flow.',
];

const serviceAreas = [
    {
        title: 'OurNear Hire',
        body: 'For organizations that need recruitment support—publish jobs, review applications, and manage hiring in one workspace.',
        href: '/hire',
        cta: 'Hire Talent',
    },
    {
        title: 'Talent Pool',
        body: 'Browse candidates who choose to share a public profile and discover skills relevant to your roles.',
        href: '/talent-pool',
        cta: 'Explore Talent',
    },
    {
        title: 'Staffing',
        body: 'Workforce and manpower support for operational teams—start with a staffing enquiry on OurNear.',
        href: '/staffing',
        cta: 'Staffing Solutions',
    },
] as const;

export default function Home({ content, cms, featuredJobs, latestJobs = [], companies = [] }: HomeProps) {
    const hero = cmsSection(cms, 'hero');
    const business = cmsSection(cms, 'business_solutions');
    const why = cmsSection(cms, 'why_choose_us');
    const cta = cmsSection(cms, 'home_cta');

    const recentJobs = latestJobs.length > 0 ? latestJobs : featuredJobs;
    const featuredOnly = featuredJobs.filter((job) => !recentJobs.some((recent) => recent.slug === job.slug));

    const search = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get('/jobs', {
            keyword: String(form.get('keyword') ?? ''),
            location: String(form.get('location') ?? ''),
        });
    };

    const heroHeading = hero?.heading?.replace(/\n/g, ' ') || 'Right people. Right training. Better results.';
    const heroBody =
        hero?.body ||
        content.tagline ||
        'OurNear connects job seekers with open roles and gives hiring teams tools for recruitment, talent discovery, and staffing support.';

    return (
        <PublicLayout title="Home">
            <section className="border-b border-line bg-brand text-white">
                <PageContainer className="py-10 sm:py-12 lg:py-14">
                    <div className="mx-auto max-w-3xl text-center lg:mx-0 lg:max-w-2xl lg:text-left">
                        <p className="text-xs font-semibold uppercase tracking-wide text-white/80">OurNear Jobs · OurNear Hire</p>
                        <h1 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl lg:text-[2.5rem] lg:leading-tight">
                            {heroHeading}
                        </h1>
                        <p className="mt-4 text-sm leading-relaxed text-white/85 sm:text-base">{heroBody}</p>
                        <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:justify-center lg:justify-start">
                            <Button href="/jobs" variant="primary" className="w-full sm:w-auto">
                                {hero?.primary_button_label || 'Find Jobs'}
                            </Button>
                            <Button href="/hire" variant="outline" className="w-full border-white/30 bg-transparent text-white hover:bg-white/10 sm:w-auto">
                                {hireTalentCtaLabel(hero?.secondary_button_label)}
                            </Button>
                        </div>
                    </div>

                    <form
                        onSubmit={search}
                        className="mt-8 grid min-w-0 gap-3 rounded-xl border border-white/15 bg-white p-3 text-ink shadow-sm sm:p-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]"
                    >
                        <div className="min-w-0">
                            <label htmlFor="home-keyword" className="mb-1 block text-xs font-medium text-muted">
                                Job title or keyword
                            </label>
                            <input
                                id="home-keyword"
                                name="keyword"
                                placeholder="e.g. Site Engineer"
                                className={controlBase}
                            />
                        </div>
                        <div className="min-w-0">
                            <label htmlFor="home-location" className="mb-1 block text-xs font-medium text-muted">
                                Location
                            </label>
                            <input id="home-location" name="location" placeholder="City or region" className={controlBase} />
                        </div>
                        <div className="flex items-end">
                            <Button type="submit" variant="secondary" className="w-full md:w-auto">
                                Search Jobs
                            </Button>
                        </div>
                    </form>
                </PageContainer>
            </section>

            {featuredOnly.length > 0 ? (
                <section className="py-10 md:py-12">
                    <PageContainer className="min-w-0">
                        <SectionHeader
                            title="Featured roles"
                            description="Highlighted openings from employers on OurNear Jobs."
                            action={
                                <Link href="/jobs?featured=1" className="text-sm font-semibold text-brand hover:underline">
                                    View all jobs
                                </Link>
                            }
                        />
                        <div className="mt-6 grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {featuredOnly.slice(0, 3).map((job) => (
                                <JobCard key={job.slug} job={job} compact />
                            ))}
                        </div>
                    </PageContainer>
                </section>
            ) : null}

            <section className={`${featuredOnly.length > 0 ? 'bg-canvas' : ''} py-10 md:py-12`}>
                <PageContainer className="min-w-0">
                    <SectionHeader
                        title="Recent jobs"
                        description="Latest published roles. Sign in to apply with your profile and resume."
                        action={
                            <Link href="/jobs" className="text-sm font-semibold text-brand hover:underline">
                                View all jobs
                            </Link>
                        }
                    />
                    {recentJobs.length === 0 ? (
                        <EmptyState
                            className="mt-6"
                            title="No open jobs yet"
                            description="When employers publish roles, they will appear here and on the jobs board."
                            action={
                                <Button href="/hire" variant="secondary">
                                    Hire Talent
                                </Button>
                            }
                        />
                    ) : (
                        <div className="mt-6 grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {recentJobs.slice(0, 6).map((job) => (
                                <JobCard key={job.slug} job={job} compact />
                            ))}
                        </div>
                    )}
                </PageContainer>
            </section>

            <section className="border-y border-line bg-white py-10 md:py-12">
                <PageContainer className="min-w-0">
                    <SectionHeader
                        title="Companies hiring on OurNear"
                        description="Explore employers with public company pages and open roles."
                        action={
                            <Link href="/companies" className="text-sm font-semibold text-brand hover:underline">
                                View all companies
                            </Link>
                        }
                    />
                    {companies.length === 0 ? (
                        <EmptyState
                            className="mt-6"
                            title="Companies will appear here"
                            description="When employers enable a public company page, they are listed on OurNear Jobs."
                            action={
                                <Button href="/companies" variant="outline">
                                    Browse companies
                                </Button>
                            }
                        />
                    ) : (
                        <div className="mt-6 grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {companies.map((company) => (
                                <PublicCompanyCard key={company.id} company={company} compact />
                            ))}
                        </div>
                    )}
                </PageContainer>
            </section>

            <section className="py-10 md:py-12">
                <PageContainer className="min-w-0">
                    <SectionHeader
                        eyebrow="For employers"
                        title={business?.heading ?? 'Recruitment, talent, and staffing'}
                        description={
                            business?.body ??
                            'OurNear Hire, Talent Pool, and Staffing work on the same platform records—choose the path that fits your team.'
                        }
                    />
                    <div className="mt-8 grid min-w-0 gap-4 md:grid-cols-3">
                        {serviceAreas.map((area) => (
                            <Card key={area.title} as="article" density="standard" className="flex flex-col">
                                <h3 className="text-base font-semibold text-ink">{area.title}</h3>
                                <p className="mt-2 flex-1 text-sm text-muted">{area.body}</p>
                                <Button href={area.href} variant="link" className="mt-4 self-start">
                                    {area.cta}
                                </Button>
                            </Card>
                        ))}
                    </div>
                </PageContainer>
            </section>

            <section className="bg-canvas py-10 md:py-12">
                <PageContainer className="min-w-0">
                    <SectionHeader
                        title={why?.heading ?? 'Why OurNear'}
                        description={
                            why?.body ??
                            'A practical recruitment platform for candidates and hiring teams—built around jobs, profiles, and supported services.'
                        }
                    />
                    <ul className="mt-6 grid min-w-0 gap-3 sm:grid-cols-2">
                        {whyPoints.map((point) => (
                            <li key={point} className="flex gap-2 text-sm text-ink">
                                <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-accent" aria-hidden="true" />
                                {point}
                            </li>
                        ))}
                    </ul>
                </PageContainer>
            </section>

            <section className="py-10 md:py-12">
                <PageContainer>
                    <div className="grid min-w-0 gap-6 rounded-2xl border border-line bg-white p-6 md:grid-cols-2 md:items-center md:p-8">
                        <div className="min-w-0">
                            <h2 className="text-xl font-semibold text-ink sm:text-2xl">
                                {cta?.heading ?? 'Find work or hire with confidence'}
                            </h2>
                            <p className="mt-2 text-sm text-muted">
                                {cta?.body ?? 'Candidates search jobs on OurNear Jobs. Employers and recruiters start with OurNear Hire.'}
                            </p>
                        </div>
                        <div className="flex flex-col gap-3 sm:flex-row md:justify-end">
                            <Button href="/jobs" variant="secondary" className="w-full sm:w-auto">
                                Find Your Next Opportunity
                            </Button>
                            <Button href="/hire" variant="primary" className="w-full sm:w-auto">
                                Hire the Right People
                            </Button>
                        </div>
                    </div>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
