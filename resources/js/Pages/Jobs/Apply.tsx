import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useRef, useState, type FormEvent } from 'react';
import Button from '@/Components/Public/Button';
import PageContainer from '@/Components/Public/PageContainer';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/Components/Public/PublicPageHero';
import Alert from '@/Components/UI/Alert';
import Card from '@/Components/UI/Card';
import SectionHeader from '@/Components/UI/SectionHeader';
import type { PublicJobDetail } from '@/types/jobs';
import type { SharedPageProps } from '@/types/page';

type ResumeSummary = {
    title: string;
    filename: string;
};

type ProfileGap = {
    key: string;
    label: string;
};

type ApplyProps = {
    job: PublicJobDetail;
    needs_resume?: boolean;
    resume?: ResumeSummary | null;
    profile_gaps?: ProfileGap[];
};

const profileGapHref: Record<string, string> = {
    name: '/account/profile',
    email: '/account/settings',
    username: '/account/settings',
    photo: '/account/profile',
    location: '/account/profile#professional',
    mobile: '/account/settings',
    bio: '/account/profile',
    email_verified: '/account/settings',
    current_role: '/account/profile#professional',
    experience: '/account/profile#professional',
    industry_or_function: '/account/profile#professional',
    skills: '/account/profile#skills',
    employment_history: '/account/profile#experience',
};

export default function JobApply({ job, needs_resume = true, resume = null, profile_gaps = [] }: ApplyProps) {
    const { errors, flash } = usePage<SharedPageProps>().props;
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [submitting, setSubmitting] = useState(false);
    const form = useForm<{ file: File | null; title: string }>({
        file: null,
        title: 'My Resume',
    });

    const upload = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/jobs/${job.slug}/apply/resume`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset('file'),
        });
    };

    const submitApplication = () => {
        if (submitting || needs_resume) {
            return;
        }
        setSubmitting(true);
        router.post(
            `/jobs/${job.slug}/apply`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setSubmitting(false),
            },
        );
    };

    const locationLine = [job.location, job.employment_type].filter(Boolean).join(' · ');
    const visibleGaps = profile_gaps.filter((gap) => !gap.key.startsWith('email_verified')).slice(0, 4);

    return (
        <PublicLayout title={`Apply · ${job.title}`}>
            <PublicPageHero
                eyebrow="Application"
                title="Apply for this role"
                description={`${job.title} · ${job.company_name}`}
                breadcrumbs={[
                    { label: 'Home', href: '/' },
                    { label: 'Jobs', href: '/jobs' },
                    { label: job.title, href: `/jobs/${job.slug}` },
                    { label: 'Apply' },
                ]}
            />

            <section className="py-6 md:py-8">
                <PageContainer className="min-w-0 max-w-2xl">
                    {flash.success ? (
                        <Alert tone="success" title="Resume ready" className="mb-4">
                            {flash.success}
                        </Alert>
                    ) : null}

                    {errors.job ? (
                        <Alert tone="error" title="Unable to apply" className="mb-4">
                            {Array.isArray(errors.job) ? errors.job[0] : errors.job}
                        </Alert>
                    ) : null}

                    {errors.resume ? (
                        <Alert tone="error" title="Resume required" className="mb-4">
                            {Array.isArray(errors.resume) ? errors.resume[0] : errors.resume}
                        </Alert>
                    ) : null}

                    <Card as="article" density="standard" className="border border-line bg-white">
                        <p className="text-xs font-semibold uppercase tracking-wide text-muted">Job</p>
                        <h1 className="mt-1 text-xl font-semibold text-ink">{job.title}</h1>
                        <p className="mt-1 text-sm font-medium text-brand">{job.company_name}</p>
                        {locationLine ? <p className="mt-0.5 text-sm text-muted">{locationLine}</p> : null}
                        <Link href={`/jobs/${job.slug}`} className="mt-3 inline-block text-sm font-medium text-brand hover:underline">
                            View full job description
                        </Link>
                    </Card>

                    <div className="mt-8 space-y-6">
                        <section aria-labelledby="apply-resume-heading">
                            <SectionHeader
                                headingLevel="h2"
                                eyebrow="Step 1"
                                title="Resume"
                                description="Applications use your current uploaded personal resume. A separate snapshot is stored when you submit — your library file stays unchanged."
                            />
                            {needs_resume ? (
                                <Card as="div" density="standard" className="mt-4 border border-line bg-white">
                                    <p className="text-sm text-muted">
                                        Upload one PDF, DOC, or DOCX (max 5 MB). Replacing your resume updates your single current file.
                                    </p>
                                    <form onSubmit={upload} className="mt-4 space-y-4">
                                        <input
                                            ref={fileInputRef}
                                            id="apply-resume-file"
                                            name="file"
                                            type="file"
                                            className="sr-only"
                                            accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                            onChange={(event) => form.setData('file', event.target.files?.[0] ?? null)}
                                        />
                                        <div>
                                            <label htmlFor="apply-resume-file" className="mb-2 block text-sm font-medium text-ink">
                                                Resume file
                                            </label>
                                            <p className="text-sm text-muted">
                                                {form.data.file ? (
                                                    <span className="font-medium text-ink">{form.data.file.name}</span>
                                                ) : (
                                                    'No file selected yet.'
                                                )}
                                            </p>
                                            {form.errors.file ? (
                                                <p className="mt-1 text-sm text-danger" role="alert">
                                                    {form.errors.file}
                                                </p>
                                            ) : null}
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type={form.data.file ? 'submit' : 'button'}
                                                variant="secondary"
                                                loading={form.processing}
                                                disabled={form.processing}
                                                onClick={() => {
                                                    if (!form.data.file) {
                                                        fileInputRef.current?.click();
                                                    }
                                                }}
                                            >
                                                {form.processing
                                                    ? 'Uploading…'
                                                    : form.data.file
                                                      ? 'Upload resume'
                                                      : 'Choose file'}
                                            </Button>
                                            <Button href={`/jobs/${job.slug}`} variant="outline">
                                                Back to job
                                            </Button>
                                        </div>
                                    </form>
                                    <p className="mt-4 text-sm text-muted">
                                        This is not your{' '}
                                        <Link href="/account/resumes/builder" className="font-medium text-brand hover:underline">
                                            Hire Resume
                                        </Link>{' '}
                                        (OurNear-generated preview from your profile).
                                    </p>
                                </Card>
                            ) : (
                                <Card as="div" density="standard" className="mt-4 border border-line bg-white">
                                    <p className="text-sm font-medium text-ink">{resume?.title ?? 'My Resume'}</p>
                                    {resume?.filename ? (
                                        <p className="mt-1 text-sm text-muted">{resume.filename}</p>
                                    ) : null}
                                    <p className="mt-2 text-sm text-success">Ready to include with your application.</p>
                                    <div className="mt-4">
                                        <Button href="/account/resumes" variant="ghost" className="text-sm">
                                            Replace in My Resume
                                        </Button>
                                    </div>
                                </Card>
                            )}
                        </section>

                        {visibleGaps.length > 0 ? (
                            <section aria-labelledby="apply-profile-heading">
                                <SectionHeader
                                    headingLevel="h2"
                                    eyebrow="Optional"
                                    title="Strengthen your profile"
                                    description="You can submit now. Completing these sections helps recruiters review you faster."
                                />
                                <ul className="mt-3 space-y-2">
                                    {visibleGaps.map((gap) => (
                                        <li key={gap.key}>
                                            <Link
                                                href={profileGapHref[gap.key] ?? '/account/profile'}
                                                className="flex items-center justify-between rounded-lg border border-line bg-white px-4 py-3 text-sm font-medium text-brand hover:bg-surface"
                                            >
                                                {gap.label}
                                                <span className="text-xs text-muted">Complete</span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ) : null}

                        {!needs_resume ? (
                            <section aria-labelledby="apply-submit-heading">
                                <SectionHeader
                                    headingLevel="h2"
                                    eyebrow="Step 2"
                                    title="Submit"
                                    description="We will attach your current resume and profile details to this application."
                                />
                                <div className="mt-4 flex flex-wrap gap-2 pb-[calc(1rem+env(safe-area-inset-bottom,0px))] sm:pb-0">
                                    <Button
                                        type="button"
                                        variant="primary"
                                        loading={submitting}
                                        disabled={submitting}
                                        onClick={submitApplication}
                                    >
                                        {submitting ? 'Submitting…' : 'Submit application'}
                                    </Button>
                                    <Button href={`/jobs/${job.slug}`} variant="outline">
                                        Cancel
                                    </Button>
                                </div>
                            </section>
                        ) : null}
                    </div>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
