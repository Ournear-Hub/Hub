import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import DocumentGroupList from '@/Components/organisms/DocumentGroupList';
import type { AccountDocument, AccountFileOption } from '@/types/account';

type Props = {
    documents: AccountDocument[];
    types: AccountFileOption[];
    visibilities: AccountFileOption[];
    documentTypeDefault?: string;
};

export default function CandidateSupportingDocumentsSection({
    documents,
    types,
    visibilities,
    documentTypeDefault = 'certificate',
}: Props) {
    const form = useForm({
        file: null as File | null,
        document_type: documentTypeDefault,
        title: '',
        visibility: 'private',
        issue_date: '',
        expiry_date: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/account/documents', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset('file', 'title', 'issue_date', 'expiry_date'),
        });
    };

    const replace = (document: AccountDocument, event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        router.post(`/account/documents/${document.id}/replace`, data, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <section className="rounded-xl border border-line bg-white p-4 sm:p-6">
            <h2 className="text-lg font-semibold text-ink">Supporting documents</h2>
            <p className="mt-1 text-sm text-muted">
                Certificates and other professional files stay private unless you choose to share them later. They are
                separate from My Resume and are not sent with applications automatically.
            </p>

            <form onSubmit={submit} className="mt-6 space-y-3 border-t border-line pt-6">
                <h3 className="text-sm font-semibold text-ink">Upload document</h3>
                <div>
                    <label className="block text-sm font-medium text-ink" htmlFor="supporting-document-type">
                        Type
                    </label>
                    <select
                        id="supporting-document-type"
                        className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                        value={form.data.document_type}
                        onChange={(event) => form.setData('document_type', event.target.value)}
                    >
                        {types.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </select>
                    {form.errors.document_type ? <p className="mt-1 text-sm text-danger">{form.errors.document_type}</p> : null}
                </div>
                <div>
                    <label className="block text-sm font-medium text-ink" htmlFor="supporting-document-title">
                        Title
                    </label>
                    <input
                        id="supporting-document-title"
                        className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                        value={form.data.title}
                        onChange={(event) => form.setData('title', event.target.value)}
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium text-ink" htmlFor="supporting-document-file">
                        File (PDF, DOC, DOCX, JPG, or PNG, max 5 MB)
                    </label>
                    <input
                        id="supporting-document-file"
                        type="file"
                        className="mt-1 w-full text-sm text-ink"
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        onChange={(event) => form.setData('file', event.target.files?.[0] ?? null)}
                    />
                    {form.errors.file ? <p className="mt-1 text-sm text-danger">{form.errors.file}</p> : null}
                </div>
                <div className="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label className="block text-sm font-medium text-ink" htmlFor="supporting-issue-date">
                            Issue date
                        </label>
                        <input
                            id="supporting-issue-date"
                            type="date"
                            className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                            value={form.data.issue_date}
                            onChange={(event) => form.setData('issue_date', event.target.value)}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-ink" htmlFor="supporting-expiry-date">
                            Expiry date
                        </label>
                        <input
                            id="supporting-expiry-date"
                            type="date"
                            className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                            value={form.data.expiry_date}
                            onChange={(event) => form.setData('expiry_date', event.target.value)}
                        />
                        {form.errors.expiry_date ? <p className="mt-1 text-sm text-danger">{form.errors.expiry_date}</p> : null}
                    </div>
                </div>
                <div>
                    <label className="block text-sm font-medium text-ink" htmlFor="supporting-document-visibility">
                        Visibility
                    </label>
                    <select
                        id="supporting-document-visibility"
                        className="mt-1 w-full rounded-md border border-line px-3 py-2 text-sm"
                        value={form.data.visibility}
                        onChange={(event) => form.setData('visibility', event.target.value)}
                    >
                        {visibilities.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>
                <button
                    type="submit"
                    disabled={form.processing || !form.data.file}
                    className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95 disabled:bg-disabled"
                >
                    {form.processing ? 'Uploading…' : 'Upload document'}
                </button>
            </form>

            <div className="mt-8 border-t border-line pt-6">
                <DocumentGroupList
                    empty="No supporting documents uploaded yet."
                    items={documents.map((document) => ({
                        id: document.id,
                        group: document.document_type_label || 'Other documents',
                        identity: document.business_id,
                        title: document.title,
                        meta: [
                            document.original_filename,
                            document.status_label,
                            document.verification_status_label,
                            document.issue_date || document.expiry_date
                                ? `${document.issue_date ?? 'No issue date'} – ${document.expiry_date ?? 'No expiry'}`
                                : null,
                        ]
                            .filter(Boolean)
                            .join(' · '),
                        actions: (
                            <>
                                <a
                                    href={document.view_url}
                                    className="font-medium text-brand hover:underline"
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    View
                                </a>
                                <a href={document.download_url} className="font-medium text-brand hover:underline">
                                    Download
                                </a>
                                <button
                                    type="button"
                                    className="font-medium text-muted hover:underline"
                                    onClick={() =>
                                        router.put(`/account/documents/${document.id}`, {
                                            status: document.status === 'archived' ? 'active' : 'archived',
                                        })
                                    }
                                >
                                    {document.status === 'archived' ? 'Restore' : 'Archive'}
                                </button>
                                <button
                                    type="button"
                                    className="font-medium text-danger hover:underline"
                                    onClick={() => {
                                        if (window.confirm('Delete this document?')) {
                                            router.delete(`/account/documents/${document.id}`, { preserveScroll: true });
                                        }
                                    }}
                                >
                                    Delete
                                </button>
                            </>
                        ),
                        extra: (
                            <form className="mt-4 space-y-2" onSubmit={(event) => replace(document, event)}>
                                <label className="block text-sm font-medium text-ink" htmlFor={`replace-doc-${document.id}`}>
                                    Replace file
                                </label>
                                <input
                                    id={`replace-doc-${document.id}`}
                                    name="file"
                                    type="file"
                                    required
                                    className="w-full text-sm text-ink"
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                />
                                <button
                                    type="submit"
                                    className="rounded-md bg-brand px-3 py-1.5 text-sm font-semibold text-white hover:opacity-95"
                                >
                                    Replace
                                </button>
                            </form>
                        ),
                    }))}
                />
            </div>
            <p className="mt-4 text-xs text-muted">
                Upload is not verification. Documents stay unverified until a later review process.
            </p>
        </section>
    );
}
