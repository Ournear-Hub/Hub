import type { FormEvent } from 'react';
import { router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/Public/Button';
import FormField from '@/Components/Public/FormField';
import JobCard from '@/Components/Public/JobCard';
import PageContainer from '@/Components/Public/PageContainer';
import Pagination from '@/Components/Pagination';
import SectionHeading from '@/Components/Public/SectionHeading';
import type { Paginator, PublicJobCard } from '@/types/jobs';
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
    filters: Filters;
    options: Options;
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function JobsIndex({ content, jobs, filters, options }: JobsIndexProps) {
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

    return (
        <PublicLayout title="Jobs">
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <p className="text-sm font-medium text-accent">{content.tagline}</p>
                    <h1 className="mt-3 text-4xl font-semibold">Jobs</h1>
                    <p className="mt-3 max-w-2xl text-white/80">
                        Browse open roles. Apply directly — no candidate account is required.
                    </p>
                </PageContainer>
            </section>
            <section className="py-12">
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
                            <select
                                id="job-type"
                                name="employment_type"
                                defaultValue={filters.employment_type}
                                className={selectClass}
                            >
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
                            <select
                                id="job-experience"
                                name="experience"
                                defaultValue={filters.experience}
                                className={selectClass}
                            >
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
                    <div className="mt-10">
                        <SectionHeading
                            title="Open roles"
                            description={
                                jobs.total === 0
                                    ? 'No matching roles are open at the moment.'
                                    : `${jobs.total} open ${jobs.total === 1 ? 'role' : 'roles'}.`
                            }
                        />
                        {jobs.data.length === 0 ? (
                            <p className="mt-8 rounded-xl border border-line bg-white p-6 text-sm text-muted">
                                Try a wider search, or check back soon for new vacancies.
                            </p>
                        ) : (
                            <div className="mt-8 grid gap-4 md:grid-cols-2">
                                {jobs.data.map((job) => (
                                    <JobCard key={job.slug} job={job} />
                                ))}
                            </div>
                        )}
                        <Pagination links={jobs.links} />
                    </div>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
