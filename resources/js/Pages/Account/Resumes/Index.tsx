import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useRef, useState, type FormEvent } from 'react';
import CandidateSupportingDocumentsSection from '@/Components/Account/CandidateSupportingDocumentsSection';
import Button from '@/Components/atoms/Button';
import CandidateUnifiedProfileNav from '@/Components/Profile/CandidateUnifiedProfileNav';
import ResumeReadinessPanel from '@/Components/Resume/ResumeReadinessPanel';
import { isHireResumeReady } from '@/Components/Resume/resumeReadiness';
import AccountLayout from '@/Layouts/AccountLayout';
import { accountLayout } from '@/design-system/classNames';
import type {
    AccountCompletionItem,
    AccountDocument,
    AccountFileOption,
    AccountResume,
} from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type ResumesProps = {
    resumes: AccountResume[];
    visibilities: AccountFileOption[];
    completion_items?: AccountCompletionItem[];
    profile_completion?: number;
    documents?: AccountDocument[];
    document_types?: AccountFileOption[];
    document_visibilities?: AccountFileOption[];
    public_profile_url?: string | null;
    public_profile_share_enabled?: boolean;
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
    documents = [],
    document_types = [],
    document_visibilities = [],
    public_profile_url = null,
    public_profile_share_enabled = false,
}: ResumesProps) {
    const page = usePage<SharedPageProps>();
    const flash = page.props.flash;
    const pageErrors = page.props.errors as Record<string, string | undefined> | undefined;
    const current = activeResume(resumes);
    const uploadInputRef = useRef<HTMLInputElement>(null);
    const replaceInputRef = useRef<HTMLInputElement>(null);
    const [replaceFileName, setReplaceFileName] = useState<string | null>(null);
    const [replacing, setReplacing] = useState(false);
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
            preserveScroll: true,
            onSuccess: () => {
                form.reset('file');
                if (uploadInputRef.current) {
                    uploadInputRef.current.value = '';
                }
            },
        });
    };

    const replace = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!current || replacing) {
            return;
        }
        const data = new FormData(event.currentTarget);
        const file = data.get('file');
        if (!(file instanceof File) || file.size === 0) {
            return;
        }
        setReplacing(true);
        router.post(`/account/resumes/${current.id}/replace`, data, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                if (replaceInputRef.current) {
                    replaceInputRef.current.value = '';
                }
                setReplaceFileName(null);
            },
            onError: () => {
                if (replaceInputRef.current) {
                    replaceInputRef.current.value = '';
                }
                setReplaceFileName(null);
            },
            onFinish: () => setReplacing(false),
        });
    };

    return (
        <AccountLayout
            title="Hire Resume"
            description="Profile-based Hire Resume, one uploaded My Resume for applications, and private supporting documents."
            breadcrumbs={[
                { label: 'Personal Dashboard', href: '/account' },
                { label: 'Hire Resume' },
            ]}
        >
            <CandidateUnifiedProfileNav
                active="resumes"
                publicProfileUrl={public_profile_url}
                shareEnabled={public_profile_share_enabled}
            />

            {flash.success ? (
                <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success" role="status">
                    {flash.success}
                </p>
            ) : null}

            <div className={`${accountLayout.profileWorkspace} flex flex-col gap-4`}>
                {completion_items.length > 0 ? (
                    <ResumeReadinessPanel items={completion_items} completion={profile_completion} />
                ) : null}

                <article className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <h2 className="text-lg font-semibold text-ink sm:text-xl">Hire Resume</h2>
                    <p className="mt-1 text-sm font-medium text-brand">Profile-based resume</p>
                    <p className="mt-2 text-sm text-muted">
                        Generated from your latest saved profile data. Preview when you are ready, then print or save as PDF.
                        This does not replace My Resume or application snapshots.
                    </p>
                    <p className="mt-3 text-sm">
                        <span className="font-medium text-ink">Status: </span>
                        <span className={hireReady ? 'font-semibold text-success' : 'font-semibold text-accent'}>
                            {hireReady ? 'Ready to preview' : 'Add profile details to improve the preview'}
                        </span>
                    </p>
                    <div className="mt-4 flex flex-wrap gap-2">
                        <Button surface="public" href="/account/resumes/builder" variant="outline" className="min-h-11">
                            Preview
                        </Button>
                        <Button
                            surface="public"
                            href="/account/resumes/builder/download"
                            nativeAnchor
                            variant="primary"
                            className="min-h-11"
                        >
                            Download Hire Resume
                        </Button>
                        <Button surface="public" href="/account/profile#professional" variant="outline" className="min-h-11">
                            Update profile
                        </Button>
                    </div>
                </article>

                <article className="rounded-xl border border-line bg-white p-4 sm:p-5">
                    <p className="text-xs font-semibold uppercase tracking-wide text-brand">My Resume</p>
                    <h2 className="mt-1 text-base font-semibold text-ink">Uploaded resume for applications</h2>
                    <p className="mt-2 text-sm text-muted">
                        One current file used when you apply. Replacing updates this library file only; submitted application
                        copies stay unchanged.
                    </p>
                    {current ? (
                        <>
                            <dl className="mt-4 space-y-2 text-sm">
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
                                {current.status_label ? (
                                    <div>
                                        <dt className="text-muted">Status</dt>
                                        <dd className="text-ink">{current.status_label}</dd>
                                    </div>
                                ) : null}
                            </dl>
                            <div className="mt-4 flex flex-wrap gap-2">
                                <Button surface="public" href={current.view_url} variant="primary" className="min-h-11">
                                    View
                                </Button>
                                <Button surface="public" href={current.download_url} nativeAnchor variant="outline" className="min-h-11">
                                    Download
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
                                {pageErrors?.file ? (
                                    <p className="text-sm text-danger" role="alert">
                                        {pageErrors.file}
                                    </p>
                                ) : null}
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        surface="public"
                                        type="button"
                                        variant="outline"
                                        className="min-h-11"
                                        onClick={() => replaceInputRef.current?.click()}
                                    >
                                        Choose file
                                    </Button>
                                    <Button
                                        surface="public"
                                        type="submit"
                                        variant="primary"
                                        className="min-h-11"
                                        loading={replacing}
                                        disabled={!replaceFileName || replacing}
                                    >
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
                                className="w-full rounded-md border border-line px-3 py-2 text-sm"
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
                                    surface="public"
                                    type="button"
                                    variant="outline"
                                    className="min-h-11"
                                    onClick={() => uploadInputRef.current?.click()}
                                >
                                    Choose file
                                </Button>
                                <Button
                                    surface="public"
                                    type="submit"
                                    variant="primary"
                                    loading={form.processing}
                                    disabled={!form.data.file || form.processing}
                                    className="min-h-11"
                                >
                                    Upload resume
                                </Button>
                            </div>
                        </form>
                    )}
                </article>

                {document_types.length > 0 ? (
                    <CandidateSupportingDocumentsSection
                        documents={documents}
                        types={document_types}
                        visibilities={document_visibilities.length > 0 ? document_visibilities : visibilities}
                    />
                ) : null}
            </div>

            <p className={`${accountLayout.profileWorkspace} mt-6 text-sm text-muted`}>
                Need the full documents workspace?{' '}
                <Link href="/account/documents" className="font-medium text-brand hover:underline">
                    Open documents
                </Link>
            </p>
        </AccountLayout>
    );
}
