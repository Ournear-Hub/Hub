import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import type { SiteContent } from '@/types/public';

type Props = {
    content: SiteContent;
    company: {
        business_id: string | null;
        company_name: string;
        logo_url: string | null;
        about_company: string | null;
        company_size: string | null;
        industry: string | null;
        company_details: string | null;
        company_website: string | null;
    };
};

export default function PublicCompanyProfile({ company }: Props) {
    const initials = company.company_name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase();

    return (
        <PublicLayout title={company.company_name}>
            <section className="py-10">
                <PageContainer className="max-w-4xl">
                    <article className="rounded-xl border border-line bg-white p-6 sm:p-8">
                        <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
                            {company.logo_url ? (
                                <img src={company.logo_url} alt={`${company.company_name} logo`} className="h-28 w-28 rounded-xl border border-line object-cover" />
                            ) : (
                                <div className="flex h-28 w-28 items-center justify-center rounded-xl bg-surface text-2xl font-semibold text-brand">{initials}</div>
                            )}
                            <div>
                                <h1 className="text-3xl font-semibold text-ink">{company.company_name}</h1>
                                {company.business_id ? <p className="mt-1 text-sm text-muted">{company.business_id}</p> : null}
                                <p className="mt-2 text-sm text-muted">{[company.industry, company.company_size].filter(Boolean).join(' · ')}</p>
                            </div>
                        </div>
                        {company.about_company ? (
                            <section className="mt-8">
                                <h2 className="text-lg font-semibold text-ink">About company</h2>
                                <p className="mt-2 whitespace-pre-line text-sm leading-6 text-muted">{company.about_company}</p>
                            </section>
                        ) : null}
                        {company.company_details ? (
                            <section className="mt-8">
                                <h2 className="text-lg font-semibold text-ink">Company details</h2>
                                <p className="mt-2 whitespace-pre-line text-sm leading-6 text-muted">{company.company_details}</p>
                            </section>
                        ) : null}
                        {company.company_website ? (
                            <a
                                href={company.company_website}
                                target="_blank"
                                rel="noopener noreferrer nofollow"
                                className="mt-8 inline-flex rounded-md border border-line px-4 py-2 text-sm font-semibold text-brand"
                            >
                                Visit company website
                            </a>
                        ) : null}
                    </article>
                    <p className="mt-4 text-xs text-muted">Private company documents are not displayed publicly.</p>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
