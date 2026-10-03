import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/Components/Public/PublicPageHero';
import PageContainer from '@/Components/Public/PageContainer';
import { cmsSection, type CmsPage } from '@/types/cms';
import type { SiteContent } from '@/types/public';

type LegalProps = {
    content: SiteContent;
    cms: CmsPage;
};

export default function Terms({ content, cms }: LegalProps) {
    const section = cmsSection(cms, 'content');
    const title = section?.heading || 'Terms & Conditions';

    return (
        <PublicLayout title={title}>
            <PublicPageHero
                title={title}
                description={`${content.brand} · ${content.tagline}`}
                breadcrumbs={[{ label: 'Home', href: '/' }, { label: title }]}
                tone="canvas"
            />
            <section className="py-10 md:py-12">
                <PageContainer className="max-w-3xl min-w-0">
                    <div className="whitespace-pre-line text-sm leading-7 text-muted sm:text-base">{section?.body}</div>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
