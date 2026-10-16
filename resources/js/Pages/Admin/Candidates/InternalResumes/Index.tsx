import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import DetailHeader from '@/Components/organisms/DetailHeader';
import Button from '@/Components/atoms/Button';
import type { Paginator } from '@/types/jobs';

type RecordRow = {
    id: number;
    display_name: string;
    source: string;
    source_label: string;
    original_filename: string;
    file_size: number | null;
    linked_user: { name: string; username: string | null } | null;
    created_at: string | null;
    download_url: string;
};

type SourceOption = { value: string; label: string };

type Props = {
    records: Paginator<RecordRow>;
    filters: { q: string };
    sources: SourceOption[];
};

export default function InternalResumesIndex({ records, filters, sources }: Props) {
    const upload = useForm({
        display_name: '',
        source: sources[0]?.value ?? 'email',
        notes: '',
        file: null as File | null,
    });

    const submitUpload = (event: FormEvent) => {
        event.preventDefault();
        upload.post('/admin/candidates/internal-resumes', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => upload.reset('file', 'display_name', 'notes'),
        });
    };

    return (
        <AdminLayout
            title="Internal resumes"
            breadcrumbs={[
                { label: 'Admin', href: '/admin' },
                { label: 'Candidates', href: '/admin/candidates' },
                { label: 'Internal resumes' },
            ]}
        >
            <div className="space-y-6">
                <DetailHeader
                    headingLevel={2}
                    title="Offline and referral resumes"
                    description="Store CVs for candidates without portal accounts. Files stay on private storage; download is permission-gated."
                />

                <form onSubmit={submitUpload} className="grid gap-4 rounded-xl border border-line bg-white p-4 sm:grid-cols-2">
                    <label className="text-sm sm:col-span-2">
                        <span className="font-medium text-ink">Candidate name</span>
                        <input
                            className="mt-1 w-full rounded-md border border-line px-3 py-2"
                            value={upload.data.display_name}
                            onChange={(e) => upload.setData('display_name', e.target.value)}
                            required
                        />
                    </label>
                    <label className="text-sm">
                        <span className="font-medium text-ink">Source</span>
                        <select
                            className="mt-1 w-full rounded-md border border-line px-3 py-2"
                            value={upload.data.source}
                            onChange={(e) => upload.setData('source', e.target.value)}
                        >
                            {sources.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="text-sm">
                        <span className="font-medium text-ink">Resume file</span>
                        <input
                            type="file"
                            accept=".pdf,.doc,.docx,application/pdf"
                            className="mt-1 w-full text-sm"
                            onChange={(e) => upload.setData('file', e.target.files?.[0] ?? null)}
                            required
                        />
                    </label>
                    <label className="text-sm sm:col-span-2">
                        <span className="font-medium text-ink">Notes (optional)</span>
                        <textarea
                            className="mt-1 w-full rounded-md border border-line px-3 py-2"
                            rows={2}
                            value={upload.data.notes}
                            onChange={(e) => upload.setData('notes', e.target.value)}
                        />
                    </label>
                    <div className="sm:col-span-2">
                        <Button type="submit" loading={upload.processing} disabled={upload.processing}>
                            Store resume
                        </Button>
                    </div>
                </form>

                <form
                    className="flex flex-wrap gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        const q = (event.currentTarget.elements.namedItem('q') as HTMLInputElement).value;
                        router.get('/admin/candidates/internal-resumes', q ? { q } : {}, { preserveState: true });
                    }}
                >
                    <input
                        name="q"
                        defaultValue={filters.q}
                        placeholder="Search name or filename"
                        className="min-w-[12rem] flex-1 rounded-md border border-line px-3 py-2 text-sm"
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-xl border border-line bg-white">
                    <table className="min-w-full text-sm">
                        <thead className="border-b border-line bg-surface text-left text-xs uppercase tracking-wide text-muted">
                            <tr>
                                <th className="px-4 py-3">Candidate</th>
                                <th className="px-4 py-3">Source</th>
                                <th className="px-4 py-3">File</th>
                                <th className="px-4 py-3">Linked user</th>
                                <th className="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {records.data.map((row) => (
                                <tr key={row.id} className="border-b border-line last:border-0">
                                    <td className="px-4 py-3 font-medium text-ink">{row.display_name}</td>
                                    <td className="px-4 py-3 text-muted">{row.source_label}</td>
                                    <td className="px-4 py-3 text-muted">{row.original_filename}</td>
                                    <td className="px-4 py-3 text-muted">
                                        {row.linked_user?.name ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <a href={row.download_url} className="font-semibold text-brand hover:underline">
                                            Download
                                        </a>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
