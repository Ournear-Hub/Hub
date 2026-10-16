import type { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/atoms/Button';
import FormField from '@/Components/Public/FormField';
import HoneypotField from '@/Components/Public/HoneypotField';
import IndustryCard from '@/Components/Public/IndustryCard';
import PageContainer from '@/Components/Public/PageContainer';
import SectionHeading from '@/Components/Public/SectionHeading';
import ServiceCard from '@/Components/Public/ServiceCard';
import TurnstileField from '@/Components/Public/TurnstileField';
import type { SiteContent } from '@/types/public';

type Option = {
    value: string | number;
    label: string;
};

type PublicPartner = {
    name: string;
    business_id: string;
    status: 'Pending' | 'Approved';
    joined_at: string | null;
    works_for: string;
};

type FreelancersProps = {
    content: SiteContent;
    industries: string[];
    submission_token: string;
    partners?: PublicPartner[];
    options: {
        locations: Option[];
        industries: Option[];
        categories: Option[];
        experiences: Option[];
        employment_types: Option[];
        work_types: Option[];
    };
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function FreelancersIndex({ content, industries, submission_token, partners = [], options }: FreelancersProps) {
    const form = useForm({
        submission_token,
        name: '',
        mobile: '',
        email: '',
        current_location_id: '',
        preferred_location_ids: [] as Array<string | number>,
        primary_skill: '',
        current_company: '',
        industry_id: '',
        job_category_id: '',
        experience_range_id: '',
        employment_type_id: '',
        preferred_work_type: '',
        expected_rate: '',
        availability: '',
        skills: '',
        summary: '',
        resume: null as File | null,
        certificate: null as File | null,
        consent: false,
        fax: '',
        'cf-turnstile-response': '',
    });

    const togglePreferredLocation = (value: string | number) => {
        const current = form.data.preferred_location_ids.map(String);
        const next = current.includes(String(value))
            ? form.data.preferred_location_ids.filter((item) => String(item) !== String(value))
            : [...form.data.preferred_location_ids, value];
        form.setData('preferred_location_ids', next);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) {
            return;
        }
        form.post('/freelancers', { forceFormData: true });
    };

    return (
        <PublicLayout title="Freelancer Network">
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <p className="text-sm font-medium text-accent">{content.tagline}</p>
                    <h1 className="mt-3 text-4xl font-semibold">Join Our Freelancer Network</h1>
                    <p className="mt-3 max-w-2xl text-white/80">
                        Register as an OurNear Freelancer Partner. This is a business partnership registration, not a job
                        application and not a candidate account.
                    </p>
                    <div className="mt-8">
                        <Button surface="public" href="#become-partner">Become a Freelancer Partner</Button>
                    </div>
                </PageContainer>
            </section>

            <section className="py-16">
                <PageContainer className="space-y-14">
                    <div>
                        <SectionHeading
                            title="Business partnership introduction"
                            description="Build opportunities through our freelancer partnership network. OurNear Talent works with independent partners to support recruitment and workforce needs."
                        />
                        <div className="mt-8 grid gap-4 md:grid-cols-3">
                            <ServiceCard
                                title="Partnership, not a job listing"
                                summary="This page is for freelancer partners. It is not a vacancy and does not create a candidate account."
                            />
                            <ServiceCard
                                title="How partnership works"
                                summary="You register your service area and experience. OurNear Talent reviews the partnership record and contacts suitable partners."
                            />
                            <ServiceCard
                                title="No login required"
                                summary="The public partnership form does not require login or registration. Save your Freelancer ID after submission."
                            />
                        </div>
                    </div>

                    <div>
                        <SectionHeading
                            title="Who can join"
                            description="Independent recruiters, coordinators, and specialist partners who want to work with OurNear Talent."
                        />
                        <div className="mt-8 grid gap-4 md:grid-cols-3">
                            <ServiceCard title="Recruitment partners" summary="People with an existing hiring or referral network." />
                            <ServiceCard title="Industry specialists" summary="Partners who cover mining, infrastructure, construction, or related sectors." />
                            <ServiceCard title="Independent operators" summary="Freelancers who want a formal partnership record with OurNear Talent." />
                        </div>
                    </div>

                    {industries.length > 0 ? (
                        <div>
                            <SectionHeading
                                title="Industries we commonly support"
                                description="Partnership needs vary. These are industries OurNear Talent already works with."
                            />
                            <div className="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3">
                                {industries.map((name) => (
                                    <IndustryCard key={name} name={name} />
                                ))}
                            </div>
                        </div>
                    ) : null}

                    <div>
                        <SectionHeading
                            title="Existing Freelancer Network"
                            description="Public partnership directory. Only profile identity, Freelancer ID, status, joined date, and service area are shown."
                        />
                        {partners.length === 0 ? (
                            <p className="mt-6 rounded-xl border border-line bg-white p-6 text-sm text-muted">
                                Partnership cards will appear here after registrations are received.
                            </p>
                        ) : (
                            <div className="mt-8 grid gap-4 md:grid-cols-3">
                                {partners.map((partner) => (
                                    <article
                                        key={partner.business_id}
                                        className="flex min-w-0 items-start gap-4 rounded-xl border border-line bg-white p-5"
                                    >
                                        <div
                                            aria-label={`${partner.name} profile image`}
                                            className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand/10 text-base font-semibold text-brand"
                                        >
                                            {partner.name
                                                .split(/\s+/)
                                                .filter(Boolean)
                                                .slice(0, 2)
                                                .map((part) => part[0])
                                                .join('')
                                                .toUpperCase() || 'F'}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <h3 className="truncate text-lg font-semibold text-ink">{partner.name}</h3>
                                            <p className="mt-1 text-sm font-medium text-brand">{partner.business_id}</p>
                                            <p className="mt-3 text-sm text-muted">Joined {partner.joined_at ?? '—'}</p>
                                            <p className="mt-1 text-sm text-muted">Works for: {partner.works_for}</p>
                                            <p className="mt-3 flex items-center gap-2 text-sm text-muted">
                                                <span>Current status</span>
                                                <span
                                                    className={`rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                        partner.status === 'Approved'
                                                            ? 'bg-success/10 text-success'
                                                            : 'bg-accent/20 text-ink'
                                                    }`}
                                                >
                                                    {partner.status}
                                                </span>
                                            </p>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}
                    </div>

                    <section id="become-partner" className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-xl font-semibold text-ink">Become a Freelancer Partner</h2>
                        <p className="mt-2 text-sm text-muted">
                            Complete the partnership form. This does not create a user or candidate account and is not a
                            job application.
                        </p>

                        <form onSubmit={submit} className="mt-6 grid gap-4 md:grid-cols-2" noValidate>
                            <input type="hidden" name="submission_token" value={form.data.submission_token} />

                            <div>
                                <FormField id="name" label="Full name *" value={form.data.name} autoComplete="name" onChange={(event) => form.setData('name', event.currentTarget.value)} />
                                {form.errors.name && <p className="mt-1 text-sm text-danger">{form.errors.name}</p>}
                            </div>
                            <div>
                                <FormField id="mobile" label="Mobile *" value={form.data.mobile} autoComplete="tel" onChange={(event) => form.setData('mobile', event.currentTarget.value)} />
                                {form.errors.mobile && <p className="mt-1 text-sm text-danger">{form.errors.mobile}</p>}
                            </div>
                            <div>
                                <FormField id="email" label="Email *" type="email" value={form.data.email} autoComplete="email" onChange={(event) => form.setData('email', event.currentTarget.value)} />
                                {form.errors.email && <p className="mt-1 text-sm text-danger">{form.errors.email}</p>}
                            </div>
                            <div>
                                <label htmlFor="current_location_id" className="mb-1 block text-sm font-medium text-ink">City / location *</label>
                                <select id="current_location_id" className={selectClass} value={form.data.current_location_id} onChange={(event) => form.setData('current_location_id', event.target.value)}>
                                    <option value="">Select location</option>
                                    {options.locations.map((item) => (
                                        <option key={String(item.value)} value={item.value}>{item.label}</option>
                                    ))}
                                </select>
                                {form.errors.current_location_id && <p className="mt-1 text-sm text-danger">{form.errors.current_location_id}</p>}
                            </div>

                            <div>
                                <label htmlFor="employment_type_id" className="mb-1 block text-sm font-medium text-ink">Freelancer / partner type</label>
                                <select id="employment_type_id" className={selectClass} value={form.data.employment_type_id} onChange={(event) => form.setData('employment_type_id', event.target.value)}>
                                    <option value="">Select type</option>
                                    {options.employment_types.map((item) => (
                                        <option key={String(item.value)} value={item.value}>{item.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <FormField id="primary_skill" label="Area of work / service *" value={form.data.primary_skill} onChange={(event) => form.setData('primary_skill', event.currentTarget.value)} />
                                {form.errors.primary_skill && <p className="mt-1 text-sm text-danger">{form.errors.primary_skill}</p>}
                            </div>
                            <div>
                                <label htmlFor="industry_id" className="mb-1 block text-sm font-medium text-ink">Industry *</label>
                                <select id="industry_id" className={selectClass} value={form.data.industry_id} onChange={(event) => form.setData('industry_id', event.target.value)}>
                                    <option value="">Select industry</option>
                                    {options.industries.map((item) => (
                                        <option key={String(item.value)} value={item.value}>{item.label}</option>
                                    ))}
                                </select>
                                {form.errors.industry_id && <p className="mt-1 text-sm text-danger">{form.errors.industry_id}</p>}
                            </div>
                            <div>
                                <label htmlFor="job_category_id" className="mb-1 block text-sm font-medium text-ink">Functional area</label>
                                <select id="job_category_id" className={selectClass} value={form.data.job_category_id} onChange={(event) => form.setData('job_category_id', event.target.value)}>
                                    <option value="">Select functional area</option>
                                    {options.categories.map((item) => (
                                        <option key={String(item.value)} value={item.value}>{item.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label htmlFor="experience_range_id" className="mb-1 block text-sm font-medium text-ink">Years of experience *</label>
                                <select id="experience_range_id" className={selectClass} value={form.data.experience_range_id} onChange={(event) => form.setData('experience_range_id', event.target.value)}>
                                    <option value="">Select experience</option>
                                    {options.experiences.map((item) => (
                                        <option key={String(item.value)} value={item.value}>{item.label}</option>
                                    ))}
                                </select>
                                {form.errors.experience_range_id && <p className="mt-1 text-sm text-danger">{form.errors.experience_range_id}</p>}
                            </div>
                            <div>
                                <FormField id="current_company" label="Current organisation / business" value={form.data.current_company} onChange={(event) => form.setData('current_company', event.currentTarget.value)} />
                            </div>
                            <div>
                                <label htmlFor="preferred_work_type" className="mb-1 block text-sm font-medium text-ink">Preferred work area</label>
                                <select id="preferred_work_type" className={selectClass} value={form.data.preferred_work_type} onChange={(event) => form.setData('preferred_work_type', event.target.value)}>
                                    <option value="">Select work area</option>
                                    {options.work_types.map((item) => (
                                        <option key={String(item.value)} value={item.value}>{item.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <FormField id="availability" label="Availability" value={form.data.availability} onChange={(event) => form.setData('availability', event.currentTarget.value)} />
                            </div>
                            <div>
                                <FormField id="expected_rate" label="Candidates / clients handled, if relevant" value={form.data.expected_rate} onChange={(event) => form.setData('expected_rate', event.currentTarget.value)} />
                            </div>

                            <fieldset className="md:col-span-2">
                                <legend className="mb-2 text-sm font-medium text-ink">Geographic coverage</legend>
                                <div className="grid gap-2 sm:grid-cols-2">
                                    {options.locations.map((item) => (
                                        <label key={String(item.value)} className="flex items-start gap-2 text-sm text-ink">
                                            <input
                                                type="checkbox"
                                                checked={form.data.preferred_location_ids.map(String).includes(String(item.value))}
                                                onChange={() => togglePreferredLocation(item.value)}
                                            />
                                            <span>{item.label}</span>
                                        </label>
                                    ))}
                                </div>
                            </fieldset>

                            <div className="md:col-span-2">
                                <FormField id="skills" label="Skills / expertise" as="textarea" value={form.data.skills} placeholder="Separate skills with commas" onChange={(event) => form.setData('skills', event.currentTarget.value)} />
                            </div>
                            <div className="md:col-span-2">
                                <FormField id="summary" label="Why do you want to partner with OurNear?" as="textarea" value={form.data.summary} onChange={(event) => form.setData('summary', event.currentTarget.value)} />
                            </div>
                            <div>
                                <label htmlFor="resume" className="mb-1 block text-sm font-medium text-ink">Supporting document (optional)</label>
                                <input id="resume" type="file" accept=".pdf,.doc,.docx" className="w-full text-sm text-ink" onChange={(event) => form.setData('resume', event.currentTarget.files?.[0] ?? null)} />
                                {form.errors.resume && <p className="mt-1 text-sm text-danger">{form.errors.resume}</p>}
                            </div>
                            <div>
                                <label htmlFor="certificate" className="mb-1 block text-sm font-medium text-ink">Certificate (optional)</label>
                                <input id="certificate" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" className="w-full text-sm text-ink" onChange={(event) => form.setData('certificate', event.currentTarget.files?.[0] ?? null)} />
                            </div>
                            <div className="md:col-span-2">
                                <label className="flex items-start gap-2 text-sm text-ink">
                                    <input type="checkbox" checked={form.data.consent} onChange={(event) => form.setData('consent', event.currentTarget.checked)} />
                                    <span>I consent to OurNear Talent contacting me about this freelancer partnership and agree to the partnership terms and privacy policy.</span>
                                </label>
                                {form.errors.consent && <p className="mt-1 text-sm text-danger">{form.errors.consent}</p>}
                            </div>
                            <div className="md:col-span-2">
                                <HoneypotField value={form.data.fax} onChange={(value) => form.setData('fax', value)} />
                                <TurnstileField onToken={(token) => form.setData('cf-turnstile-response', token)} />
                            </div>
                            <div className="md:col-span-2">
                                <Button surface="public" type="submit">{form.processing ? 'Submitting…' : 'Become a Freelancer Partner'}</Button>
                            </div>
                        </form>
                    </section>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
