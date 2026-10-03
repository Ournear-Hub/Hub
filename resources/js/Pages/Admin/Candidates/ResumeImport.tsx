import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';

type ImportItem = {
    filename: string;
    status: string;
    email_status: string;
    message: string | null;
    ournear_id: string | null;
    profile_url: string | null;
};

type ImportBatch = {
    id: number;
    created_at: string | null;
    total_files: number;
    successful: number;
    matched: number;
    needs_review: number;
    failed: number;
    emails_queued: number;
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
                <div>
                    <h1 className="text-2xl font-semibold text-ink">Bulk resume import</h1>
                    <p className="mt-1 max-w-3xl text-sm text-muted">
                        Upload PDF, DOC, DOCX, or a ZIP of those files. Existing email addresses are matched and not overwritten. New people receive a password-setup email, not a password.
                    </p>
                </div>

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
                            Upload
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
                                Total {batch.total_files} · Created {batch.successful} · Matched {batch.matched} · Needs review {batch.needs_review} · Failed {batch.failed} · Emails queued {batch.emails_queued}
                            </p>
                            <ul className="mt-3 divide-y divide-line text-sm">
                                {batch.items.map((item) => (
                                    <li key={`${batch.id}-${item.filename}`} className="py-2">
                                        <p className="font-medium text-ink">{item.filename}</p>
                                        <p className="text-xs text-muted">
                                            {item.status} · email {item.email_status}
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
