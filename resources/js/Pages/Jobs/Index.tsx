import type { FormEvent } from 'react';
import { router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/Public/Button';
import PublicPageHero from '@/Components/Public/PublicPageHero';
import JobsSearchPanel, { countActiveJobFilters, type JobsFilterValues } from '@/Components/Public/JobsSearchPanel';
import EmptyState from '@/Components/UI/EmptyState';
import JobCard from '@/Components/Public/JobCard';
import JobDetail from '@/Components/Public/JobDetail';
import PageContainer from '@/Components/Public/PageContainer';
import Pagination from '@/Components/Pagination';
import type { Paginator, PublicJobCard, PublicJobDetail } from '@/types/jobs';

type Filters = JobsFilterValues;

type Options = {
    locations: string[];
    industries: string[];
    categories: string[];
    employment_types: string[];
    experiences: string[];
    companies?: string[];
    salaries?: string[];
};

type JobsIndexProps = {
    jobs: Paginator<PublicJobCard>;
    selected: PublicJobDetail | null;
    filters: Filters;
    options: Options;
};

function buildFilterQuery(filters: Filters, jobSlug?: string) {
    return {
        keyword: filters.keyword,
        location: filters.location,
        industry: filters.industry,
        category: filters.category,
        employment_type: filters.employment_type,
        experience: filters.experience,
        company: filters.company,
        salary: filters.salary,
        work_mode: filters.work_mode,
        sort: filters.sort,
        featured: filters.featured ? 1 : undefined,
        job: jobSlug,
    };
}

export default function JobsIndex({ jobs, selected, filters, options }: JobsIndexProps) {
    const activeFilters = countActiveJobFilters(filters);

    const search = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get(
            '/jobs',
            {
                keyword: String(form.get('keyword') ?? ''),
                location: String(form.get('location') ?? ''),
                industry: String(form.get('industry') ?? ''),
                category: String(form.get('category') ?? ''),
                employment_type: String(form.get('employment_type') ?? ''),
                experience: String(form.get('experience') ?? ''),
                company: String(form.get('company') ?? ''),
                salary: String(form.get('salary') ?? ''),
                work_mode: String(form.get('work_mode') ?? ''),
                sort: String(form.get('sort') ?? ''),
                featured: form.get('featured') ? 1 : undefined,
                job: selected?.slug,
            },
            { preserveState: true },
        );
    };

    const clearFilters = () => {
        router.get('/jobs', selected?.slug ? { job: selected.slug } : {}, { preserveState: true });
    };

    const selectJob = (slug: string) => {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            router.get('/jobs', buildFilterQuery(filters, slug), { preserveState: true, preserveScroll: true, replace: true });
            return;
        }

        router.get(`/jobs/${slug}`);
    };

    return (
        <PublicLayout title="Jobs">
            <PublicPageHero
                eyebrow="OurNear Jobs"
                title="Jobs"
                description="Browse open roles. Sign in as a candidate to apply with your profile and resume."
                breadcrumbs={[{ label: 'Home', href: '/' }, { label: 'Jobs' }]}
            />

            <section className="border-b border-line bg-canvas pb-8 lg:pb-4">
                <PageContainer className="min-w-0 py-6 lg:sticky lg:top-14 lg:z-30 lg:border-b lg:border-line lg:bg-canvas/95 lg:py-4 lg:backdrop-blur-[2px]">
                    <p className="mb-3 text-sm text-muted lg:hidden">
                        Tap a job to open the full role. Save and apply stay on the card.
                    </p>

                    <JobsSearchPanel
                        filters={filters}
                        options={options}
                        onSubmit={search}
                        extraActions={
                            activeFilters > 0 ? (
                                <Button type="button" variant="ghost" className="hidden shrink-0 md:inline-flex" onClick={clearFilters}>
                                    Clear
                                </Button>
                            ) : null
                        }
                    />

                    <div className="mt-4 flex flex-wrap items-center justify-between gap-2">
                        <p className="text-sm text-muted">
                            {jobs.total === 0
                                ? 'No matching roles are open at the moment.'
                                : `${jobs.total} open ${jobs.total === 1 ? 'job' : 'jobs'}.`}
                            {activeFilters > 0 ? (
                                <span className="ml-2 rounded-full bg-brand/10 px-2 py-0.5 text-xs font-medium text-brand">
                                    {activeFilters} filter{activeFilters === 1 ? '' : 's'} active
                                </span>
                            ) : null}
                        </p>
                        {activeFilters > 0 ? (
                            <button type="button" className="text-sm font-semibold text-brand hover:underline" onClick={clearFilters}>
                                Clear filters
                            </button>
                        ) : null}
                    </div>
                </PageContainer>

                <PageContainer className="min-w-0 pb-8 lg:pb-6">
                    {jobs.data.length === 0 ? (
                        <EmptyState
                            className="mt-4"
                            title="No matching roles"
                            description="Try a wider search or clear filters to see more openings."
                            action={
                                activeFilters > 0 ? (
                                    <Button type="button" variant="secondary" onClick={clearFilters}>
                                        Clear filters
                                    </Button>
                                ) : (
                                    <Button href="/" variant="outline">
                                        Back to home
                                    </Button>
                                )
                            }
                        />
                    ) : (
                        <>
                            <div className="mt-4 space-y-2 lg:hidden">
                                {jobs.data.map((job) => (
                                    <JobCard key={job.slug} job={job} onSelect={selectJob} list />
                                ))}
                                <Pagination links={jobs.links} />
                            </div>

                            <div className="mt-4 hidden h-[min(720px,calc(100dvh-15.5rem))] max-h-[calc(100dvh-15.5rem)] overflow-hidden rounded-xl border border-line bg-white shadow-sm lg:grid lg:grid-cols-[minmax(18rem,22rem)_minmax(0,1fr)]">
                                <div className="flex h-full min-h-0 flex-col border-line lg:border-r">
                                    <div className="flex-1 space-y-2 overflow-y-auto overscroll-contain p-2" aria-label="Job results">
                                        {jobs.data.map((job) => (
                                            <JobCard
                                                key={job.slug}
                                                job={job}
                                                selected={selected?.slug === job.slug}
                                                onSelect={selectJob}
                                                list
                                            />
                                        ))}
                                    </div>
                                    <div className="shrink-0 border-t border-line bg-white p-2">
                                        <Pagination links={jobs.links} />
                                    </div>
                                </div>
                                <div className="hidden h-full min-h-0 flex-col lg:flex" aria-label="Job detail">
                                    {selected ? (
                                        <JobDetail job={selected} layout="pane" />
                                    ) : (
                                        <div className="flex flex-1 items-center justify-center p-6">
                                            <EmptyState
                                                title="Select a job"
                                                description="Choose a role from the list to read the full description and apply."
                                            />
                                        </div>
                                    )}
                                </div>
                            </div>
                        </>
                    )}
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
