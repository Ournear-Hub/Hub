import PageContainer from '@/Components/Public/PageContainer';

export type ShowcaseCompanyPublic = {
    name: string;
    logo_url: string | null;
};

type Props = {
    title: string;
    companies: ShowcaseCompanyPublic[];
    id?: string;
};

function LogoBox({ name, logoUrl }: { name: string; logoUrl: string | null }) {
    return (
        <div className="flex h-24 w-full items-center justify-center rounded-lg border border-line bg-white p-4 sm:h-28">
            {logoUrl ? (
                <img src={logoUrl} alt="" className="max-h-full max-w-full object-contain" loading="lazy" />
            ) : (
                <span className="line-clamp-2 text-center text-sm font-semibold text-[#214449]">{name}</span>
            )}
        </div>
    );
}

export default function ShowcaseCompanyGrid({ title, companies, id }: Props) {
    if (companies.length === 0) {
        return null;
    }

    return (
        <section className="border-b border-line bg-canvas py-10 md:py-12" aria-labelledby={id ?? 'showcase-companies-heading'}>
            <PageContainer className="min-w-0">
                <h2 id={id ?? 'showcase-companies-heading'} className="text-lg font-semibold text-[#214449] md:text-xl">
                    {title}
                </h2>
                <ul className="mt-6 grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    {companies.map((company) => (
                        <li key={company.name}>
                            <div className="rounded-xl border border-line bg-white p-3 shadow-sm">
                                <LogoBox name={company.name} logoUrl={company.logo_url} />
                                <p className="mt-3 line-clamp-2 text-center text-sm font-medium text-ink">{company.name}</p>
                            </div>
                        </li>
                    ))}
                </ul>
            </PageContainer>
        </section>
    );
}
