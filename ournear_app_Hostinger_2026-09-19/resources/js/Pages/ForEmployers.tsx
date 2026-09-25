import type { FormEvent } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/Public/Button';
import CtaSection from '@/Components/Public/CtaSection';
import FormField from '@/Components/Public/FormField';
import HoneypotField from '@/Components/Public/HoneypotField';
import IndustryCard from '@/Components/Public/IndustryCard';
import PageContainer from '@/Components/Public/PageContainer';
import SectionHeading from '@/Components/Public/SectionHeading';
import ServiceCard from '@/Components/Public/ServiceCard';
import TurnstileField from '@/Components/Public/TurnstileField';
import { activeItems, cmsSection, type CmsPage } from '@/types/cms';
import type { SharedPageProps } from '@/types/page';
import type { SiteContent } from '@/types/public';

type ForEmployersProps = {
    content: SiteContent;
    cms: CmsPage;
    industries: string[];
    workforceTypes: string[];
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function ForEmployers({ content, cms, industries, workforceTypes }: ForEmployersProps) {
    const page = usePage<SharedPageProps>().props;
    const flash = page.flash;
    const user = page.auth.user;
    const submitted = Boolean(flash.success);
    const hero = cmsSection(cms, 'hero');
    const provide = cmsSection(cms, 'what_we_provide');
    const deliver = cmsSection(cms, 'how_we_deliver');
    const get = cmsSection(cms, 'what_employers_get');
    const workforce = cmsSection(cms, 'workforce');
    const industrySection = cmsSection(cms, 'industries');
    const getInTouch = cmsSection(cms, 'get_in_touch');

    const form = useForm({
        company_name: '',
        contact_person: '',
        mobile_number: '',
        email: '',
        industry: '',
        project_location: '',
        workforce_type: '',
        number_of_people: 1,
        required_skills: '',
        expected_start_date: '',
        training_required: '0',
        additional_requirements: '',
        fax: '',
        'cf-turnstile-response': '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/for-employers');
    };

    return (
        <PublicLayout title="For Employers">
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
                    {!user ? (
                        <Link
                            href="/login"
                            className="mt-6 inline-flex min-h-11 items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-brand hover:bg-white/90"
                        >
                            Login as Employer
                        </Link>
                    ) : user.role === 'employer' ? (
                        <Link
                            href="/account"
                            className="mt-6 inline-flex min-h-11 items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-brand hover:bg-white/90"
                        >
                            Employer dashboard
                        </Link>
                    ) : null}
                </PageContainer>
            </section>
            <section className="py-16">
                <PageContainer className="space-y-14">
                    {provide ? (
                        <div>
                            <SectionHeading title={provide.heading ?? ''} description={provide.body ?? ''} />
                            <div className="mt-8 grid gap-4 md:grid-cols-3">
                                {activeItems(provide).map((item) => (
                                    <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
                                ))}
                            </div>
                        </div>
                    ) : null}
                    {deliver ? (
                        <div>
                            <SectionHeading title={deliver.heading ?? ''} description={deliver.body ?? ''} />
                            <ol className="mt-8 grid gap-4 md:grid-cols-4">
                                {activeItems(deliver).map((item, index) => (
                                    <li key={item.id} className="rounded-xl border border-line bg-white p-6">
                                        <p className="text-xs font-semibold uppercase tracking-wide text-accent">
                                            Step {index + 1}
                                        </p>
                                        <h3 className="mt-2 text-lg font-semibold text-ink">{item.title}</h3>
                                        {item.description ? <p className="mt-2 text-sm text-muted">{item.description}</p> : null}
                                    </li>
                                ))}
                            </ol>
                        </div>
                    ) : null}
                    {get ? (
                        <div>
                            <SectionHeading title={get.heading ?? ''} description={get.body ?? ''} />
                            <div className="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                {activeItems(get).map((item) => (
                                    <ServiceCard key={item.id} title={item.title} summary={item.description ?? ''} />
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
                    {industrySection ? (
                        <div>
                            <SectionHeading title={industrySection.heading ?? ''} description={industrySection.body ?? ''} />
                            <div className="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3">
                                {activeItems(industrySection).map((item) => (
                                    <IndustryCard key={item.id} name={item.title} />
                                ))}
                            </div>
                        </div>
                    ) : null}
                    <section className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-xl font-semibold text-ink">{getInTouch?.heading ?? 'Get In Touch'}</h2>
                        <p className="mt-2 text-sm text-muted">
                            {getInTouch?.body ??
                                'Tell us about the people and training you need. We will follow up from this request.'}
                        </p>
                        {submitted ? (
                            <p className="mt-6 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                                {flash.success}
                            </p>
                        ) : (
                            <form onSubmit={submit} className="mt-6 grid gap-4 md:grid-cols-2">
                                <div>
                                    <FormField
                                        id="company_name"
                                        label="Company Name *"
                                        value={form.data.company_name}
                                        onChange={(event) => form.setData('company_name', event.currentTarget.value)}
                                    />
                                    {form.errors.company_name && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.company_name}</p>
                                    )}
                                </div>
                                <div>
                                    <FormField
                                        id="contact_person"
                                        label="Contact Person *"
                                        value={form.data.contact_person}
                                        onChange={(event) => form.setData('contact_person', event.currentTarget.value)}
                                    />
                                    {form.errors.contact_person && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.contact_person}</p>
                                    )}
                                </div>
                                <div>
                                    <FormField
                                        id="mobile_number"
                                        label="Mobile Number *"
                                        value={form.data.mobile_number}
                                        onChange={(event) => form.setData('mobile_number', event.currentTarget.value)}
                                    />
                                    {form.errors.mobile_number && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.mobile_number}</p>
                                    )}
                                </div>
                                <div>
                                    <FormField
                                        id="email"
                                        label="Email *"
                                        type="email"
                                        value={form.data.email}
                                        onChange={(event) => form.setData('email', event.currentTarget.value)}
                                    />
                                    {form.errors.email && <p className="mt-1 text-sm text-danger">{form.errors.email}</p>}
                                </div>
                                <div>
                                    <label htmlFor="industry" className="mb-1 block text-sm font-medium text-ink">
                                        Industry *
                                    </label>
                                    <select
                                        id="industry"
                                        className={selectClass}
                                        value={form.data.industry}
                                        onChange={(event) => form.setData('industry', event.target.value)}
                                    >
                                        <option value="">Select industry</option>
                                        {industries.map((name) => (
                                            <option key={name} value={name}>
                                                {name}
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.industry && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.industry}</p>
                                    )}
                                </div>
                                <div>
                                    <FormField
                                        id="project_location"
                                        label="Project Location *"
                                        value={form.data.project_location}
                                        onChange={(event) => form.setData('project_location', event.currentTarget.value)}
                                    />
                                    {form.errors.project_location && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.project_location}</p>
                                    )}
                                </div>
                                <div>
                                    <label htmlFor="workforce_type" className="mb-1 block text-sm font-medium text-ink">
                                        Workforce Required *
                                    </label>
                                    <select
                                        id="workforce_type"
                                        className={selectClass}
                                        value={form.data.workforce_type}
                                        onChange={(event) => form.setData('workforce_type', event.target.value)}
                                    >
                                        <option value="">Select type</option>
                                        {workforceTypes.map((type) => (
                                            <option key={type} value={type}>
                                                {type}
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.workforce_type && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.workforce_type}</p>
                                    )}
                                </div>
                                <div>
                                    <FormField
                                        id="number_of_people"
                                        label="Number of People Required *"
                                        type="number"
                                        min={1}
                                        value={form.data.number_of_people}
                                        onChange={(event) => form.setData('number_of_people', Number(event.currentTarget.value))}
                                    />
                                    {form.errors.number_of_people && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.number_of_people}</p>
                                    )}
                                </div>
                                <div className="md:col-span-2">
                                    <FormField
                                        id="required_skills"
                                        label="Required Skills / Positions *"
                                        as="textarea"
                                        value={form.data.required_skills}
                                        onChange={(event) => form.setData('required_skills', event.currentTarget.value)}
                                    />
                                    {form.errors.required_skills && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.required_skills}</p>
                                    )}
                                </div>
                                <div>
                                    <FormField
                                        id="expected_start_date"
                                        label="Expected Start Date"
                                        type="date"
                                        value={form.data.expected_start_date}
                                        onChange={(event) => form.setData('expected_start_date', event.currentTarget.value)}
                                    />
                                    {form.errors.expected_start_date && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.expected_start_date}</p>
                                    )}
                                </div>
                                <div>
                                    <label htmlFor="training_required" className="mb-1 block text-sm font-medium text-ink">
                                        Training Required?
                                    </label>
                                    <select
                                        id="training_required"
                                        className={selectClass}
                                        value={form.data.training_required}
                                        onChange={(event) => form.setData('training_required', event.target.value)}
                                    >
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                    {form.errors.training_required && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.training_required}</p>
                                    )}
                                </div>
                                <div className="md:col-span-2">
                                    <FormField
                                        id="additional_requirements"
                                        label="Additional Requirements"
                                        as="textarea"
                                        value={form.data.additional_requirements}
                                        onChange={(event) =>
                                            form.setData('additional_requirements', event.currentTarget.value)
                                        }
                                    />
                                    {form.errors.additional_requirements && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.additional_requirements}</p>
                                    )}
                                </div>
                                <div className="md:col-span-2">
                                    <HoneypotField value={form.data.fax} onChange={(value) => form.setData('fax', value)} />
                                    <TurnstileField onToken={(token) => form.setData('cf-turnstile-response', token)} />
                                    {form.errors['cf-turnstile-response'] && (
                                        <p className="text-sm text-danger">{form.errors['cf-turnstile-response']}</p>
                                    )}
                                </div>
                                <div>
                                    <Button type="submit">{form.processing ? 'Submitting…' : 'Submit Request'}</Button>
                                </div>
                            </form>
                        )}
                    </section>
                </PageContainer>
            </section>
            <CtaSection
                title="Ready to hire?"
                description="Start a conversation about the people and training you need."
                actionLabel="Contact us"
                actionHref="/contact"
            />
        </PublicLayout>
    );
}
