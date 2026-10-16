import { Link, router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import DocumentGroupList from '@/Components/organisms/DocumentGroupList';
import AccountLayout from '@/Layouts/AccountLayout';
import type { AccountDocument, AccountFileOption } from '@/types/account';
import type { SharedPageProps } from '@/types/page';

type DocumentsProps = {
    documents: AccountDocument[];
    filters: { type: string | null };
    types: AccountFileOption[];
    visibilities: AccountFileOption[];
    statuses: AccountFileOption[];
};

export default function DocumentsIndex({ documents, filters, types, visibilities, statuses }: DocumentsProps) {
    const flash = usePage<SharedPageProps>().props.flash;
    const form = useForm({
        file: null as File | null,
        document_type: filters.type || 'certificate',
        title: '',
        visibility: 'private',
        issue_date: '',
        expiry_date: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/account/documents', {
            forceFormData: true,
            onSuccess: () => form.reset('file', 'title', 'issue_date', 'expiry_date'),
        });
    };

    const replace = (document: AccountDocument, event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        router.post(`/account/documents/${document.id}/replace`, data, { forceFormData: true });
    };

    return (
        <AccountLayout
            title="Documents"
            breadcrumbs={[
                { label: 'Account', href: '/account' },
                { label: 'Documents' },
            ]}
        >
            
                    {flash.success ? (
                        <p className="mb-4 rounded-md border border-success/20 bg-success/10 px-3 py-2 text-sm text-success">
                            {flash.success}
                        </p>
                    ) : null}
                    <div className="mb-6 text-sm">
                        <Link href="/account/resumes" className="font-medium text-brand">
                            Resume library
                        </Link>
                    </div>
                    <form className="mb-6" method="get">
                        <label className="block text-sm font-medium text-ink" htmlFor="document-filter">
                            Filter by type
                        </label>
                        <select
                            id="document-filter"
                            name="type"
                            className="mt-1 rounded-md border border-line px-3 py-2 text-sm"
                            defaultValue={filters.type ?? ''}
                            onChange={(event) =>
                                router.get('/account/documents', event.target.value ? { type: event.target.value } : {}, {
                                    preserveState: true,
                                })
                            }
                        >
                            <option value="">All types</option>
                            {types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                    </form>
                    <form onSubmit={submit} className="mb-8 space-y-3 rounded-xl border border-line bg-white p-6">
                        <h2 className="text-lg font-semibold text-ink">Upload document</h2>
                        <label className="block text-sm font-medium text-ink" htmlFor="document-type">
                            Type
                        </label>
                        <select
                            id="document-type"
                            className="w-full rounded-md border border-line px-3 py-2 text-sm"
                            value={form.data.document_type}
                            onChange={(event) => form.setData('document_type', event.target.value)}
                        >
                            {types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                        {form.errors.document_type ? <p className="text-sm text-danger">{form.errors.document_type}</p> : null}
                        <label className="block text-sm font-medium text-ink" htmlFor="document-title">
                            Title
                        </label>
                        <input
                            id="document-title"
                            className="w-full rounded-md border border-line px-3 py-2 text-sm"
                            value={form.data.title}
                            onChange={(event) => form.setData('title', event.target.value)}
                        />
                        <label className="block text-sm font-medium text-ink" htmlFor="document-file">
                            File (PDF, DOC, DOCX, JPG, or PNG, max 5 MB)
                        </label>
                        <input
                            id="document-file"
                            type="file"
                            required
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                            onChange={(event) => form.setData('file', event.target.files?.[0] ?? null)}
                        />
                        {form.errors.file ? <p className="text-sm text-danger">{form.errors.file}</p> : null}
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-ink" htmlFor="issue-date">
                                    Issue date
                                </label>
                                <input
                                    id="issue-date"
                                    type="date"
                                    className="w-full rounded-md border border-line px-3 py-2 text-sm"
                                    value={form.data.issue_date}
                                    onChange={(event) => form.setData('issue_date', event.target.value)}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-ink" htmlFor="expiry-date">
                                    Expiry date
                                </label>
                                <input
                                    id="expiry-date"
                                    type="date"
                                    className="w-full rounded-md border border-line px-3 py-2 text-sm"
                                    value={form.data.expiry_date}
                                    onChange={(event) => form.setData('expiry_date', event.target.value)}
                                />
                                {form.errors.expiry_date ? <p className="text-sm text-danger">{form.errors.expiry_date}</p> : null}
                            </div>
                        </div>
                        <label className="block text-sm font-medium text-ink" htmlFor="document-visibility">
                            Visibility
                        </label>
                        <select
                            id="document-visibility"
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
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white disabled:bg-disabled"
                        >
                            Upload document
                        </button>
                    </form>
                    <DocumentGroupList
                        empty="No documents uploaded yet."
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
                                    <a href={document.view_url} className="font-medium text-brand" target="_blank" rel="noreferrer">
                                        View
                                    </a>
                                    <a href={document.download_url} className="font-medium text-brand">
                                        Download
                                    </a>
                                    <button
                                        type="button"
                                        className="font-medium text-muted"
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
                                        className="font-medium text-danger"
                                        onClick={() => {
                                            if (window.confirm('Delete this document?')) {
                                                router.delete(`/account/documents/${document.id}`);
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
                                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                    />
                                    <button type="submit" className="rounded-md bg-brand px-3 py-1.5 text-sm font-semibold text-white">
                                        Replace
                                    </button>
                                </form>
                            ),
                        }))}
                    />
                    <p className="mt-4 text-xs text-muted">
                        Upload is not verification. Documents stay unverified until a later review process.
                        {statuses.length ? '' : ''}
                    </p>
                </AccountLayout>
    );
}
