import PublicLayout from '@/Layouts/PublicLayout';
import CtaSection from '@/Components/Public/CtaSection';
import IndustryCard from '@/Components/Public/IndustryCard';
import PageContainer from '@/Components/Public/PageContainer';
import SectionHeading from '@/Components/Public/SectionHeading';
import ServiceCard from '@/Components/Public/ServiceCard';
import { activeItems, cmsSection, type CmsPage } from '@/types/cms';
import type { SiteContent } from '@/types/public';

type ServicesProps = {
    content: SiteContent;
    cms: CmsPage;
};

export default function Services({ content, cms }: ServicesProps) {
    const hero = cmsSection(cms, 'hero');
    const coreServices = cmsSection(cms, 'core_services');
    const workforce = cmsSection(cms, 'workforce');
    const training = cmsSection(cms, 'training');
    const delivery = cmsSection(cms, 'delivery_process');
    const competency = cmsSection(cms, 'competency_process');
    const projectSupport = cmsSection(cms, 'project_support');
    const industries = cmsSection(cms, 'industries');
    const whyChooseUs = cmsSection(cms, 'why_choose_us');
    const cta = cmsSection(cms, 'cta');

    return (
        <PublicLayout title="Services">
            <section
                className="bg-brand py-14 text-white"
                style={
                    hero?.image_url
                        ? {
                              backgroundImage: `linear-gradient(rgba(33, 68, 73, 0.82), rgba(33, 68, 73, 0.82)), url(${hero.image_url})`,
                              backgroundSize: 'cover',
                              backgroundPosition: 'center',
                          }
                        : undefined
                }
            >
                <PageContainer>
                    <p className="text-sm font-medium text-accent">{hero?.subtitle || content.tagline}</p>
                    <h1 className="mt-3 text-4xl font-semibold">{hero?.heading}</h1>
                    <p className="mt-3 max-w-2xl text-white/80">{hero?.body}</p>
                </PageContainer>
            </section>
            <section className="py-16">
                <PageContainer className="space-y-14">
                    {coreServices ? (
                        <div>
                            <SectionHeading title={coreServices.heading ?? ''} description={coreServices.body ?? ''} />
                            <div className="mt-8 grid gap-4 md:grid-cols-3">
                                {activeItems(coreServices).map((item) => (
                                    <ServiceCard
                                        key={item.id}
                                        id={item.title.toLowerCase().replace(/[^a-z0-9]+/g, '-')}
                                        title={item.title}
                                        summary={item.description ?? ''}
                                        bullets={item.bullets}
                                    />
                                ))}
                            </div>
                        </div>
                    ) : null}
                    {workforce ? (
                        <div>
                            <SectionHeading title={workforce.heading ?? ''} description={workforce.body ?? ''} />
                            <div className="mt-8 grid grid-cols-2 gap-4 md:grid-cols-4">
                                {activeItems(workforce).map((item) => (
                                    <IndustryCard key={item.id} name={item.title} />
                                ))}
                            </div>
                        </div>
                    ) : null}
                    {training ? (
                        <div>
                            <SectionHeading title={training.heading ?? ''} description={training.body ?? ''} />
                            <div className="mt-8 grid gap-4 md:grid-cols-2">
                                {activeItems(training).map((item) => (
                                    <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
                                ))}
                            </div>
                        </div>
                    ) : null}
                    {delivery ? (
                        <div>
                            <SectionHeading title={delivery.heading ?? ''} description={delivery.body ?? ''} />
                            <div className="mt-8 grid gap-4 md:grid-cols-5">
                                {activeItems(delivery).map((item) => (
                                    <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
                                ))}
                            </div>
                        </div>
                    ) : null}
                    {competency ? (
                        <div>
                            <SectionHeading title={competency.heading ?? ''} description={competency.body ?? ''} />
                            <div className="mt-8 grid gap-4 md:grid-cols-5">
                                {activeItems(competency).map((item) => (
                                    <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
                                ))}
                            </div>
                        </div>
                    ) : null}
                    {projectSupport ? (
                        <div>
                            <SectionHeading title={projectSupport.heading ?? ''} description={projectSupport.body ?? ''} />
                            <div className="mt-8 grid gap-4 md:grid-cols-5">
                                {activeItems(projectSupport).map((item) => (
                                    <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
                                ))}
                            </div>
                        </div>
                    ) : null}
                    {industries ? (
                        <div>
                            <SectionHeading title={industries.heading ?? ''} description={industries.body ?? ''} />
                            <div className="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3">
                                {activeItems(industries).map((item) => (
                                    <IndustryCard key={item.id} name={item.title} />
                                ))}
                            </div>
                        </div>
                    ) : null}
                    {whyChooseUs ? (
                        <div>
                            <SectionHeading title={whyChooseUs.heading ?? ''} description={whyChooseUs.body ?? ''} />
                            <div className="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                {activeItems(whyChooseUs).map((item) => (
                                    <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
                                ))}
                            </div>
                        </div>
                    ) : null}
                </PageContainer>
            </section>
            <CtaSection
                title={cta?.heading ?? 'Talk to OurNear Hire'}
                description={cta?.body ?? 'Tell us about the role, the team, or the training outcome you need.'}
                actionLabel={cta?.primary_button_label ?? 'Contact us'}
                actionHref="/contact"
            />
        </PublicLayout>
    );
}
