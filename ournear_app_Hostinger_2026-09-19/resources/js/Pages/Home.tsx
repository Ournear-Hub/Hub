import type { FormEvent } from 'react';
import { router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/Public/Button';
import CtaSection from '@/Components/Public/CtaSection';
import FormField from '@/Components/Public/FormField';
import IndustryCard from '@/Components/Public/IndustryCard';
import JobCard from '@/Components/Public/JobCard';
import PageContainer from '@/Components/Public/PageContainer';
import SectionHeading from '@/Components/Public/SectionHeading';
import ServiceCard from '@/Components/Public/ServiceCard';
import type { PublicJobCard } from '@/types/jobs';
import { activeItems, cmsSection, type CmsPage } from '@/types/cms';
import type { SiteContent } from '@/types/public';

type HomeProps = {
    content: SiteContent;
    cms: CmsPage;
    featuredJobs: PublicJobCard[];
};

export default function Home({ content, cms, featuredJobs }: HomeProps) {
    const hero = cmsSection(cms, 'hero');
    const whatWeDo = cmsSection(cms, 'what_we_do');
    const coreServices = cmsSection(cms, 'core_services');
    const industries = cmsSection(cms, 'industries');
    const howWeWork = cmsSection(cms, 'how_we_work');
    const whyChooseUs = cmsSection(cms, 'why_choose_us');
    const commitment = cmsSection(cms, 'our_commitment');

    const search = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get('/jobs', {
            keyword: String(form.get('keyword') ?? ''),
            location: String(form.get('location') ?? ''),
        });
    };

    return (
        <PublicLayout title="Home">
            <section
                className="bg-brand py-16 text-white sm:py-20"
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
                    <h1 className="mt-3 max-w-3xl whitespace-pre-line text-4xl font-semibold tracking-tight sm:text-5xl">
                        {hero?.heading}
                    </h1>
                    <p className="mt-4 max-w-2xl text-base text-white/80">{hero?.body}</p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        <Button href="/jobs">{hero?.primary_button_label || 'Find Jobs'}</Button>
                        <Button href="/for-employers" variant="light">
                            {hero?.secondary_button_label || 'For Employers'}
                        </Button>
                    </div>
                </PageContainer>
            </section>

            <section className="py-10">
                <PageContainer>
                    <form
                        onSubmit={search}
                        className="grid gap-3 rounded-xl border border-line bg-white p-4 sm:grid-cols-3"
                        aria-label="Job search"
                    >
                        <FormField id="keywords" name="keyword" label="Keywords" placeholder="Role or skill" />
                        <FormField id="location" name="location" label="Location" placeholder="City or area" />
                        <div className="flex items-end">
                            <Button type="submit" className="w-full">
                                Search jobs
                            </Button>
                        </div>
                    </form>
                </PageContainer>
            </section>

            <section className="pb-16">
                <PageContainer>
                    <SectionHeading
                        eyebrow="Jobs"
                        title="Featured jobs"
                        description="Highlighted roles that are open now."
                    />
                    {featuredJobs.length === 0 ? (
                        <p className="mt-8 rounded-xl border border-line bg-white p-6 text-sm text-muted">
                            There are no featured jobs at the moment. Browse all open roles on the Jobs page.
                        </p>
                    ) : (
                        <div className="mt-8 grid gap-4 md:grid-cols-3">
                            {featuredJobs.map((job) => (
                                <JobCard key={job.slug} job={job} />
                            ))}
                        </div>
                    )}
                </PageContainer>
            </section>

            {whatWeDo ? (
                <section className="bg-white py-16">
                    <PageContainer>
                        <SectionHeading eyebrow="What we do" title={whatWeDo.heading ?? ''} description={whatWeDo.body ?? ''} />
                        <div className="mt-8 grid gap-4 md:grid-cols-3">
                            {activeItems(whatWeDo).map((item) => (
                                <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
                            ))}
                        </div>
                    </PageContainer>
                </section>
            ) : null}

            {coreServices ? (
                <section className="py-16">
                    <PageContainer>
                        <SectionHeading eyebrow="Core services" title={coreServices.heading ?? ''} description={coreServices.body ?? ''} />
                        <div className="mt-8 grid gap-4 md:grid-cols-3">
                            {activeItems(coreServices).map((item) => (
                                <ServiceCard
                                    key={item.id}
                                    title={item.title}
                                    summary={item.description ?? ''}
                                    bullets={item.bullets}
                                />
                            ))}
                        </div>
                    </PageContainer>
                </section>
            ) : null}

            {industries ? (
                <section className="bg-white py-16">
                    <PageContainer>
                        <SectionHeading eyebrow="Industries" title={industries.heading ?? ''} description={industries.body ?? ''} />
                        <div className="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3">
                            {activeItems(industries).map((item) => (
                                <IndustryCard key={item.id} name={item.title} />
                            ))}
                        </div>
                    </PageContainer>
                </section>
            ) : null}

            {howWeWork ? (
                <section className="py-16">
                    <PageContainer>
                        <SectionHeading eyebrow="How we work" title={howWeWork.heading ?? ''} description={howWeWork.body ?? ''} />
                        <div className="mt-8 grid gap-4 md:grid-cols-5">
                            {activeItems(howWeWork).map((item) => (
                                <article key={item.id} className="rounded-xl border border-line bg-white p-6">
                                    <h3 className="text-lg font-semibold text-ink">{item.title}</h3>
                                    <p className="mt-2 text-sm text-muted">{item.description}</p>
                                </article>
                            ))}
                        </div>
                    </PageContainer>
                </section>
            ) : null}

            {whyChooseUs ? (
                <section className="bg-white py-16">
                    <PageContainer>
                        <SectionHeading
                            eyebrow="Why choose us"
                            title={whyChooseUs.heading ?? ''}
                            description={whyChooseUs.body ?? ''}
                        />
                        <div className="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            {activeItems(whyChooseUs).map((item) => (
                                <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
                            ))}
                        </div>
                    </PageContainer>
                </section>
            ) : null}

            {commitment ? (
                <section className="py-16">
                    <PageContainer>
                        <SectionHeading
                            eyebrow="Our commitment"
                            title={commitment.heading ?? ''}
                            description={commitment.body ?? ''}
                        />
                        {commitment.image_url ? (
                            <img src={commitment.image_url} alt="" className="mt-8 max-h-72 w-full rounded-xl object-cover" />
                        ) : null}
                        <div className="mt-8 grid gap-4 md:grid-cols-3">
                            {activeItems(commitment).map((item) => (
                                <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
                            ))}
                        </div>
                    </PageContainer>
                </section>
            ) : null}

            <CtaSection
                title="Talk to OurNear Talent"
                description="Tell us what you need. We will point you to jobs, services, or employer support."
                actionLabel="Contact us"
                actionHref="/contact"
            />
        </PublicLayout>
    );
}
