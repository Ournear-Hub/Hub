import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import DetailHeader from '@/Components/organisms/DetailHeader';
import AdminLayout from '@/Layouts/AdminLayout';

type ImportItem = {
    filename: string;
    status: string;
    email_status: string;
    message: string | null;
    extracted_name: string | null;
    extracted_email: string | null;
    ournear_id: string | null;
    profile_url: string | null;
};

type ImportBatch = {
    id: number;
    created_at: string | null;
    status: string;
    total_files: number;
    successful: number;
    matched: number;
    needs_review: number;
    failed: number;
    emails_queued: number;
    ready: number;
    items: ImportItem[];
};

export default function ResumeImport({ can_import, batches }: { can_import: boolean; batches: ImportBatch[] }) {
    const form = useForm<{ files: File[] }>({ files: [] });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/admin/candidates/imports', { forceFormData: true });
    };

    return (
        <AdminLayout
            title="Bulk resume import"
            breadcrumbs={[{ label: 'Admin', href: '/admin' }, { label: 'Candidates', href: '/admin/candidates' }, { label: 'Bulk resume import' }]}
        >
            <div className="space-y-6">
                <DetailHeader
                    headingLevel={2}
                    title="Import and review resumes"
                    description="Upload PDF, DOC, DOCX, or a ZIP of those files. The system extracts contact details for review, then you confirm the batch to create or match candidates. Existing profile fields are not overwritten."
                />

                {can_import ? (
                    <form onSubmit={submit} className="rounded-xl border border-line bg-white p-5">
                        <label className="block text-sm font-medium text-ink" htmlFor="files">
                            Resume files
                        </label>
                        <input
                            id="files"
                            type="file"
                            multiple
                            accept=".pdf,.doc,.docx,.zip,application/pdf,application/zip"
                            className="mt-2 block w-full text-sm"
                            onChange={(event) => form.setData('files', Array.from(event.target.files ?? []))}
                        />
                        {form.errors.files ? <p className="mt-2 text-sm text-danger">{form.errors.files}</p> : null}
                        <button type="submit" disabled={form.processing || form.data.files.length === 0} className="mt-4 rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled">
                            Extract for review
                        </button>
                    </form>
                ) : (
                    <p className="text-sm text-muted">You can review import history. Uploading requires candidate management permission.</p>
                )}

                {batches.length === 0 ? (
                    <p className="rounded-xl border border-dashed border-line bg-white px-4 py-6 text-sm text-muted">No resume imports yet.</p>
                ) : (
                    batches.map((batch) => (
                        <section key={batch.id} className="rounded-xl border border-line bg-white p-5">
                            <h2 className="text-base font-semibold text-ink">Import {batch.created_at}</h2>
                            <p className="mt-1 text-sm text-muted">
                                Status {batch.status} · Total {batch.total_files} · Ready {batch.ready} · Created {batch.successful} · Matched {batch.matched} · Needs review {batch.needs_review} · Failed {batch.failed}
                            </p>
                            {can_import && batch.status === 'awaiting_confirmation' && batch.ready + batch.matched > 0 ? (
                                <button
                                    type="button"
                                    className="mt-3 rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white"
                                    onClick={() => router.post(`/admin/candidates/imports/${batch.id}/confirm`)}
                                >
                                    Confirm import
                                </button>
                            ) : null}
                            <ul className="mt-3 divide-y divide-line text-sm">
                                {batch.items.map((item) => (
                                    <li key={`${batch.id}-${item.filename}`} className="py-2">
                                        <p className="font-medium text-ink">{item.filename}</p>
                                        <p className="text-xs text-muted">
                                            {item.status} · email {item.email_status}
                                            {item.extracted_name ? ` · ${item.extracted_name}` : ''}
                                            {item.extracted_email ? ` · ${item.extracted_email}` : ''}
                                            {item.ournear_id ? ` · ${item.ournear_id}` : ''}
                                            {item.message ? ` · ${item.message}` : ''}
                                        </p>
                                        {item.profile_url && item.ournear_id ? (
                                            <a href={item.profile_url} className="text-xs font-medium text-brand hover:underline">
                                                {item.ournear_id}
                                            </a>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ))
                )}
            </div>
        </AdminLayout>
    );
}
