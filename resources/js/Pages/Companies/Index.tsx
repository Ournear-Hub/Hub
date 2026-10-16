import type { FormEvent } from 'react';
import { Link, router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicCompanyCard, { type PublicCompanyCardData } from '@/Components/Public/PublicCompanyCard';
import PublicPageHero from '@/Components/Public/PublicPageHero';
import PageContainer from '@/Components/Public/PageContainer';
import Button from '@/Components/atoms/Button';
import FormField, { selectControlClass } from '@/Components/Public/FormField';
import EmptyState from '@/Components/UI/EmptyState';
import type { SiteContent } from '@/types/public';

type Props = {
    content: SiteContent;
    companies: PublicCompanyCardData[];
    filters: { keyword: string; industry: string; location: string };
    industries: string[];
    locations: string[];
};

export default function CompaniesIndex({ companies, filters, industries, locations }: Props) {
    const search = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get('/companies', {
            keyword: String(form.get('keyword') ?? ''),
            industry: String(form.get('industry') ?? ''),
            location: String(form.get('location') ?? ''),
        });
    };

    const hasFilters = Boolean(filters.keyword || filters.industry || filters.location);

    return (
        <PublicLayout title="Companies">
            <PublicPageHero
                eyebrow="OurNear Jobs"
                title="Discover companies"
                description="Search employers on OurNear Jobs by name, industry, or location and explore their open roles."
                breadcrumbs={[{ label: 'Home', href: '/' }, { label: 'Companies' }]}
            />

            <section className="py-8 md:py-10">
                <PageContainer className="min-w-0">
                    <form
                        onSubmit={search}
                        className="grid min-w-0 gap-3 rounded-xl border border-line bg-white p-4 md:grid-cols-[minmax(0,1fr)_11rem_11rem_auto]"
                    >
                        <FormField
                            id="company-keyword"
                            name="keyword"
                            label="Search companies"
                            placeholder="Company name"
                            defaultValue={filters.keyword}
                        />
                        <div className="min-w-0">
                            <label htmlFor="company-industry" className="mb-1 block text-sm font-medium text-ink">
                                Industry
                            </label>
                            <select
                                id="company-industry"
                                name="industry"
                                defaultValue={filters.industry}
                                className={selectControlClass}
                            >
                                <option value="">All industries</option>
                                {industries.map((value) => (
                                    <option key={value} value={value}>
                                        {value}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="min-w-0">
                            <label htmlFor="company-location" className="mb-1 block text-sm font-medium text-ink">
                                Location
                            </label>
                            <select
                                id="company-location"
                                name="location"
                                defaultValue={filters.location}
                                className={selectControlClass}
                            >
                                <option value="">All locations</option>
                                {locations.map((value) => (
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
                        </div>
                    </form>

                    <p className="mt-6 text-sm text-muted">
                        {companies.length === 0
                            ? 'No companies match this search.'
                            : `${companies.length} ${companies.length === 1 ? 'company' : 'companies'} found.`}
                    </p>

                    {companies.length === 0 ? (
                        <EmptyState
                            className="mt-4"
                            title="No companies found"
                            description="Try a different keyword or clear filters. Companies appear here when they publish a public company page."
                            action={
                                hasFilters ? (
                                    <Link href="/companies" className="text-sm font-semibold text-brand hover:underline">
                                        Clear search
                                    </Link>
                                ) : (
                                    <Button surface="public" href="/jobs" variant="outline">
                                        Browse jobs
                                    </Button>
                                )
                            }
                        />
                    ) : (
                        <div className="mt-6 grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {companies.map((company) => (
                                <PublicCompanyCard key={company.id} company={company} />
                            ))}
                        </div>
                    )}
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
