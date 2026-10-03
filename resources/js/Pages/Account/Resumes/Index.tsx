import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useRef, useState, type FormEvent } from 'react';
import Button from '@/Components/Public/Button';
import ResumeReadinessPanel from '@/Components/Resume/ResumeReadinessPanel';
import { isHireResumeReady } from '@/Components/Resume/resumeReadiness';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountCompletionItem, AccountFileOption, AccountResume } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type ResumesProps = {
    resumes: AccountResume[];
    visibilities: AccountFileOption[];
    completion_items?: AccountCompletionItem[];
    profile_completion?: number;
};

function activeResume(resumes: AccountResume[]): AccountResume | null {
    const active = resumes.filter((resume) => resume.status === 'active');
    return active.find((resume) => resume.is_primary) ?? active[0] ?? null;
}

export default function ResumesIndex({
    resumes,
    visibilities,
    completion_items = [],
    profile_completion = 0,
}: ResumesProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const current = activeResume(resumes);
    const uploadInputRef = useRef<HTMLInputElement>(null);
    const replaceInputRef = useRef<HTMLInputElement>(null);
    const [replaceFileName, setReplaceFileName] = useState<string | null>(null);
    const hireReady = isHireResumeReady(completion_items);

    const form = useForm({
        file: null as File | null,
        title: current?.title ?? 'My Resume',
        visibility: current?.visibility ?? visibilities[0]?.value ?? 'private',
        is_primary: true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/account/resumes', {
            forceFormData: true,
            onSuccess: () => form.reset('file'),
        });
    };

    const replace = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!current) {
            return;
        }
        const data = new FormData(event.currentTarget);
        router.post(`/account/resumes/${current.id}/replace`, data, {
            forceFormData: true,
            onSuccess: () => {
                if (replaceInputRef.current) {
                    replaceInputRef.current.value = '';
                }
            },
        });
    };

    return (
        <AccountLayout
            title="Resume"
            description="OurNear Hire Resume from your profile, and one uploaded My Resume for applications."
            breadcrumbs={[
                { label: 'Account', href: '/account' },
                { label: 'Resume' },
            ]}
        >
            {flash.success ? (
                <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success" role="status">
                    {flash.success}
                </p>
            ) : null}

            <header className="mb-6 max-w-3xl">
                <h1 className="text-xl font-semibold text-ink sm:text-2xl">Your resumes</h1>
                <p className="mt-1 text-sm text-muted">
                    Applications use My Resume. Hire Resume is a printable profile summary — it does not replace uploads or
                    application snapshots.
                </p>
            </header>

            {completion_items.length > 0 ? (
                <div className="mb-6 max-w-3xl">
                    <ResumeReadinessPanel items={completion_items} completion={profile_completion} />
                </div>
            ) : null}

            <div className="grid min-w-0 max-w-3xl gap-4">
                <article className="rounded-xl border border-line bg-white p-4 sm:p-6">
                    <p className="text-xs font-semibold uppercase tracking-wide text-brand">OurNear Resume</p>
                    <h2 className="mt-1 text-lg font-semibold text-ink">Hire Resume</h2>
                    <p className="mt-2 text-sm text-muted">Generated from your OurNear profile. Update profile data, then preview and print.</p>
                    <p className="mt-2 text-sm font-medium text-ink">
                        Status:{' '}
                        <span className={hireReady ? 'text-success' : 'text-accent'}>{hireReady ? 'Ready to preview' : 'Needs profile details'}</span>
                    </p>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Button href="/account/resumes/builder" variant="secondary" className="min-h-11">
                            {hireReady ? 'Preview & download' : 'Build / update'}
                        </Button>
                        <Button href="/account/profile" variant="outline" className="min-h-11">
                            Complete profile
                        </Button>
                    </div>
                </article>

                <article className="rounded-xl border border-line bg-white p-4 sm:p-6">
                    <p className="text-xs font-semibold uppercase tracking-wide text-brand">My Resume</p>
                    <h2 className="mt-1 text-lg font-semibold text-ink">Uploaded personal resume</h2>
                    <p className="mt-2 text-sm text-muted">One current file for job applications. Replacing updates this file only.</p>
                    {current ? (
                        <>
                            <dl className="mt-4 space-y-1 text-sm">
                                <div>
                                    <dt className="text-muted">File</dt>
                                    <dd className="font-medium text-ink">{current.original_filename || current.title}</dd>
                                </div>
                                {current.created_at ? (
                                    <div>
                                        <dt className="text-muted">Uploaded</dt>
                                        <dd className="text-ink">{current.created_at}</dd>
                                    </div>
                                ) : null}
                            </dl>
                            <div className="mt-4 flex flex-wrap gap-2">
                                <Button href={current.download_url} variant="outline" className="min-h-11">
                                    Download
                                </Button>
                                <Button href={current.view_url} variant="ghost" className="min-h-11">
                                    Open
                                </Button>
                            </div>
                            <form className="mt-6 space-y-3 border-t border-line pt-4" onSubmit={replace}>
                                <label htmlFor="replace-file" className="block text-sm font-medium text-ink">
                                    Replace resume (PDF, DOC, or DOCX — max 5 MB)
                                </label>
                                <input
                                    ref={replaceInputRef}
                                    id="replace-file"
                                    name="file"
                                    type="file"
                                    className="sr-only"
                                    accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                    onChange={(event) => setReplaceFileName(event.target.files?.[0]?.name ?? null)}
                                />
                                <p className="text-sm text-muted">
                                    {replaceFileName ?? 'Choose a new file to replace your current resume.'}
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="min-h-11"
                                        onClick={() => replaceInputRef.current?.click()}
                                    >
                                        Choose file
                                    </Button>
                                    <Button type="submit" variant="primary" className="min-h-11">
                                        Replace resume
                                    </Button>
                                </div>
                            </form>
                        </>
                    ) : (
                        <form onSubmit={submit} className="mt-4 space-y-3">
                            <input
                                ref={uploadInputRef}
                                id="resume-file"
                                name="file"
                                type="file"
                                className="sr-only"
                                accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                onChange={(event) => form.setData('file', event.target.files?.[0] ?? null)}
                            />
                            <label htmlFor="resume-title" className="block text-sm font-medium text-ink">
                                Label (optional)
                            </label>
                            <input
                                id="resume-title"
                                className="w-full max-w-md rounded-md border border-line px-3 py-2 text-sm"
                                value={form.data.title}
                                onChange={(event) => form.setData('title', event.target.value)}
                            />
                            <p className="text-sm text-muted">
                                {form.data.file ? (
                                    <span className="font-medium text-ink">{form.data.file.name}</span>
                                ) : (
                                    'PDF, DOC, or DOCX — max 5 MB'
                                )}
                            </p>
                            {form.errors.file ? (
                                <p className="text-sm text-danger" role="alert">
                                    {form.errors.file}
                                </p>
                            ) : null}
                            <div className="flex flex-wrap gap-2 pb-[calc(0.25rem+env(safe-area-inset-bottom,0px))]">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="min-h-11"
                                    onClick={() => uploadInputRef.current?.click()}
                                >
                                    Choose file
                                </Button>
                                <Button type="submit" variant="primary" loading={form.processing} disabled={!form.data.file} className="min-h-11">
                                    Upload resume
                                </Button>
                            </div>
                        </form>
                    )}
                </article>
            </div>

            <p className="mt-8 max-w-3xl text-sm text-muted">
                <Link href="/account/documents" className="font-medium text-brand hover:underline">
                    Private documents
                </Link>{' '}
                are separate and are not sent with applications automatically.
            </p>
        </AccountLayout>
    );
}
