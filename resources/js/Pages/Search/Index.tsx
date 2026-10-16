import { Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import Breadcrumbs from '@/Components/Navigation/Breadcrumbs';
import type { SiteContent } from '@/types/public';

type SearchResults = {
    jobs: Array<{ id: number; title: string; company: string | null; location: string | null; url: string }>;
    companies: Array<{ id: number; name: string; industry: string | null; url: string }>;
    profiles: Array<{ name: string; headline: string | null; url: string }>;
};

type Props = {
    content: SiteContent;
    query: string;
    results: SearchResults;
};

export default function SearchIndex({ query, results }: Props) {
    const hasQuery = query.trim().length >= 2;

    return (
        <PublicLayout title="Search">
            <section className="border-b border-line bg-white py-8">
                <PageContainer className="max-w-3xl">
                    <Breadcrumbs items={[{ label: 'Home', href: '/' }, { label: 'Search' }]} />
                    <h1 className="mt-4 text-2xl font-semibold text-ink">Discover on OurNear</h1>
                    <p className="mt-2 text-sm text-muted">
                        Public jobs, share-enabled companies, and candidate profiles with public sharing turned on.
                        Private resumes, unpublished jobs, and admin records are never shown here.
                    </p>
                    <form action="/search" method="get" className="mt-4 flex gap-2">
                        <input
                            type="search"
                            name="q"
                            defaultValue={query}
                            placeholder="Search jobs, companies, or profiles"
                            className="min-h-11 flex-1 rounded-md border border-line px-3 text-sm"
                            aria-label="Search jobs, companies, or public profiles"
                        />
                        <button type="submit" className="min-h-11 rounded-md bg-brand px-4 text-sm font-semibold text-white">
                            Search
                        </button>
                    </form>
                </PageContainer>
            </section>
            <section className="py-8">
                <PageContainer className="max-w-3xl space-y-8">
                    {!hasQuery ? (
                        <p className="text-sm text-muted">Enter at least two characters to search.</p>
                    ) : (
                        <>
                            <div>
                                <h2 className="text-lg font-semibold text-ink">Jobs</h2>
                                {results.jobs.length === 0 ? (
                                    <p className="mt-2 text-sm text-muted">No published jobs matched your search.</p>
                                ) : (
                                    <ul className="mt-3 space-y-2">
                                        {results.jobs.map((job) => (
                                            <li key={job.id}>
                                                <Link href={job.url} className="block rounded-lg border border-line bg-white p-3 hover:border-brand/40">
                                                    <p className="font-medium text-ink">{job.title}</p>
                                                    <p className="text-xs text-muted">
                                                        {[job.company, job.location].filter(Boolean).join(' · ')}
                                                    </p>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                            <div>
                                <h2 className="text-lg font-semibold text-ink">Companies</h2>
                                {results.companies.length === 0 ? (
                                    <p className="mt-2 text-sm text-muted">No public company profiles matched your search.</p>
                                ) : (
                                    <ul className="mt-3 space-y-2">
                                        {results.companies.map((company) => (
                                            <li key={company.id}>
                                                <Link
                                                    href={company.url}
                                                    className="block rounded-lg border border-line bg-white p-3 hover:border-brand/40"
                                                >
                                                    <p className="font-medium text-ink">{company.name}</p>
                                                    {company.industry ? (
                                                        <p className="text-xs text-muted">{company.industry}</p>
                                                    ) : null}
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                            <div>
                                <h2 className="text-lg font-semibold text-ink">Public profiles</h2>
                                {results.profiles.length === 0 ? (
                                    <p className="mt-2 text-sm text-muted">No public candidate profiles matched your search.</p>
                                ) : (
                                    <ul className="mt-3 space-y-2">
                                        {results.profiles.map((profile) => (
                                            <li key={profile.url}>
                                                <Link
                                                    href={profile.url}
                                                    className="block rounded-lg border border-line bg-white p-3 hover:border-brand/40"
                                                >
                                                    <p className="font-medium text-ink">{profile.name}</p>
                                                    {profile.headline ? (
                                                        <p className="text-xs text-muted">{profile.headline}</p>
                                                    ) : null}
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </>
                    )}
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
