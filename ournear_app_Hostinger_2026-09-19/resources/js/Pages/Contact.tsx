import type { FormEvent } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/Public/Button';
import ContactCard from '@/Components/Public/ContactCard';
import FormField from '@/Components/Public/FormField';
import HoneypotField from '@/Components/Public/HoneypotField';
import PageContainer from '@/Components/Public/PageContainer';
import TurnstileField from '@/Components/Public/TurnstileField';
import { cmsSection, type CmsPage } from '@/types/cms';
import type { SharedPageProps } from '@/types/page';
import type { SiteContent } from '@/types/public';

type ContactProps = {
    content: SiteContent;
    cms: CmsPage;
    interestOptions: string[];
};

const selectClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function Contact({ content, cms, interestOptions }: ContactProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const submitted = Boolean(flash.success);
    const hero = cmsSection(cms, 'hero');
    const details = cmsSection(cms, 'details');
    const extra = details?.extra ?? {};

    const form = useForm({
        full_name: '',
        mobile_number: '',
        email: '',
        interested_in: '',
        message: '',
        fax: '',
        'cf-turnstile-response': '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/contact');
    };

    return (
        <PublicLayout title="Contact Us">
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
                <PageContainer className="grid gap-8 lg:grid-cols-2">
                    <div className="space-y-4">
                        {extra.help || details?.body ? (
                            <p className="text-sm text-muted">{extra.help || details?.body}</p>
                        ) : null}
                        <ContactCard label="Call Us" value={content.contact.phone} />
                        <ContactCard label="WhatsApp" value={extra.whatsapp || content.contact.whatsapp || '—'} />
                        <ContactCard label="Email" value={content.contact.email} />
                        <ContactCard label="Office" value={content.contact.address} />
                        <ContactCard label="Hours" value={extra.hours || content.contact.hours} />
                    </div>
                    <div className="rounded-xl border border-line bg-white p-6">
                        <h2 className="text-xl font-semibold text-ink">Send a message</h2>
                        {submitted ? (
                            <p className="mt-6 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                                {flash.success}
                            </p>
                        ) : (
                            <form onSubmit={submit} className="mt-6 space-y-4">
                                <div>
                                    <FormField
                                        id="full_name"
                                        label="Full Name *"
                                        value={form.data.full_name}
                                        onChange={(event) => form.setData('full_name', event.currentTarget.value)}
                                    />
                                    {form.errors.full_name && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.full_name}</p>
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
                                        label="Email Address"
                                        type="email"
                                        value={form.data.email}
                                        onChange={(event) => form.setData('email', event.currentTarget.value)}
                                    />
                                    {form.errors.email && <p className="mt-1 text-sm text-danger">{form.errors.email}</p>}
                                </div>
                                <div>
                                    <label htmlFor="interested_in" className="mb-1 block text-sm font-medium text-ink">
                                        I'm Interested In *
                                    </label>
                                    <select
                                        id="interested_in"
                                        className={selectClass}
                                        value={form.data.interested_in}
                                        onChange={(event) => form.setData('interested_in', event.target.value)}
                                    >
                                        <option value="">Select a topic</option>
                                        {interestOptions.map((option) => (
                                            <option key={option} value={option}>
                                                {option}
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.interested_in && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.interested_in}</p>
                                    )}
                                </div>
                                <div>
                                    <FormField
                                        id="message"
                                        label="Message *"
                                        as="textarea"
                                        value={form.data.message}
                                        onChange={(event) => form.setData('message', event.currentTarget.value)}
                                    />
                                    {form.errors.message && (
                                        <p className="mt-1 text-sm text-danger">{form.errors.message}</p>
                                    )}
                                </div>
                                <HoneypotField value={form.data.fax} onChange={(value) => form.setData('fax', value)} />
                                <TurnstileField onToken={(token) => form.setData('cf-turnstile-response', token)} />
                                {form.errors['cf-turnstile-response'] && (
                                    <p className="text-sm text-danger">{form.errors['cf-turnstile-response']}</p>
                                )}
                                <Button type="submit">{form.processing ? 'Sending…' : 'Send Enquiry'}</Button>
                            </form>
                        )}
                    </div>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
