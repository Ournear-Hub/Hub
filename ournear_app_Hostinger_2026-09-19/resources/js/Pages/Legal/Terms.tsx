import PublicLayout from '@/Layouts/PublicLayout';
import PageContainer from '@/Components/Public/PageContainer';
import { cmsSection, type CmsPage } from '@/types/cms';
import type { SiteContent } from '@/types/public';

type LegalProps = {
    content: SiteContent;
    cms: CmsPage;
};

export default function Terms({ content, cms }: LegalProps) {
    const section = cmsSection(cms, 'content');

    return (
        <PublicLayout title={section?.heading || 'Terms & Conditions'}>
            <section className="bg-white py-16">
                <PageContainer className="max-w-3xl">
                    <h1 className="text-3xl font-semibold text-ink">{section?.heading || 'Terms & Conditions'}</h1>
                    <p className="mt-4 text-sm text-muted">
                        {content.brand} · {content.tagline}
                    </p>
                    <div className="mt-6 whitespace-pre-line text-base text-muted">{section?.body}</div>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
