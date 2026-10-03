import { usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/Public/Button';
import PublicPageHero from '@/Components/Public/PublicPageHero';
import PageContainer from '@/Components/Public/PageContainer';
import Card from '@/Components/UI/Card';
import SectionHeader from '@/Components/UI/SectionHeader';
import { cmsSection, type CmsPage } from '@/types/cms';
import type { SharedPageProps } from '@/types/page';
import type { SiteContent } from '@/types/public';

const steps = [
    ['Share the requirement', 'Headcount, location, duration, and type of work.'],
    ['Source and deploy', 'Placements use existing staffing records in the platform.'],
    ['Manage the workforce', 'Employees and replacements follow existing product flows.'],
];

export default function Staffing({ content, cms }: { content: SiteContent; cms?: CmsPage }) {
    const user = usePage<SharedPageProps>().props.auth.user;
    const hero = cmsSection(cms, 'hero');
    const process = cmsSection(cms, 'process');
    const cta = cmsSection(cms, 'cta');

    return (
        <PublicLayout title="Staffing">
            <PublicPageHero
                eyebrow="OurNear Staffing"
                title={hero?.heading || 'Staffing solutions for your workforce'}
                description={hero?.body || content.tagline}
                breadcrumbs={[{ label: 'Home', href: '/' }, { label: 'Staffing' }]}
                actions={
                    <>
                        <Button href="/for-employers" variant="primary">
                            Request Staffing
                        </Button>
                        <Button href={user ? '/account/hiring' : '/hire'} variant="outline" className="border-white/30 bg-transparent text-white hover:bg-white/10">
                            {user ? 'Open Hiring Dashboard' : 'Hire Talent'}
                        </Button>
                    </>
                }
            />

            <section className="py-10 md:py-12">
                <PageContainer>
                    <SectionHeader title={process?.heading || 'How OurNear Staffing works'} description={process?.body ?? undefined} />
                    <div className="mt-6 grid gap-4 md:grid-cols-3">
                        {steps.map(([title, body], index) => (
                            <Card key={title} as="article" density="standard">
                                <p className="text-xs font-semibold uppercase tracking-wide text-brand">Step {index + 1}</p>
                                <h3 className="mt-2 font-semibold text-ink">{title}</h3>
                                <p className="mt-2 text-sm text-muted">{body}</p>
                            </Card>
                        ))}
                    </div>
                </PageContainer>
            </section>

            <section className="border-t border-line bg-canvas py-10 md:py-12">
                <PageContainer>
                    <Card density="feature">
                        <h2 className="text-xl font-semibold text-ink">{cta?.heading || 'Need manpower for your business?'}</h2>
                        <p className="mt-2 max-w-2xl text-sm text-muted">
                            {cta?.body || 'Submit a workforce requirement through the employer enquiry form—the same flow as Staffing Services in your account.'}
                        </p>
                        <div className="mt-6 flex flex-wrap gap-3">
                            <Button href="/for-employers" variant="primary">
                                Request staffing support
                            </Button>
                            <Button href="/hire" variant="link">
                                Explore Hire
                            </Button>
                            <Button href="/talent-pool" variant="link">
                                Explore Talent Pool
                            </Button>
                        </div>
                    </Card>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
