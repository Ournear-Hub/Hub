import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import PublicPageHero from '@/Components/Public/PublicPageHero';
import { Button } from '@/Components/atoms';
import Card from '@/Components/atoms/Card';
import { cmsSection, type CmsPage } from '@/types/cms';
import type { SiteContent } from '@/types/public';

type AboutProps = {
    content: SiteContent;
    cms: CmsPage;
    industries?: string[];
};

function personInitials(name: string): string {
    return (
        name
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part[0])
            .join('')
            .toUpperCase() || '?'
    );
}

const missionVisionValues = [
    { key: 'mission', label: 'Mission' },
    { key: 'vision', label: 'Vision' },
    { key: 'values', label: 'Values' },
] as const;

export default function About({ content, cms, industries = [] }: AboutProps) {
    const hero = cmsSection(cms, 'hero');
    const who = cmsSection(cms, 'who_we_are');
    const what = cmsSection(cms, 'what_we_do');
    const approach = cmsSection(cms, 'our_approach');
    const why = cmsSection(cms, 'why_choose_us');
    const commitment = cmsSection(cms, 'our_commitment');
    const founder = cmsSection(cms, 'founder');
    const extra = who?.extra ?? {};
    const founderName = founder?.extra?.founder_name?.trim() || '';
    const founderTitle = founder?.extra?.founder_title?.trim() || '';
    const founderDescription =
        founder?.extra?.founder_description?.trim() || founder?.body?.trim() || '';
    const heroHasImage = Boolean(hero?.image_url);
    const pillarSections = [what, approach, why].filter(Boolean);

    return (
        <PublicLayout title="About Us">
            {heroHasImage ? (
                <section
                    className="bg-canvas"
                    style={{
                        backgroundImage: `linear-gradient(90deg, rgba(249,250,250,.94), rgba(249,250,250,.72)), url(${hero?.image_url})`,
                        backgroundSize: 'cover',
                        backgroundPosition: 'center',
                    }}
                >
                    <PageContainer className="grid min-w-0 items-center gap-8 py-14 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,.8fr)]">
                        <div className="min-w-0">
                            <p className="text-sm font-semibold uppercase tracking-wide text-brand">OurNear</p>
                            <h1 className="mt-2 text-4xl font-semibold tracking-tight text-ink sm:text-5xl">
                                {hero?.heading || 'About Us'}
                            </h1>
                            {hero?.subtitle ? (
                                <p className="mt-2 text-lg font-medium text-ink/80">{hero.subtitle}</p>
                            ) : null}
                            <p className="mt-4 max-w-2xl text-muted">{hero?.body || content.tagline}</p>
                        </div>
                        <img src={hero!.image_url!} alt="" className="h-64 w-full rounded-2xl object-cover" />
                    </PageContainer>
                </section>
            ) : (
                <PublicPageHero
                    eyebrow="OurNear"
                    title={hero?.heading || 'About OurNear'}
                    description={hero?.subtitle ? `${hero.subtitle} ${hero?.body || content.tagline}` : hero?.body || content.tagline}
                    breadcrumbs={[{ label: 'Home', href: '/' }, { label: 'About' }]}
                />
            )}

            {who ? (
                <section className="border-t border-line bg-white py-14">
                    <PageContainer className="grid min-w-0 gap-10 lg:grid-cols-2 lg:gap-12">
                        <div className="min-w-0">
                            <h2 className="text-3xl font-semibold text-ink">{who.heading || 'Who we are'}</h2>
                            {who.subtitle ? <p className="mt-2 text-lg text-muted">{who.subtitle}</p> : null}
                            {who.body ? <p className="mt-4 text-muted">{who.body}</p> : null}
                        </div>
                        <div className="grid min-w-0 gap-4">
                            {missionVisionValues.map(({ key, label }) => {
                                const value = extra[key]?.trim() || '';
                                return (
                                    <Card
                                        key={key}
                                        as="article"
                                        padding="standard"
                                        className="rounded-2xl bg-canvas/40"
                                    >
                                        <h3 className="text-sm font-semibold uppercase tracking-wide text-brand">
                                            {label}
                                        </h3>
                                        <p className="mt-2 text-sm text-muted">
                                            {value || 'Content not published yet.'}
                                        </p>
                                    </Card>
                                );
                            })}
                        </div>
                    </PageContainer>
                </section>
            ) : null}

            {pillarSections.length > 0 ? (
                <section className="py-14">
                    <PageContainer className="grid min-w-0 gap-4 md:grid-cols-3">
                        {pillarSections.map((section) => (
                            <Card
                                key={section?.key}
                                as="article"
                                padding="standard"
                                className="flex min-w-0 flex-col rounded-2xl"
                            >
                                <h2 className="text-xl font-semibold text-ink">{section?.heading}</h2>
                                {section?.subtitle ? (
                                    <p className="mt-1 text-sm font-medium text-brand">{section.subtitle}</p>
                                ) : null}
                                <p className="mt-3 flex-1 text-sm text-muted">{section?.body}</p>
                            </Card>
                        ))}
                    </PageContainer>
                </section>
            ) : null}

            {founder ? (
                <section className="border-t border-line bg-white py-14">
                    <PageContainer className="grid min-w-0 items-start gap-8 lg:grid-cols-[minmax(0,16rem)_minmax(0,1fr)] lg:items-center">
                        {founder.image_url ? (
                            <img
                                src={founder.image_url}
                                alt={founderName || founder.heading || 'Founder'}
                                className="aspect-[4/5] w-full max-w-xs rounded-2xl object-cover lg:max-w-none"
                            />
                        ) : (
                            <div
                                className="flex aspect-[4/5] w-full max-w-xs items-center justify-center rounded-2xl bg-surface text-3xl font-semibold text-muted lg:max-w-none"
                                aria-hidden={!founderName}
                            >
                                {founderName ? personInitials(founderName) : '—'}
                            </div>
                        )}
                        <div className="min-w-0">
                            <p className="text-sm font-semibold uppercase tracking-wide text-brand">
                                {founder.heading || 'Founder'}
                            </p>
                            {founderName ? (
                                <h2 className="mt-2 text-3xl font-semibold text-ink">{founderName}</h2>
                            ) : null}
                            {founderTitle ? <p className="mt-1 text-base text-muted">{founderTitle}</p> : null}
                            {founderDescription ? (
                                <p className="mt-4 max-w-2xl whitespace-pre-line text-muted">{founderDescription}</p>
                            ) : (
                                <p className="mt-4 max-w-2xl text-sm text-muted">Founder profile not published yet.</p>
                            )}
                        </div>
                    </PageContainer>
                </section>
            ) : null}

            {commitment ? (
                <section className="py-14">
                    <PageContainer>
                        <Card padding="none" className="overflow-hidden rounded-2xl shadow-sm">
                            {commitment.image_url ? (
                                <img src={commitment.image_url} alt="" className="h-48 w-full object-cover" />
                            ) : null}
                            <div className="p-8">
                                <h2 className="text-2xl font-semibold text-ink">{commitment.heading}</h2>
                                {commitment.subtitle ? (
                                    <p className="mt-1 text-sm font-medium text-brand">{commitment.subtitle}</p>
                                ) : null}
                                <p className="mt-3 max-w-3xl text-muted">{commitment.body}</p>
                            </div>
                        </Card>
                    </PageContainer>
                </section>
            ) : null}

            {industries.length > 0 ? (
                <section className="border-t border-line bg-white py-14">
                    <PageContainer className="min-w-0">
                        <h2 className="text-2xl font-semibold text-ink">Industries we serve</h2>
                        <ul className="mt-6 flex flex-wrap gap-2">
                            {industries.map((industry) => (
                                <li
                                    key={industry}
                                    className="rounded-full border border-line bg-surface px-3 py-1 text-sm text-ink"
                                >
                                    {industry}
                                </li>
                            ))}
                        </ul>
                    </PageContainer>
                </section>
            ) : null}

            <section className="bg-brand py-12 text-white">
                <PageContainer className="flex min-w-0 flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="min-w-0">
                        <h2 className="text-2xl font-semibold">Explore OurNear Jobs</h2>
                        <p className="mt-2 max-w-xl text-white/85">
                            Search open roles or get in touch if you need hiring support.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Button href="/jobs" variant="light">
                            Search jobs
                        </Button>
                        <Link
                            href="/contact"
                            className="inline-flex items-center justify-center rounded-md border border-white/40 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10"
                        >
                            Contact us
                        </Link>
                    </div>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
