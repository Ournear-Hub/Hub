import { Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

type ImageRow = {
    id: number | null;
    page_label: string;
    page: string;
    slot_key: string;
    slot_label: string;
    title: string | null;
    filename: string | null;
    alt_text: string | null;
    is_active: boolean;
    url: string | null;
    updated_at: string | null;
};

export default function Index({ images }: { images: ImageRow[] }) {
    return (
        <AdminLayout title="Page Images">
            <div className="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h1 className="text-xl font-bold text-ink">Page Images</h1>
                    <p className="mt-1 text-sm text-muted">Upload photographs for public page sections. Inactive images stay off the website.</p>
                </div>
                <Link href="/admin/page-images/create" className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white">
                    Upload image
                </Link>
            </div>
            <div className="overflow-x-auto rounded-xl border border-line bg-white">
                <table className="min-w-full text-left text-sm">
                    <thead className="border-b border-line text-xs uppercase text-muted">
                        <tr>
                            <th className="px-3 py-2">Preview</th>
                            <th className="px-3 py-2">Page</th>
                            <th className="px-3 py-2">Section</th>
                            <th className="px-3 py-2">Title</th>
                            <th className="px-3 py-2">Filename</th>
                            <th className="px-3 py-2">Alt text</th>
                            <th className="px-3 py-2">Status</th>
                            <th className="px-3 py-2">Updated</th>
                            <th className="px-3 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {images.map((image) => (
                            <tr key={`${image.page}-${image.slot_key}`} className="border-b border-line last:border-0">
                                <td className="px-3 py-2">
                                    {image.url ? (
                                        <img src={image.url} alt="" className="h-12 w-16 rounded object-cover" />
                                    ) : (
                                        <span className="text-xs text-muted">No image</span>
                                    )}
                                </td>
                                <td className="px-3 py-2">{image.page_label}</td>
                                <td className="px-3 py-2">{image.slot_label}</td>
                                <td className="px-3 py-2">{image.title ?? '—'}</td>
                                <td className="px-3 py-2">{image.filename ?? '—'}</td>
                                <td className="max-w-[12rem] px-3 py-2">{image.alt_text ?? '—'}</td>
                                <td className="px-3 py-2">{image.id === null ? 'Not set' : image.is_active ? 'Active' : 'Inactive'}</td>
                                <td className="px-3 py-2">{image.updated_at ?? '—'}</td>
                                <td className="px-3 py-2">
                                    <Link
                                        href={
                                            image.id
                                                ? `/admin/page-images/${image.id}/edit`
                                                : `/admin/page-images/create?page=${image.page}&slot=${image.slot_key}`
                                        }
                                        className="font-semibold text-brand"
                                    >
                                        {image.id ? 'Replace' : 'Upload'}
                                    </Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
