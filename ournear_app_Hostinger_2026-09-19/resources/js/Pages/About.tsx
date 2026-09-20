import PublicLayout from '@/Layouts/PublicLayout';
import CtaSection from '@/Components/Public/CtaSection';
import IndustryCard from '@/Components/Public/IndustryCard';
import PageContainer from '@/Components/Public/PageContainer';
import SectionHeading from '@/Components/Public/SectionHeading';
import ServiceCard from '@/Components/Public/ServiceCard';
import { activeItems, cmsSection, type CmsPage } from '@/types/cms';
import type { SiteContent } from '@/types/public';

type AboutProps = {
    content: SiteContent;
    cms: CmsPage;
};

export default function About({ content, cms }: AboutProps) {
    const hero = cmsSection(cms, 'hero');
    const whoWeAre = cmsSection(cms, 'who_we_are');
    const whatWeDo = cmsSection(cms, 'what_we_do');
    const approach = cmsSection(cms, 'our_approach');
    const industries = cmsSection(cms, 'industries');
    const whyChooseUs = cmsSection(cms, 'why_choose_us');
    const commitment = cmsSection(cms, 'our_commitment');

    return (
        <PublicLayout title="About Us">
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
                    <p className="text-sm font-medium text-accent">{content.tagline}</p>
                    <h1 className="mt-3 text-4xl font-semibold">{hero?.heading}</h1>
                    <p className="mt-3 max-w-2xl text-white/80">{hero?.body}</p>
                </PageContainer>
            </section>
            <section className="py-16">
                <PageContainer className="space-y-12">
                    {whoWeAre ? (
                        <div>
                            <SectionHeading title={whoWeAre.heading ?? ''} description={whoWeAre.body ?? ''} />
                            <div className="mt-8 grid gap-4 md:grid-cols-3">
                                {activeItems(whoWeAre).map((item) => (
                                    <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
                                ))}
                            </div>
                        </div>
                    ) : null}
                    {whatWeDo ? (
                        <div>
                            <SectionHeading title={whatWeDo.heading ?? ''} description={whatWeDo.body ?? ''} />
                            <div className="mt-8 grid gap-4 md:grid-cols-2">
                                {activeItems(whatWeDo).map((item) => (
                                    <ServiceCard
                                        key={item.id}
                                        title={item.title}
                                        summary={item.description ?? ''}
                                        bullets={item.bullets}
                                    />
                                ))}
                            </div>
                        </div>
                    ) : null}
                    {approach ? (
                        <div>
                            <SectionHeading title={approach.heading ?? ''} description={approach.body ?? ''} />
                            <div className="mt-8 grid gap-4 md:grid-cols-3">
                                {activeItems(approach).map((item) => (
                                    <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
                                ))}
                            </div>
                        </div>
                    ) : null}
                    {industries ? (
                        <div>
                            <h2 className="text-2xl font-semibold text-ink">{industries.heading}</h2>
                            <p className="mt-2 max-w-2xl text-muted">{industries.body}</p>
                            <div className="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3">
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
                    {commitment ? (
                        <div>
                            <SectionHeading title={commitment.heading ?? ''} description={commitment.body ?? ''} />
                            {commitment.image_url ? (
                                <img src={commitment.image_url} alt="" className="mt-8 max-h-72 w-full rounded-xl object-cover" />
                            ) : null}
                            <div className="mt-8 grid gap-4 md:grid-cols-2">
                                {activeItems(commitment).map((item) => (
                                    <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
                                ))}
                            </div>
                        </div>
                    ) : null}
                </PageContainer>
            </section>
            <CtaSection
                title="Work with OurNear Talent"
                description="Speak with us about hiring, training, or your next role."
                actionLabel="Contact us"
                actionHref="/contact"
            />
        </PublicLayout>
    );
}
