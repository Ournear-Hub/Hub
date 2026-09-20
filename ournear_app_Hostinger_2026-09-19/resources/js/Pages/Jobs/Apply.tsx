import { Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/Public/Button';
import FormField from '@/Components/Public/FormField';
import HoneypotField from '@/Components/Public/HoneypotField';
import PageContainer from '@/Components/Public/PageContainer';
import TurnstileField from '@/Components/Public/TurnstileField';
import type { PublicJobDetail } from '@/types/jobs';
import type { SharedPageProps } from '@/types/page';
import type { SiteContent } from '@/types/public';

type JobApplyProps = {
    content: SiteContent;
    job: PublicJobDetail;
    canApply: boolean;
    closedMessage: string | null;
    prefill: {
        full_name: string;
        mobile: string;
        email: string;
        current_location: string;
        experience: string;
        qualification: string;
    };
};

export default function JobApply({ content, job, canApply, closedMessage, prefill }: JobApplyProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const submitted = Boolean(flash.success);

    const form = useForm({
        full_name: prefill.full_name,
        mobile: prefill.mobile,
        email: prefill.email,
        current_location: prefill.current_location,
        experience: prefill.experience,
        qualification: prefill.qualification,
        resume: null as File | null,
        additional_information: '',
        consent: false,
        fax: '',
        'cf-turnstile-response': '',
    });
    const jobError = (form.errors as Record<string, string | undefined>).job;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/jobs/${job.slug}/apply`, { forceFormData: true });
    };

    return (
        <PublicLayout title={`Apply · ${job.title}`}>
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <p className="text-sm font-medium text-accent">{content.tagline}</p>
                    <h1 className="mt-3 text-4xl font-semibold">Apply</h1>
                    <p className="mt-3 max-w-2xl text-white/80">
                        {job.title} · {job.project}
                    </p>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer className="max-w-2xl">
                    {submitted ? (
                        <div className="rounded-xl border border-success/20 bg-white p-6">
                            <h2 className="text-xl font-semibold text-ink">Application received</h2>
                            <p className="mt-3 text-sm text-muted">{flash.success}</p>
                            <p className="mt-2 text-sm text-muted">
                                OurNear Talent will review your application. You can apply without an account.
                            </p>
                            <Link href={`/jobs/${job.slug}`} className="mt-4 inline-block text-sm font-medium text-brand">
                                Back to job details
                            </Link>
                        </div>
                    ) : !canApply ? (
                        <div className="rounded-xl border border-line bg-white p-6">
                            <h2 className="text-xl font-semibold text-ink">Applications closed</h2>
                            <p className="mt-3 text-sm text-muted">{closedMessage}</p>
                            <Link href="/jobs" className="mt-4 inline-block text-sm font-medium text-brand">
                                Browse open roles
                            </Link>
                        </div>
                    ) : (
                        <form onSubmit={submit} className="space-y-4 rounded-xl border border-line bg-white p-6">
                            {jobError && <p className="text-sm text-danger">{jobError}</p>}
                            <FormField
                                id="full_name"
                                label="Full name"
                                value={form.data.full_name}
                                onChange={(event) => form.setData('full_name', event.currentTarget.value)}
                            />
                            {form.errors.full_name && <p className="text-sm text-danger">{form.errors.full_name}</p>}
                            <FormField
                                id="mobile"
                                label="Mobile number"
                                value={form.data.mobile}
                                onChange={(event) => form.setData('mobile', event.currentTarget.value)}
                            />
                            {form.errors.mobile && <p className="text-sm text-danger">{form.errors.mobile}</p>}
                            <FormField
                                id="email"
                                label="Email"
                                type="email"
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.currentTarget.value)}
                            />
                            {form.errors.email && <p className="text-sm text-danger">{form.errors.email}</p>}
                            <FormField
                                id="current_location"
                                label="Current location"
                                value={form.data.current_location}
                                onChange={(event) => form.setData('current_location', event.currentTarget.value)}
                            />
                            {form.errors.current_location && (
                                <p className="text-sm text-danger">{form.errors.current_location}</p>
                            )}
                            <FormField
                                id="experience"
                                label="Experience"
                                value={form.data.experience}
                                onChange={(event) => form.setData('experience', event.currentTarget.value)}
                            />
                            {form.errors.experience && <p className="text-sm text-danger">{form.errors.experience}</p>}
                            <FormField
                                id="qualification"
                                label="Qualification"
                                value={form.data.qualification}
                                onChange={(event) => form.setData('qualification', event.currentTarget.value)}
                            />
                            {form.errors.qualification && (
                                <p className="text-sm text-danger">{form.errors.qualification}</p>
                            )}
                            <div>
                                <label htmlFor="resume" className="mb-1 block text-sm font-medium text-ink">
                                    Resume (PDF, DOC, or DOCX, max 5 MB)
                                </label>
                                <input
                                    id="resume"
                                    type="file"
                                    accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                    className="w-full text-sm text-ink"
                                    onChange={(event) => form.setData('resume', event.currentTarget.files?.[0] ?? null)}
                                />
                                {form.errors.resume && <p className="mt-1 text-sm text-danger">{form.errors.resume}</p>}
                            </div>
                            <FormField
                                id="additional_information"
                                label="Additional information"
                                as="textarea"
                                value={form.data.additional_information}
                                onChange={(event) => form.setData('additional_information', event.currentTarget.value)}
                            />
                            {form.errors.additional_information && (
                                <p className="text-sm text-danger">{form.errors.additional_information}</p>
                            )}
                            <label className="flex items-start gap-2 text-sm text-ink">
                                <input
                                    type="checkbox"
                                    checked={form.data.consent}
                                    onChange={(event) => form.setData('consent', event.currentTarget.checked)}
                                />
                                <span>
                                    I confirm that the information is accurate and that OurNear Talent may process this
                                    application for recruitment purposes.
                                </span>
                            </label>
                            {form.errors.consent && <p className="text-sm text-danger">{form.errors.consent}</p>}
                            <HoneypotField value={form.data.fax} onChange={(value) => form.setData('fax', value)} />
                            <TurnstileField onToken={(token) => form.setData('cf-turnstile-response', token)} />
                            {form.errors['cf-turnstile-response'] && (
                                <p className="text-sm text-danger">{form.errors['cf-turnstile-response']}</p>
                            )}
                            <Button type="submit">{form.processing ? 'Submitting…' : 'Submit application'}</Button>
                        </form>
                    )}
                    <Link href={`/jobs/${job.slug}`} className="mt-4 inline-block text-sm font-medium text-brand">
                        Back to job details
                    </Link>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
