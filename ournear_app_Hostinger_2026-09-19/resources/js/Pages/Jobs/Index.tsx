import type { FormEvent } from 'react';
import { router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/Public/Button';
import FormField from '@/Components/Public/FormField';
import JobCard from '@/Components/Public/JobCard';
import JobDetail from '@/Components/Public/JobDetail';
import PageContainer from '@/Components/Public/PageContainer';
import Pagination from '@/Components/Pagination';
import type { Paginator, PublicJobCard, PublicJobDetail } from '@/types/jobs';
import type { SiteContent } from '@/types/public';

type Filters = {
    keyword: string;
    location: string;
    industry: string;
    category: string;
    employment_type: string;
    experience: string;
    featured: boolean;
};

type Options = {
    locations: string[];
    industries: string[];
    categories: string[];
    employment_types: string[];
    experiences: string[];
};

type JobsIndexProps = {
    content: SiteContent;
    jobs: Paginator<PublicJobCard>;
    selected: PublicJobDetail | null;
    filters: Filters;
    options: Options;
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function JobsIndex({ content, jobs, selected, filters, options }: JobsIndexProps) {
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
                featured: form.get('featured') ? 1 : undefined,
            },
            { preserveState: true },
        );
    };

    const filterQuery = {
        keyword: filters.keyword,
        location: filters.location,
        industry: filters.industry,
        category: filters.category,
        employment_type: filters.employment_type,
        experience: filters.experience,
        featured: filters.featured ? 1 : undefined,
    };

    const selectJob = (slug: string) => {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            router.get('/jobs', { ...filterQuery, job: slug }, { preserveState: true, preserveScroll: true, replace: true });
            return;
        }

        router.get(`/jobs/${slug}`);
    };

    return (
        <PublicLayout title="Jobs">
            <section className="bg-brand py-10 text-white">
                <PageContainer>
                    <p className="text-sm font-medium text-accent">{content.tagline}</p>
                    <h1 className="mt-3 text-4xl font-semibold">Jobs</h1>
                    <p className="mt-3 max-w-2xl text-white/80">
                        Browse open roles. Sign in as a candidate to apply with your profile and resume.
                    </p>
                </PageContainer>
            </section>
            <section className="py-8">
                <PageContainer>
                    <form onSubmit={search} className="grid gap-3 rounded-xl border border-line bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
                        <FormField id="job-keywords" name="keyword" label="Keywords" defaultValue={filters.keyword} />
                        <div>
                            <label htmlFor="job-location" className="mb-1 block text-sm font-medium text-ink">
                                Location
                            </label>
                            <select id="job-location" name="location" defaultValue={filters.location} className={selectClass}>
                                <option value="">All locations</option>
                                {options.locations.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="job-industry" className="mb-1 block text-sm font-medium text-ink">
                                Industry
                            </label>
                            <select id="job-industry" name="industry" defaultValue={filters.industry} className={selectClass}>
                                <option value="">All industries</option>
                                {options.industries.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="job-category" className="mb-1 block text-sm font-medium text-ink">
                                Category
                            </label>
                            <select id="job-category" name="category" defaultValue={filters.category} className={selectClass}>
                                <option value="">All categories</option>
                                {options.categories.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="job-type" className="mb-1 block text-sm font-medium text-ink">
                                Employment type
                            </label>
                            <select id="job-type" name="employment_type" defaultValue={filters.employment_type} className={selectClass}>
                                <option value="">All types</option>
                                {options.employment_types.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="job-experience" className="mb-1 block text-sm font-medium text-ink">
                                Experience
                            </label>
                            <select id="job-experience" name="experience" defaultValue={filters.experience} className={selectClass}>
                                <option value="">All experience</option>
                                {options.experiences.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <label className="flex items-end gap-2 pb-2 text-sm text-ink">
                            <input type="checkbox" name="featured" defaultChecked={filters.featured} />
                            Featured only
                        </label>
                        <div className="flex items-end">
                            <Button type="submit" className="w-full">
                                Search
                            </Button>
                        </div>
                    </form>
                    <div className="mt-8">
                        <p className="text-sm text-muted">
                            {jobs.total === 0
                                ? 'No matching roles are open at the moment.'
                                : `${jobs.total} open ${jobs.total === 1 ? 'role' : 'roles'}.`}
                        </p>
                        {jobs.data.length === 0 ? (
                            <p className="mt-6 rounded-xl border border-line bg-white p-6 text-sm text-muted">
                                Try a wider search, or check back soon for new vacancies.
                            </p>
                        ) : (
                            <div className="mt-6 grid gap-6 lg:grid-cols-[minmax(18rem,24rem)_minmax(0,1fr)]">
                                <div className="space-y-3">
                                    {jobs.data.map((job) => (
                                        <JobCard
                                            key={job.slug}
                                            job={job}
                                            selected={selected?.slug === job.slug}
                                            onSelect={selectJob}
                                        />
                                    ))}
                                    <Pagination links={jobs.links} />
                                </div>
                                <div className="hidden self-start lg:sticky lg:top-24 lg:block">
                                    {selected ? (
                                        <JobDetail job={selected} />
                                    ) : (
                                        <div className="rounded-xl border border-dashed border-line bg-white p-10 text-center">
                                            <h2 className="text-lg font-semibold text-ink">Select a job</h2>
                                            <p className="mt-2 text-sm text-muted">
                                                Choose a role from the list to see the full details here.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
