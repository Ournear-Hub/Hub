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
    ['Post a job', 'Create and publish a vacancy in the hiring workspace.'],
    ['Review applications', 'Applications arrive through OurNear Jobs into your hiring flow.'],
    ['Interview and decide', 'Schedule interviews and move candidates using existing recruitment tools.'],
];

const tools = [
    ['Job posting', 'Publish vacancies employers are permitted to post.'],
    ['Applications', 'Review candidate applications in one place.'],
    ['Candidate profiles', 'Open profiles your permissions allow.'],
    ['Interviews', 'Schedule and track interviews already in the product.'],
];

export default function Hire({ content, cms }: { content: SiteContent; cms?: CmsPage }) {
    const page = usePage<SharedPageProps>().props;
    const user = page.auth.account ?? page.auth.user;
    const hero = cmsSection(cms, 'hero');
    const problem = cmsSection(cms, 'problem');
    const solution = cmsSection(cms, 'solution');
    const cta = cmsSection(cms, 'cta');
    const primaryHref = user ? '/account/hiring' : '/contact';
    const primaryLabel = user ? 'Enter Hiring Workspace' : 'Hire Talent';

    return (
        <PublicLayout title="Hire">
            <PublicPageHero
                eyebrow="OurNear Hire"
                title={hero?.heading || 'Hire the right people'}
                description={hero?.body || content.tagline}
                breadcrumbs={[{ label: 'Home', href: '/' }, { label: 'Hire' }]}
                actions={
                    <>
                        <Button href={primaryHref} variant="primary">
                            {primaryLabel}
                        </Button>
                        <Button href={user ? '/account/hiring' : '/contact'} variant="outline" className="border-white/30 bg-transparent text-white hover:bg-white/10">
                            {user ? 'Hiring workspace' : 'Contact Us'}
                        </Button>
                    </>
                }
            />

            <section className="py-10 md:py-12">
                <PageContainer className="grid min-w-0 gap-4 md:grid-cols-2">
                    <Card as="article" density="standard">
                        <h2 className="text-lg font-semibold text-ink">{problem?.heading || 'Recruitment challenge'}</h2>
                        <p className="mt-2 text-sm text-muted">{problem?.body || 'Finding and screening candidates takes time when hiring teams juggle multiple roles.'}</p>
                    </Card>
                    <Card as="article" density="standard">
                        <h2 className="text-lg font-semibold text-ink">{solution?.heading || 'How OurNear Hire helps'}</h2>
                        <p className="mt-2 text-sm text-muted">{solution?.body || 'Use jobs, applications, and interviews on the same platform records you already manage.'}</p>
                    </Card>
                </PageContainer>
            </section>

            <section className="border-y border-line bg-canvas py-10 md:py-12">
                <PageContainer>
                    <SectionHeader
                        title="How it works"
                        description="A straightforward hiring path using existing jobs, applications, and interviews."
                        className="text-center [&>div]:mx-auto [&>div]:text-center"
                    />
                    <div className="mt-8 grid gap-4 md:grid-cols-3">
                        {steps.map(([title, body]) => (
                            <Card key={title} as="article" density="standard" className="text-center">
                                <h3 className="font-semibold text-ink">{title}</h3>
                                <p className="mt-2 text-sm text-muted">{body}</p>
                            </Card>
                        ))}
                    </div>
                </PageContainer>
            </section>

            {user ? (
                <section className="border-b border-line bg-canvas py-8 md:py-10">
                    <PageContainer>
                        <Card density="standard" className="max-w-3xl">
                            <h2 className="text-lg font-semibold text-ink">Hire with OurNear</h2>
                            <p className="mt-2 text-sm text-muted">
                                Access your hiring workspace to post jobs, manage candidates, track applications, and work with OurNear
                                recruitment services—without a separate login.
                            </p>
                            <Button href="/account/hiring" variant="primary" className="mt-4 min-h-11 w-full sm:w-auto">
                                Enter Hiring Workspace
                            </Button>
                        </Card>
                    </PageContainer>
                </section>
            ) : null}

            <section className="py-10 md:py-12">
                <PageContainer>
                    <SectionHeader title="Manage your hiring" description="Tools already available in the OurNear hiring workspace." />
                    <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {tools.map(([title, body]) => (
                            <Card key={title} as="article" density="compact">
                                <h3 className="font-semibold text-ink">{title}</h3>
                                <p className="mt-2 text-sm text-muted">{body}</p>
                            </Card>
                        ))}
                    </div>
                </PageContainer>
            </section>

            <section className="border-t border-line bg-canvas py-10 md:py-12">
                <PageContainer className="grid gap-4 md:grid-cols-2">
                    <Card as="article" density="standard">
                        <h3 className="text-lg font-semibold text-ink">Talent Pool</h3>
                        <p className="mt-2 text-sm text-muted">See candidates who allow a public profile.</p>
                        <Button href="/talent-pool" variant="link" className="mt-4">
                            Explore Talent
                        </Button>
                    </Card>
                    <Card as="article" density="standard">
                        <h3 className="text-lg font-semibold text-ink">OurNear Staffing</h3>
                        <p className="mt-2 text-sm text-muted">Request workforce support through the existing enquiry flow.</p>
                        <Button href="/staffing" variant="link" className="mt-4">
                            Staffing Solutions
                        </Button>
                    </Card>
                </PageContainer>
            </section>

            <section id="hiring-support" className="py-10 md:py-12">
                <PageContainer>
                    <Card density="feature" className="text-center">
                        <h2 className="text-xl font-semibold text-ink sm:text-2xl">{cta?.heading || 'Talk to OurNear Hire'}</h2>
                        <p className="mx-auto mt-3 max-w-xl text-sm text-muted">
                            {cta?.body || 'Send a hiring request through the contact form. Signed-in users can open the Hiring Dashboard directly.'}
                        </p>
                        <div className="mt-6 flex flex-wrap justify-center gap-3">
                            <Button href="/contact" variant="primary">
                                Contact Us
                            </Button>
                            {user ? (
                                <Button href="/account/hiring" variant="secondary">
                                    Enter Hiring Workspace
                                </Button>
                            ) : null}
                        </div>
                    </Card>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
