import { router, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/Public/Button';
import PublicPageHero from '@/Components/Public/PublicPageHero';
import PageContainer from '@/Components/Public/PageContainer';
import Card from '@/Components/UI/Card';
import EmptyState from '@/Components/UI/EmptyState';
import SectionHeader from '@/Components/UI/SectionHeader';
import { cmsSection, type CmsPage } from '@/types/cms';
import type { SharedPageProps } from '@/types/page';
import type { SiteContent } from '@/types/public';

type Person = { name: string; username: string; headline: string | null; location: string | null; url: string };

const steps = [
    ['Share your requirement', 'Role, skills, location, and headcount through the contact form.'],
    ['Review public profiles', 'OurNear surfaces candidates who allow a public profile.'],
    ['Recruiter shortlists', 'Shortlisting stays a human decision after review.'],
];

type Props = {
    content: SiteContent;
    cms?: CmsPage;
    people: Person[];
    filters: {
        q: string;
        location: string;
    };
    result_count: number;
};

export default function TalentPool({ content, cms, people, filters, result_count }: Props) {
    const user = usePage<SharedPageProps>().props.auth.user;
    const hero = cmsSection(cms, 'hero');
    const how = cmsSection(cms, 'how_it_works');
    const cta = cmsSection(cms, 'cta');
    const [q, setQ] = useState(filters.q);
    const [location, setLocation] = useState(filters.location);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        router.get(
            '/talent-pool',
            {
                q: q.trim() || undefined,
                location: location.trim() || undefined,
            },
            { preserveScroll: true },
        );
    };

    const clearFilters = () => {
        setQ('');
        setLocation('');
        router.get('/talent-pool', {}, { preserveScroll: true });
    };

    const activeFilters = [filters.q, filters.location].filter(Boolean).length;

    return (
        <PublicLayout title="Talent Pool">
            <PublicPageHero
                eyebrow="OurNear Hire"
                title={hero?.heading || 'Find the talent you need'}
                description={hero?.body || content.tagline}
                breadcrumbs={[{ label: 'Home', href: '/' }, { label: 'Talent Pool' }]}
                actions={
                    <>
                        <Button href="/contact" variant="primary">
                            {cta?.primary_button_label || 'Submit a requirement'}
                        </Button>
                        <Button href={user ? '/account/hiring' : '/hire'} variant="outline" className="border-white/30 bg-transparent text-white hover:bg-white/10">
                            {user ? 'Enter Hiring Workspace' : 'Hire Talent'}
                        </Button>
                    </>
                }
            />

            <section className="border-b border-line bg-canvas py-10 md:py-12">
                <PageContainer>
                    <SectionHeader title={how?.heading || 'How the Talent Pool works'} description={how?.body ?? undefined} />
                    <div className="mt-6 grid gap-4 md:grid-cols-3">
                        {steps.map(([title, body]) => (
                            <Card key={title} as="article" density="standard">
                                <h3 className="font-semibold text-ink">{title}</h3>
                                <p className="mt-2 text-sm text-muted">{body}</p>
                            </Card>
                        ))}
                    </div>
                </PageContainer>
            </section>

            <section className="py-10 md:py-12">
                <PageContainer className="min-w-0">
                    <SectionHeader
                        title="Public candidate profiles"
                        description="Discovery for candidates who enable a public profile. This is separate from job applicants in your hiring workspace."
                    />
                    <form
                        onSubmit={submit}
                        className="mt-6 rounded-xl border border-line bg-white p-4 sm:p-5"
                        aria-labelledby="talent-pool-search"
                    >
                        <h2 id="talent-pool-search" className="text-base font-semibold text-ink">
                            Search candidates
                        </h2>
                        <div className="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
                            <label className="block text-sm">
                                <span className="font-medium text-ink">Keyword</span>
                                <input
                                    className="mt-1 min-h-11 w-full rounded-md border border-line px-3 py-2 text-sm"
                                    placeholder="Name or role"
                                    value={q}
                                    onChange={(event) => setQ(event.target.value)}
                                />
                            </label>
                            <label className="block text-sm">
                                <span className="font-medium text-ink">Location</span>
                                <input
                                    className="mt-1 min-h-11 w-full rounded-md border border-line px-3 py-2 text-sm"
                                    placeholder="City or region"
                                    value={location}
                                    onChange={(event) => setLocation(event.target.value)}
                                />
                            </label>
                            <Button type="submit" variant="secondary" className="min-h-11 w-full md:w-auto">
                                Search
                            </Button>
                        </div>
                        <p className="mt-3 text-sm text-muted">
                            {result_count} profile{result_count === 1 ? '' : 's'} shown
                            {activeFilters > 0 ? ` · ${activeFilters} filter${activeFilters === 1 ? '' : 's'} active` : ''}
                        </p>
                        {activeFilters > 0 ? (
                            <button type="button" onClick={clearFilters} className="mt-2 text-sm font-semibold text-brand hover:underline">
                                Clear filters
                            </button>
                        ) : null}
                    </form>

                    {people.length === 0 ? (
                        <EmptyState
                            className="mt-6"
                            title={activeFilters > 0 ? 'No candidates match your filters' : 'No public profiles yet'}
                            description={
                                activeFilters > 0
                                    ? 'Try broader keywords or clear filters.'
                                    : 'When candidates allow a public profile, they can appear in the Talent Pool.'
                            }
                            action={
                                activeFilters > 0 ? (
                                    <Button type="button" variant="secondary" onClick={clearFilters}>
                                        Clear filters
                                    </Button>
                                ) : (
                                    <Button href="/contact" variant="secondary">
                                        Contact Us
                                    </Button>
                                )
                            }
                        />
                    ) : (
                        <div className="mt-6 grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {people.map((person) => (
                                <Card key={person.username} as="article" density="compact" className="flex flex-col">
                                    <h3 className="truncate font-semibold text-ink">
                                        <Link href={person.url} className="hover:text-brand">
                                            {person.name}
                                        </Link>
                                    </h3>
                                    {person.headline ? (
                                        <p className="mt-1 line-clamp-2 text-sm text-ink">{person.headline}</p>
                                    ) : null}
                                    {person.location ? <p className="mt-1 truncate text-sm text-muted">{person.location}</p> : null}
                                    <Link
                                        href={person.url}
                                        className="mt-auto inline-flex min-h-11 items-center pt-3 text-sm font-semibold text-brand hover:underline"
                                    >
                                        View profile
                                    </Link>
                                </Card>
                            ))}
                        </div>
                    )}
                </PageContainer>
            </section>

            <section className="border-t border-line bg-canvas py-10">
                <PageContainer className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-lg font-semibold text-ink">Need workforce deployment?</h2>
                        <p className="mt-1 text-sm text-muted">Explore OurNear Staffing for manpower requests.</p>
                    </div>
                    <Button href="/staffing" variant="outline">
                        Staffing Solutions
                    </Button>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
