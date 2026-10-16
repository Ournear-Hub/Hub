import PublicLayout from '@/Layouts/PublicLayout';
import ContactDetailsSimple from '@/Components/Public/ContactDetailsSimple';
import PageContainer from '@/Components/Public/PageContainer';
import type { SiteContent } from '@/types/public';

type ContactProps = {
    content: SiteContent;
};

export default function Contact({ content }: ContactProps) {
    return (
        <PublicLayout title="Contact Us">
            <PageContainer className="max-w-2xl py-10 md:py-12">
                <h1 className="text-2xl font-semibold text-ink sm:text-3xl">Contact Us</h1>
                <ContactDetailsSimple contact={content.contact} className="mt-8" />
            </PageContainer>
        </PublicLayout>
    );
}
