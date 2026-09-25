import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PageContainer from '@/Components/Public/PageContainer';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountFileOption, AccountResume } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type ResumesProps = {
    resumes: AccountResume[];
    visibilities: AccountFileOption[];
};

export default function ResumesIndex({ resumes, visibilities }: ResumesProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const form = useForm({
        file: null as File | null,
        title: '',
        visibility: 'private',
        is_primary: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/account/resumes', {
            forceFormData: true,
            onSuccess: () => form.reset(),
        });
    };

    const replace = (resume: AccountResume, event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        router.post(`/account/resumes/${resume.id}/replace`, data, { forceFormData: true });
    };

    return (
        <AccountLayout title="Resumes">
            
                    {flash.success ? (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.success}
                        </p>
                    ) : null}
                    <div className="mb-6 flex flex-wrap gap-3 text-sm">
                        <Link href="/account/resumes/builder" className="font-medium text-brand">
                            Open resume builder
                        </Link>
                        <Link href="/account/documents" className="font-medium text-brand">
                            Documents
                        </Link>
                    </div>
                    <form onSubmit={submit} className="mb-8 space-y-3 rounded-xl border border-line bg-white p-6">
                        <h2 className="text-lg font-semibold text-ink">Upload resume</h2>
                        <label className="block text-sm font-medium text-ink" htmlFor="resume-title">
                            Title
                        </label>
                        <input
                            id="resume-title"
                            className="w-full rounded-md border border-line px-3 py-2 text-sm"
                            value={form.data.title}
                            onChange={(event) => form.setData('title', event.target.value)}
                        />
                        <label className="block text-sm font-medium text-ink" htmlFor="resume-file">
                            File (PDF, DOC, or DOCX, max 5 MB)
                        </label>
                        <input
                            id="resume-file"
                            type="file"
                            required
                            accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            onChange={(event) => form.setData('file', event.target.files?.[0] ?? null)}
                        />
                        {form.errors.file ? <p className="text-sm text-danger">{form.errors.file}</p> : null}
                        <label className="block text-sm font-medium text-ink" htmlFor="resume-visibility">
                            Visibility
                        </label>
                        <select
                            id="resume-visibility"
                            className="w-full rounded-md border border-line px-3 py-2 text-sm"
                            value={form.data.visibility}
                            onChange={(event) => form.setData('visibility', event.target.value)}
                        >
                            {visibilities.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        <label className="flex items-center gap-2 text-sm text-ink">
                            <input
                                type="checkbox"
                                checked={form.data.is_primary}
                                onChange={(event) => form.setData('is_primary', event.target.checked)}
                            />
                            Set as primary resume
                        </label>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white disabled:bg-disabled"
                        >
                            Upload resume
                        </button>
                    </form>
                    {resumes.length === 0 ? (
                        <p className="text-sm text-muted">No library resumes uploaded yet.</p>
                    ) : (
                        <ul className="space-y-4">
                            {resumes.map((resume) => (
                                <li key={resume.id} className="rounded-xl border border-line bg-white p-6">
                                    <p className="text-lg font-semibold text-ink">{resume.title}</p>
                                    <p className="mt-1 text-sm text-muted">
                                        {resume.original_filename} · {resume.status_label} · {resume.visibility_label}
                                        {resume.is_primary ? ' · Primary' : ''}
                                    </p>
                                    <div className="mt-3 flex flex-wrap gap-3 text-sm">
                                        <a href={resume.view_url} className="font-medium text-brand" target="_blank" rel="noreferrer">
                                            View
                                        </a>
                                        <a href={resume.download_url} className="font-medium text-brand">
                                            Download
                                        </a>
                                        {!resume.is_primary && resume.status === 'active' ? (
                                            <button
                                                type="button"
                                                className="font-medium text-brand"
                                                onClick={() => router.patch(`/account/resumes/${resume.id}/primary`)}
                                            >
                                                Set primary
                                            </button>
                                        ) : null}
                                        {resume.status === 'active' ? (
                                            <button
                                                type="button"
                                                className="font-medium text-muted"
                                                onClick={() => router.patch(`/account/resumes/${resume.id}/archive`)}
                                            >
                                                Archive
                                            </button>
                                        ) : null}
                                        <button
                                            type="button"
                                            className="font-medium text-danger"
                                            onClick={() => {
                                                if (window.confirm('Delete this library resume? Application snapshots stay intact.')) {
                                                    router.delete(`/account/resumes/${resume.id}`);
                                                }
                                            }}
                                        >
                                            Delete
                                        </button>
                                    </div>
                                    <form className="mt-4 space-y-2" onSubmit={(event) => replace(resume, event)}>
                                        <label className="block text-sm font-medium text-ink" htmlFor={`replace-${resume.id}`}>
                                            Replace file
                                        </label>
                                        <input
                                            id={`replace-${resume.id}`}
                                            name="file"
                                            type="file"
                                            required
                                            accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                        />
                                        <button type="submit" className="rounded-md bg-brand px-3 py-1.5 text-sm font-semibold text-white">
                                            Replace
                                        </button>
                                    </form>
                                </li>
                            ))}
                        </ul>
                    )}
                </AccountLayout>
    );
}
