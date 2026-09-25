import { Link, useForm, usePage } from '@inertiajs/react';
import { useRef, type FormEvent } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import Button from '@/Components/Public/Button';
import PageContainer from '@/Components/Public/PageContainer';
import type { PublicJobDetail } from '@/types/jobs';
import type { SharedPageProps } from '@/types/page';
import type { SiteContent } from '@/types/public';

type JobApplyProps = {
    content: SiteContent;
    job: PublicJobDetail;
    needs_resume?: boolean;
};

export default function JobApply({ content, job, needs_resume = true }: JobApplyProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const fileInputRef = useRef<HTMLInputElement>(null);
    const form = useForm({
        file: null as File | null,
        title: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (form.processing) {
            return;
        }

        if (!form.data.file) {
            fileInputRef.current?.click();
            return;
        }

        form.post(`/jobs/${job.slug}/apply/resume`, { forceFormData: true });
    };

    return (
        <PublicLayout title={`Upload resume · ${job.title}`}>
            <section className="bg-brand py-14 text-white">
                <PageContainer>
                    <p className="text-sm font-medium text-accent">{content.tagline}</p>
                    <h1 className="mt-3 text-4xl font-semibold">Upload Resume to Apply</h1>
                    <p className="mt-3 max-w-2xl text-white/80">
                        {job.title} · {job.project} · {job.location}
                    </p>
                </PageContainer>
            </section>
            <section className="py-12">
                <PageContainer className="max-w-xl">
                    {flash.success ? (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.success}
                        </p>
                    ) : null}
                    {needs_resume ? (
                        <form onSubmit={submit} className="space-y-4 rounded-xl border border-line bg-white p-6">
                            <p className="text-sm text-muted">
                                Upload a resume to your Resume Library. After it is saved, return to the job and click
                                Apply Now. This is not an application form.
                            </p>
                            <div>
                                <label htmlFor="file" className="mb-1 block text-sm font-medium text-ink">
                                    Resume (PDF, DOC, or DOCX, max 5 MB)
                                </label>
                                <input
                                    ref={fileInputRef}
                                    id="file"
                                    type="file"
                                    required
                                    accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                    className="w-full text-sm text-ink"
                                    onChange={(event) => form.setData('file', event.currentTarget.files?.[0] ?? null)}
                                />
                                {form.errors.file && <p className="mt-1 text-sm text-danger">{form.errors.file}</p>}
                            </div>
                            <Button
                                type={form.data.file ? 'submit' : 'button'}
                                onClick={form.data.file ? undefined : () => fileInputRef.current?.click()}
                            >
                                {form.processing ? 'Uploading…' : 'Upload Resume'}
                            </Button>
                            {!form.data.file ? (
                                <p className="text-xs text-muted">
                                    Select a file above, or press Upload Resume to open the file picker.
                                </p>
                            ) : null}
                            <p className="text-sm text-muted">
                                To set a primary resume later, open{' '}
                                <Link href="/account/resumes" className="font-medium text-brand">
                                    Resume Library
                                </Link>
                                .
                            </p>
                        </form>
                    ) : (
                        <p className="text-sm text-muted">This role is ready for a one-click application.</p>
                    )}
                    <Link href={`/jobs/${job.slug}`} className="mt-6 inline-block text-sm font-medium text-brand">
                        Back to job
                    </Link>
                </PageContainer>
            </section>
        </PublicLayout>
    );
}
