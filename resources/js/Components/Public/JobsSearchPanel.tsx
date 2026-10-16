import type { FormEvent, ReactNode } from 'react';
import FormField, { selectControlClass } from '@/Components/Public/FormField';
import Button from '@/Components/atoms/Button';

export type JobsFilterValues = {
    keyword: string;
    location: string;
    industry: string;
    category: string;
    employment_type: string;
    experience: string;
    company: string;
    salary: string;
    work_mode: string;
    sort: string;
    featured: boolean;
};

type Options = {
    locations: string[];
    industries: string[];
    categories: string[];
    employment_types: string[];
    experiences: string[];
    companies?: string[];
    salaries?: string[];
};

type Props = {
    filters: JobsFilterValues;
    options: Options;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    extraActions?: ReactNode;
    showExperienceInPrimary?: boolean;
    /** Workspace: keyword, location, industry, job type, experience, company only (sort lives on the list). */
    variant?: 'full' | 'workspace';
};

function PrimaryFilters({ filters, options }: { filters: JobsFilterValues; options: Options }) {
    return (
        <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div className="min-w-0">
                <label htmlFor="job-industry" className="mb-1 block text-sm font-medium text-ink">
                    Industry
                </label>
                <select id="job-industry" name="industry" defaultValue={filters.industry} className={selectControlClass}>
                    <option value="">All industries</option>
                    {options.industries.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
            </div>
            <div className="min-w-0">
                <label htmlFor="job-category" className="mb-1 block text-sm font-medium text-ink">
                    Category
                </label>
                <select id="job-category" name="category" defaultValue={filters.category} className={selectControlClass}>
                    <option value="">All categories</option>
                    {options.categories.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
            </div>
            <div className="min-w-0">
                <label htmlFor="job-type" className="mb-1 block text-sm font-medium text-ink">
                    Job type
                </label>
                <select id="job-type" name="employment_type" defaultValue={filters.employment_type} className={selectControlClass}>
                    <option value="">All types</option>
                    {options.employment_types.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
            </div>
            <label className="flex min-h-[2.75rem] items-end gap-2 pb-2 text-sm text-ink">
                <input type="checkbox" name="featured" defaultChecked={filters.featured} className="shrink-0" />
                Featured
            </label>
        </div>
    );
}

function SecondaryFilters({ filters, options }: { filters: JobsFilterValues; options: Options }) {
    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {!options.experiences.length ? null : (
                <div>
                    <label htmlFor="job-experience-more" className="mb-1 block text-sm font-medium text-ink">
                        Experience
                    </label>
                    <select id="job-experience-more" name="experience" defaultValue={filters.experience} className={selectControlClass}>
                        <option value="">All experience</option>
                        {options.experiences.map((value) => (
                            <option key={value} value={value}>
                                {value}
                            </option>
                        ))}
                    </select>
                </div>
            )}
            <div>
                <label htmlFor="job-company" className="mb-1 block text-sm font-medium text-ink">
                    Company
                </label>
                <select id="job-company" name="company" defaultValue={filters.company} className={selectControlClass}>
                    <option value="">All companies</option>
                    {(options.companies ?? []).map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
            </div>
            <div>
                <label htmlFor="job-salary" className="mb-1 block text-sm font-medium text-ink">
                    Salary
                </label>
                <select id="job-salary" name="salary" defaultValue={filters.salary} className={selectControlClass}>
                    <option value="">All salaries</option>
                    {(options.salaries ?? []).map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
            </div>
            <div>
                <label htmlFor="job-work-mode" className="mb-1 block text-sm font-medium text-ink">
                    Work mode
                </label>
                <select id="job-work-mode" name="work_mode" defaultValue={filters.work_mode} className={selectControlClass}>
                    <option value="">All work modes</option>
                    <option value="On-site">On-site</option>
                    <option value="Hybrid">Hybrid</option>
                    <option value="Remote">Remote</option>
                </select>
            </div>
            <div>
                <label htmlFor="job-sort" className="mb-1 block text-sm font-medium text-ink">
                    Sort
                </label>
                <select id="job-sort" name="sort" defaultValue={filters.sort} className={selectControlClass}>
                    <option value="">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="title">Title</option>
                </select>
            </div>
        </div>
    );
}

function WorkspaceFilters({ filters, options }: { filters: JobsFilterValues; options: Options }) {
    return (
        <div className="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div className="min-w-0">
                <label htmlFor="job-industry" className="mb-1 block text-sm font-medium text-ink">
                    Industry
                </label>
                <select id="job-industry" name="industry" defaultValue={filters.industry} className={selectControlClass}>
                    <option value="">All industries</option>
                    {options.industries.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
            </div>
            <div className="min-w-0">
                <label htmlFor="job-type" className="mb-1 block text-sm font-medium text-ink">
                    Job type
                </label>
                <select id="job-type" name="employment_type" defaultValue={filters.employment_type} className={selectControlClass}>
                    <option value="">All types</option>
                    {options.employment_types.map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
            </div>
            {options.experiences.length > 0 ? (
                <div className="min-w-0">
                    <label htmlFor="job-experience" className="mb-1 block text-sm font-medium text-ink">
                        Experience
                    </label>
                    <select id="job-experience" name="experience" defaultValue={filters.experience} className={selectControlClass}>
                        <option value="">All experience</option>
                        {options.experiences.map((value) => (
                            <option key={value} value={value}>
                                {value}
                            </option>
                        ))}
                    </select>
                </div>
            ) : null}
            <div className="min-w-0">
                <label htmlFor="job-company" className="mb-1 block text-sm font-medium text-ink">
                    Company
                </label>
                <select id="job-company" name="company" defaultValue={filters.company} className={selectControlClass}>
                    <option value="">All companies</option>
                    {(options.companies ?? []).map((value) => (
                        <option key={value} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
            </div>
        </div>
    );
}

export default function JobsSearchPanel({
    filters,
    options,
    onSubmit,
    extraActions,
    showExperienceInPrimary = false,
    variant = 'full',
}: Props) {
    const isWorkspace = variant === 'workspace';

    return (
        <form onSubmit={onSubmit} className="shrink-0 rounded-xl border border-line bg-white p-3 shadow-sm sm:p-4">
            <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_11rem_auto]">
                <FormField id="job-keywords" name="keyword" label="Search jobs" placeholder="Job title or keyword" defaultValue={filters.keyword} />
                <div className="min-w-0">
                    <label htmlFor="job-location" className="mb-1 block text-sm font-medium text-ink">
                        Location
                    </label>
                    <select id="job-location" name="location" defaultValue={filters.location} className={selectControlClass}>
                        <option value="">All locations</option>
                        {options.locations.map((value) => (
                            <option key={value} value={value}>
                                {value}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="flex items-end gap-2">
                    <Button surface="public" type="submit" variant="secondary" className="w-full md:w-auto">
                        Search
                    </Button>
                    {extraActions}
                </div>
            </div>
            {isWorkspace ? (
                <WorkspaceFilters filters={filters} options={options} />
            ) : (
                <>
                    {showExperienceInPrimary ? (
                        <div className="mt-3 max-w-xs">
                            <label htmlFor="job-experience-primary" className="mb-1 block text-sm font-medium text-ink">
                                Experience
                            </label>
                            <select
                                id="job-experience-primary"
                                name="experience"
                                defaultValue={filters.experience}
                                className={selectControlClass}
                            >
                                <option value="">All experience</option>
                                {options.experiences.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </div>
                    ) : null}
                    <PrimaryFilters filters={filters} options={options} />
                    <div className="mt-3">
                        <p className="mb-2 text-sm font-semibold text-brand lg:text-xs lg:font-semibold lg:uppercase lg:tracking-wide lg:text-muted">
                            More filters
                        </p>
                        <SecondaryFilters filters={filters} options={options} />
                    </div>
                </>
            )}
        </form>
    );
}

export function countActiveJobFilters(filters: JobsFilterValues): number {
    let count = 0;
    if (filters.keyword) count += 1;
    if (filters.location) count += 1;
    if (filters.industry) count += 1;
    if (filters.category) count += 1;
    if (filters.employment_type) count += 1;
    if (filters.experience) count += 1;
    if (filters.company) count += 1;
    if (filters.salary) count += 1;
    if (filters.work_mode) count += 1;
    if (filters.sort) count += 1;
    if (filters.featured) count += 1;
    return count;
}
