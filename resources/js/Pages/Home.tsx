import type { FormEvent } from 'react';
import { useRef } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import JobCard from '@/Components/Public/JobCard';
import Button from '@/Components/atoms/Button';
import PageContainer from '@/Components/Public/PageContainer';
import EmptyState from '@/Components/UI/EmptyState';
import HomeAudienceFeatureCards, { type AudienceFeatureCard } from '@/Components/Public/HomeAudienceFeatureCards';
import HomeAtAGlance, { type HomeGlanceStat } from '@/Components/Public/HomeAtAGlance';
import HomeHeroSlider, { type HomeHeroSlide } from '@/Components/Public/HomeHeroSlider';
import HomeIndustriesScroller, { type HomeIndustry } from '@/Components/Public/HomeIndustriesScroller';
import ShowcaseCompanyGrid, { type ShowcaseCompanyPublic } from '@/Components/Public/ShowcaseCompanyGrid';
import HomePopularSearches, { type PopularSearchDiscovery } from '@/Components/Public/HomePopularSearches';
import HomePromoBanner, { type HomePromoBannerData } from '@/Components/Public/HomePromoBanner';
import HomeRecruitmentProcess from '@/Components/Public/HomeRecruitmentProcess';
import PublicLayout from '@/Layouts/PublicLayout';
import { controlBase } from '@/design-system/classNames';
import { defaultCandidateFeatures, defaultHiringFeatures } from '@/content/homeAudienceFallbacks';
import type { PublicJobCard } from '@/types/jobs';
import { cmsSection, type CmsPage } from '@/types/cms';
import type { SharedPageProps } from '@/types/page';
import { resolveHiringWorkspaceHref, resolvePostJobHref } from '@/utils/employerHiringEntry';

type HomeProps = {
    cms: CmsPage;
    heroSlides: HomeHeroSlide[];
    popularSearchDiscovery: PopularSearchDiscovery;
    hiringFeatures: { title: string; body: string; icon: string; href: string }[];
    candidateFeatures: { title: string; body: string; icon: string; href: string }[];
    industries: HomeIndustry[];
    promoBanner: HomePromoBannerData | null;
    featuredJobs: PublicJobCard[];
    latestJobs?: PublicJobCard[];
    homeGlanceStats?: HomeGlanceStat[];
    homeShowcaseCompanies?: ShowcaseCompanyPublic[];
};

const HIRING_TITLES = ['Post a Job', 'Search Talent', 'Manage Applications', 'Interview & Schedule'] as const;
const CANDIDATE_TITLES = ['Find Jobs', 'Create Profile', 'Upload Resume', 'Internships'] as const;

const defaultPromoBanner: HomePromoBannerData = {
    eyebrow: 'ONE PLATFORM. EVERY HIRING NEED.',
    heading: 'Smarter Hiring. Better Teams.',
    body: 'Use OurNear Hire to post jobs, manage talent and coordinate recruitment through one connected platform.',
    primary_label: 'Explore ATS',
    primary_href: '/hire',
    image_url: null,
};

function mergeByTitle(
    order: readonly string[],
    cms: { title: string; body: string; icon: string; href: string }[],
    defaults: AudienceFeatureCard[],
): AudienceFeatureCard[] {
    const pool = new Map<string, AudienceFeatureCard>();
    defaults.forEach((item) => pool.set(item.title, item));
    cms.forEach((item) => pool.set(item.title, { ...item, href: item.href }));

    return order
        .map((title) => pool.get(title))
        .filter((item): item is AudienceFeatureCard => item !== undefined);
}

function resolveHiringHref(title: string, user: SharedPageProps['auth']['user']): string | null {
    switch (title) {
        case 'Post a Job':
            return resolvePostJobHref(user);
        case 'Search Talent':
            return '/talent-pool';
        case 'Manage Applications':
            return resolveHiringWorkspaceHref(user, '/account/hiring/applications');
        case 'Interview & Schedule':
            return resolveHiringWorkspaceHref(user, '/account/hiring/applications');
        default:
            return null;
    }
}

function resolveCandidateHref(title: string, user: SharedPageProps['auth']['user']): string | null {
    switch (title) {
        case 'Find Jobs':
            return '/jobs';
        case 'Create Profile':
            return user ? '/account/profile' : '/login';
        case 'Upload Resume':
            return user ? '/account/resumes' : '/login';
        case 'Internships':
            return null;
        default:
            return null;
    }
}

export default function Home({
    cms,
    heroSlides,
    popularSearchDiscovery,
    hiringFeatures,
    candidateFeatures,
    industries,
    promoBanner,
    featuredJobs,
    latestJobs = [],
    homeGlanceStats = [],
    homeShowcaseCompanies = [],
}: HomeProps) {
    const { auth } = usePage<SharedPageProps>().props;
    const user = auth.user;
    const industriesSection = cmsSection(cms, 'industries');
    const keywordRef = useRef<HTMLInputElement>(null);
    const locationRef = useRef<HTMLInputElement>(null);
    const latest = latestJobs.length > 0 ? latestJobs : featuredJobs;

    const hiringCards = mergeByTitle(HIRING_TITLES, hiringFeatures, defaultHiringFeatures).map((card) => ({
        ...card,
        href: resolveHiringHref(card.title, user),
    }));

    const candidateCards = mergeByTitle(CANDIDATE_TITLES, candidateFeatures, defaultCandidateFeatures).map((card) => ({
        ...card,
        href: resolveCandidateHref(card.title, user),
    }));

    const search = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get('/jobs', {
            keyword: String(form.get('keyword') ?? ''),
            location: String(form.get('location') ?? ''),
        });
    };

    const applyPopularChip = (item: PopularSearchDiscovery['designations'][number]) => {
        if (keywordRef.current) {
            keywordRef.current.value = item.label;
        }
    };

    return (
        <PublicLayout title="Home">
            <HomeHeroSlider slides={heroSlides} />

            <HomeAtAGlance stats={homeGlanceStats} />

            <HomeIndustriesScroller
                industries={industries}
                heading={industriesSection?.heading ?? 'Explore industries'}
                subheading={industriesSection?.body ?? 'Sectors where OurNear Hire supports recruitment.'}
            />

            <ShowcaseCompanyGrid title="Our Companies" companies={homeShowcaseCompanies} id="home-our-companies-heading" />

            <section className="border-b border-line bg-white py-6 md:py-7">
                <PageContainer className="min-w-0">
                    <form
                        onSubmit={search}
                        className="grid min-w-0 gap-3 rounded-xl border border-line bg-canvas p-3 shadow-sm sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:p-4"
                    >
                        <div className="min-w-0">
                            <label htmlFor="home-keyword" className="mb-1 block text-xs font-medium text-muted">
                                Job title, skill or keyword
                            </label>
                            <input
                                ref={keywordRef}
                                id="home-keyword"
                                name="keyword"
                                autoComplete="off"
                                placeholder="e.g. Site Engineer…"
                                className={controlBase}
                            />
                        </div>
                        <div className="min-w-0">
                            <label htmlFor="home-location" className="mb-1 block text-xs font-medium text-muted">
                                Location
                            </label>
                            <input
                                ref={locationRef}
                                id="home-location"
                                name="location"
                                autoComplete="off"
                                placeholder="City or region…"
                                className={controlBase}
                            />
                        </div>
                        <div className="flex items-end">
                            <Button surface="public" type="submit" variant="primary" className="w-full sm:w-auto">
                                Search Jobs
                            </Button>
                        </div>
                    </form>
                    <HomePopularSearches discovery={popularSearchDiscovery} onSelect={applyPopularChip} />
                </PageContainer>
            </section>

            <HomeAudienceFeatureCards
                eyebrow="For employers"
                title="HIRE & MANAGE TALENT"
                items={hiringCards}
                variant="employer"
            />
            <HomeAudienceFeatureCards
                eyebrow="For candidates"
                title="Find Jobs on ON Hire"
                items={candidateCards}
                variant="candidate"
            />

            <HomeRecruitmentProcess />

            <section className="bg-canvas py-8 md:py-10">
                <PageContainer className="min-w-0">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h2 className="text-lg font-semibold text-ink sm:text-xl">Latest Jobs</h2>
                        <Link href="/jobs" className="text-sm font-semibold text-brand hover:underline">
                            View All Jobs
                        </Link>
                    </div>
                    {latest.length > 0 ? (
                        <div className="mt-5 grid min-w-0 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {latest.slice(0, 6).map((job) => (
                                <JobCard key={job.slug} job={job} compact openInWorkspace />
                            ))}
                        </div>
                    ) : (
                        <div className="mt-4">
                            <EmptyState
                                className="px-5 py-6"
                                title="No open jobs yet"
                                description="Published roles from employers will appear here and on the jobs board."
                                action={
                                    <Button surface="public" href="/jobs" variant="secondary">
                                        Browse Jobs Board
                                    </Button>
                                }
                            />
                        </div>
                    )}
                </PageContainer>
            </section>

            <HomePromoBanner banner={promoBanner ?? defaultPromoBanner} />
        </PublicLayout>
    );
}
