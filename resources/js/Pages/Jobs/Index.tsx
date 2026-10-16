import type { FormEvent, RefObject } from 'react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/atoms/Button';
import EmptyState from '@/Components/UI/EmptyState';
import JobCard from '@/Components/Public/JobCard';
import JobDetail from '@/Components/Public/JobDetail';
import JobsSearchPanel, {
    countActiveJobFilters,
    type JobsFilterValues,
} from '@/Components/Public/JobsSearchPanel';
import PageContainer from '@/Components/Public/PageContainer';
import { selectControlClass } from '@/Components/Public/FormField';
import type { Paginator, PublicJobCard, PublicJobDetail } from '@/types/jobs';

type FilterOptions = {
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
    selected_job: PublicJobDetail | null;
    filters: JobsFilterValues;
    options: FilterOptions;
};

function readFiltersFromForm(form: FormData): JobsFilterValues {
    return {
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
        featured: form.get('featured') === 'on',
    };
}

function jobsQueryParams(filters: JobsFilterValues, extras?: { job?: string; page?: string }) {
    const params: Record<string, string | number> = {};

    if (filters.keyword) params.keyword = filters.keyword;
    if (filters.location) params.location = filters.location;
    if (filters.industry) params.industry = filters.industry;
    if (filters.category) params.category = filters.category;
    if (filters.employment_type) params.employment_type = filters.employment_type;
    if (filters.experience) params.experience = filters.experience;
    if (filters.company) params.company = filters.company;
    if (filters.salary) params.salary = filters.salary;
    if (filters.work_mode) params.work_mode = filters.work_mode;
    if (filters.sort) params.sort = filters.sort;
    if (filters.featured) params.featured = 1;
    if (extras?.job) params.job = extras.job;
    if (extras?.page) params.page = extras.page;

    return params;
}

const SCROLL_LOAD_ROOT_MARGIN = '120px';

export default function JobsIndex({ jobs, selected_job, filters, options }: JobsIndexProps) {
    const [listedJobs, setListedJobs] = useState<PublicJobCard[]>(jobs.data);
    const [pagination, setPagination] = useState(jobs);
    const [loadingMore, setLoadingMore] = useState(false);
    const [loadError, setLoadError] = useState<string | null>(null);

    const desktopListRef = useRef<HTMLDivElement>(null);
    const mobileListRef = useRef<HTMLDivElement>(null);
    const desktopSentinelRef = useRef<HTMLDivElement>(null);
    const mobileSentinelRef = useRef<HTMLDivElement>(null);
    const loadingMoreRef = useRef(false);
    const requestedPageRef = useRef<number | null>(null);

    useEffect(() => {
        if (jobs.current_page <= 1) {
            setListedJobs(jobs.data);
            requestedPageRef.current = null;
        } else {
            setListedJobs((previous) => {
                const seen = new Set(previous.map((job) => job.slug));
                const merged = [...previous];
                for (const job of jobs.data) {
                    if (!seen.has(job.slug)) {
                        merged.push(job);
                    }
                }
                return merged;
            });
        }
        setPagination(jobs);
        loadingMoreRef.current = false;
        setLoadingMore(false);
    }, [jobs]);

    const selectedSlug = selected_job?.slug ?? '';
    const activeFilterCount = useMemo(() => countActiveJobFilters(filters), [filters]);
    const hasMore = listedJobs.length < pagination.total && Boolean(pagination.next_page_url);

    const search = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const nextFilters = readFiltersFromForm(new FormData(event.currentTarget));
        router.get(
            '/jobs',
            jobsQueryParams({
                ...nextFilters,
                sort: filters.sort,
            }),
            {
                preserveScroll: true,
                replace: true,
                preserveState: false,
            },
        );
    };

    const clearFilters = () => {
        router.get('/jobs', {}, { preserveState: false });
    };

    const loadNextPage = useCallback(() => {
        const nextUrl = pagination.next_page_url;
        const nextPage = pagination.current_page + 1;

        if (!nextUrl || loadingMoreRef.current || requestedPageRef.current === nextPage) {
            return;
        }

        loadingMoreRef.current = true;
        requestedPageRef.current = nextPage;
        setLoadingMore(true);
        setLoadError(null);

        router.get(
            nextUrl,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                only: ['jobs', 'selected_job'],
                onError: () => {
                    setLoadError('Could not load more jobs. Scroll again to retry.');
                    requestedPageRef.current = null;
                },
                onFinish: () => {
                    loadingMoreRef.current = false;
                    setLoadingMore(false);
                },
            },
        );
    }, [pagination.current_page, pagination.next_page_url]);

    useEffect(() => {
        if (!hasMore) {
            return;
        }

        const media = window.matchMedia('(min-width: 1024px)');

        const connect = () => {
            const useDesktop = media.matches;
            const root = useDesktop ? desktopListRef.current : mobileListRef.current;
            const sentinel = useDesktop ? desktopSentinelRef.current : mobileSentinelRef.current;
            if (!root || !sentinel) {
                return null;
            }

            const observer = new IntersectionObserver(
                (entries) => {
                    if (entries.some((entry) => entry.isIntersecting)) {
                        loadNextPage();
                    }
                },
                {
                    root,
                    rootMargin: SCROLL_LOAD_ROOT_MARGIN,
                    threshold: 0,
                },
            );
            observer.observe(sentinel);
            return observer;
        };

        let observer = connect();
        const onViewportChange = () => {
            observer?.disconnect();
            observer = connect();
        };
        media.addEventListener('change', onViewportChange);

        return () => {
            media.removeEventListener('change', onViewportChange);
            observer?.disconnect();
        };
    }, [hasMore, loadNextPage, listedJobs.length]);

    const selectJob = (slug: string) => {
        if (slug === selectedSlug) {
            return;
        }

        router.get('/jobs', jobsQueryParams(filters, { job: slug }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['selected_job'],
        });
    };

    const changeSort = (sort: string) => {
        router.get('/jobs', jobsQueryParams({ ...filters, sort }, selectedSlug ? { job: selectedSlug } : undefined), {
            preserveScroll: true,
            preserveState: false,
        });
    };

    const renderListFooter = (sentinelRef: RefObject<HTMLDivElement | null>) => (
        <>
            <div ref={sentinelRef} className="h-px w-full" aria-hidden />
            {loadingMore ? (
                <p className="py-2 text-center text-xs text-muted" role="status">
                    Loading more jobs…
                </p>
            ) : null}
            {loadError ? (
                <p className="py-2 text-center text-xs text-red-700" role="alert">
                    {loadError}
                </p>
            ) : null}
            {!hasMore && listedJobs.length > 0 && pagination.total > 0 ? (
                <p className="py-2 text-center text-xs text-muted">All matching jobs loaded.</p>
            ) : null}
        </>
    );

    const listHeader = (
        <div className="flex shrink-0 flex-wrap items-end justify-between gap-2 border-b border-line pb-3">
            <div className="min-w-0">
                <h1 className="text-base font-semibold text-ink">Jobs</h1>
                {pagination.total > 0 ? (
                    <p className="mt-0.5 text-xs text-muted">
                        {pagination.total} open {pagination.total === 1 ? 'job' : 'jobs'}
                        {listedJobs.length < pagination.total ? ` · showing ${listedJobs.length}` : ''}
                    </p>
                ) : null}
            </div>
            <div className="min-w-[9.5rem]">
                <label htmlFor="jobs-list-sort" className="sr-only">
                    Sort jobs
                </label>
                <select
                    id="jobs-list-sort"
                    value={filters.sort}
                    onChange={(event) => changeSort(event.target.value)}
                    className={`${selectControlClass} py-1.5 text-xs`}
                >
                    <option value="">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="title">Title</option>
                </select>
            </div>
        </div>
    );

    const renderJobList = (splitSelect: boolean, sentinelRef: RefObject<HTMLDivElement | null>) => (
        <>
            {listedJobs.length === 0 ? (
                <EmptyState
                    className="mt-4"
                    title="No matching roles"
                    description="Try a wider search or clear filters to see more openings."
                    action={
                        activeFilterCount > 0 ? (
                            <Button surface="public" type="button" variant="secondary" onClick={clearFilters}>
                                Clear filters
                            </Button>
                        ) : undefined
                    }
                />
            ) : (
                <ul className="mt-2 flex flex-col gap-2">
                    {listedJobs.map((job) => (
                        <li key={job.slug}>
                            <JobCard
                                job={job}
                                compact
                                selected={splitSelect && job.slug === selectedSlug}
                                onSelect={splitSelect ? selectJob : undefined}
                            />
                        </li>
                    ))}
                </ul>
            )}
            {renderListFooter(sentinelRef)}
        </>
    );

    return (
        <PublicLayout title="Jobs">
            <PageContainer className="flex min-w-0 flex-col py-4 lg:h-[calc(100dvh-4.5rem)] lg:min-h-0 lg:overflow-hidden lg:py-5">
                <JobsSearchPanel
                    variant="workspace"
                    filters={filters}
                    options={options}
                    onSubmit={search}
                    extraActions={
                        activeFilterCount > 0 ? (
                            <Button surface="public" type="button" variant="ghost" onClick={clearFilters}>
                                Clear
                            </Button>
                        ) : null
                    }
                />

                <div className="mt-4 min-h-0 flex-1 lg:hidden">
                    {listHeader}
                    <div ref={mobileListRef} aria-label="Job results" className="max-h-[70vh] overflow-y-auto overscroll-y-contain">
                        {renderJobList(false, mobileSentinelRef)}
                    </div>
                </div>

                <div className="mt-3 hidden min-h-0 flex-1 lg:grid lg:grid-cols-[minmax(0,34fr)_minmax(0,66fr)] lg:gap-5">
                    <aside className="flex min-h-0 min-w-0 flex-col border-r border-line pr-4">
                        {listHeader}
                        <div
                            ref={desktopListRef}
                            aria-label="Job results"
                            className="mt-2 min-h-0 flex-1 overflow-y-auto overscroll-y-contain pr-1"
                        >
                            {renderJobList(true, desktopSentinelRef)}
                        </div>
                    </aside>

                    <section aria-label="Selected job" className="flex min-h-0 min-w-0 flex-col overflow-hidden pl-1">
                        {selected_job ? (
                            <JobDetail job={selected_job} layout="pane" openJobsLinkMode="jobs_index" key={selected_job.slug} />
                        ) : (
                            <div className="flex min-h-0 flex-1 items-center justify-center">
                                <EmptyState
                                    title="Select a job"
                                    description="Choose a role from the list to view details and apply."
                                />
                            </div>
                        )}
                    </section>
                </div>
            </PageContainer>
        </PublicLayout>
    );
}
