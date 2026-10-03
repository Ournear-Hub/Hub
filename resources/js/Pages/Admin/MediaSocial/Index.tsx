import { router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import Pagination from '@/Components/Pagination';
import type { Paginator } from '@/types/jobs';
import type { MediaAssetAdmin, SocialLinkAdmin } from '@/types/media';
import type { SharedPageProps } from '@/types/page';

type Props = {
    tab: 'media' | 'social';
    media: Paginator<MediaAssetAdmin>;
    socialLinks: SocialLinkAdmin[];
};

const inputClass =
    'w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function MediaSocialIndex({ tab, media, socialLinks }: Props) {
    const { errors = {} } = usePage<SharedPageProps>().props;
    const upload = useForm<{ image: File | null }>({ image: null });
    const social = useForm({
        links: socialLinks.map((link) => ({
            id: link.id,
            url: link.url,
            is_enabled: link.is_enabled,
        })),
    });

    const openTab = (next: 'media' | 'social') => {
        router.get('/admin/media-social', { tab: next }, { preserveState: false });
    };

    const submitUpload = (event: FormEvent) => {
        event.preventDefault();
        upload.post('/admin/media-social/media', { forceFormData: true });
    };

    const submitSocial = (event: FormEvent) => {
        event.preventDefault();
        social.put('/admin/media-social/social');
    };

    const remove = (asset: MediaAssetAdmin) => {
        if (asset.in_use) {
            window.alert('Unassign this image from Website Content before deleting it.');
            return;
        }

        if (!window.confirm(`Delete ${asset.original_name}? This cannot be undone.`)) {
            return;
        }

        router.delete(`/admin/media-social/media/${asset.id}`);
    };

    const previewLinks = social.data.links
        .map((row, index) => {
            const platform = socialLinks[index];
            if (!platform || !row.is_enabled || !row.url.trim()) {
                return null;
            }

            return { ...platform, href: row.url.trim() };
        })
        .filter((row): row is SocialLinkAdmin & { href: string } => row !== null);

    return (
        <AdminLayout title="Media & Social">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight text-ink">Media & Social</h1>
                    <p className="mt-1 text-sm text-muted">
                        Upload website images and manage public social links. This is not a separate application.
                    </p>
                </div>
            </div>

            <div className="mt-6 flex flex-wrap gap-2 border-b border-line pb-3">
                <button
                    type="button"
                    onClick={() => openTab('media')}
                    className={`rounded-md px-4 py-2 text-sm font-semibold ${
                        tab === 'media' ? 'bg-brand text-white' : 'bg-white text-ink ring-1 ring-line'
                    }`}
                >
                    Media
                </button>
                <button
                    type="button"
                    onClick={() => openTab('social')}
                    className={`rounded-md px-4 py-2 text-sm font-semibold ${
                        tab === 'social' ? 'bg-brand text-white' : 'bg-white text-ink ring-1 ring-line'
                    }`}
                >
                    Social Links
                </button>
            </div>

            {tab === 'media' ? (
                <section className="mt-6 space-y-6">
                    <form onSubmit={submitUpload} className="rounded-xl border border-line bg-white p-5">
                        <h2 className="text-lg font-semibold text-ink">Upload image</h2>
                        <p className="mt-1 text-sm text-muted">JPG, PNG, or WEBP. Maximum 5 MB and 4096×4096 pixels.</p>
                        <div className="mt-4 flex flex-wrap items-end gap-3">
                            <input
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                className={inputClass}
                                onChange={(event) => upload.setData('image', event.target.files?.[0] ?? null)}
                            />
                            <button
                                type="submit"
                                disabled={upload.processing}
                                className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                            >
                                {upload.processing ? 'Uploading…' : 'Upload'}
                            </button>
                        </div>
                        {upload.errors.image || errors.image ? (
                            <p className="mt-2 text-sm text-danger">{upload.errors.image ?? errors.image}</p>
                        ) : null}
                    </form>

                    <div className="overflow-x-auto rounded-xl border border-line bg-white">
                        <table className="min-w-full text-left text-sm">
                            <thead className="border-b border-line bg-surface text-muted">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Preview</th>
                                    <th className="px-4 py-3 font-medium">Filename</th>
                                    <th className="px-4 py-3 font-medium">Type</th>
                                    <th className="px-4 py-3 font-medium">Size</th>
                                    <th className="px-4 py-3 font-medium">Uploaded</th>
                                    <th className="px-4 py-3 font-medium">Used on</th>
                                    <th className="px-4 py-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {media.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-8 text-muted">
                                            No images uploaded yet.
                                        </td>
                                    </tr>
                                ) : (
                                    media.data.map((asset) => (
                                        <tr key={asset.id} className="border-b border-line last:border-0 align-top">
                                            <td className="px-4 py-3">
                                                {asset.url ? (
                                                    <img
                                                        src={asset.url}
                                                        alt=""
                                                        className="h-16 w-16 rounded-md object-cover"
                                                    />
                                                ) : (
                                                    <span className="text-muted">Unavailable</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 font-medium text-ink">{asset.original_name}</td>
                                            <td className="px-4 py-3 text-muted">
                                                {asset.extension.toUpperCase()} · {asset.mime_type}
                                            </td>
                                            <td className="px-4 py-3 text-muted">{asset.size_label}</td>
                                            <td className="px-4 py-3 text-muted">{asset.created_at ?? '—'}</td>
                                            <td className="px-4 py-3 text-muted">
                                                {asset.used_on.length === 0 ? 'Unused' : asset.used_on.join(', ')}
                                            </td>
                                            <td className="px-4 py-3">
                                                <button
                                                    type="button"
                                                    className={`text-sm font-medium ${asset.in_use ? 'text-disabled' : 'text-danger'}`}
                                                    onClick={() => remove(asset)}
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    {errors.media ? <p className="text-sm text-danger">{errors.media}</p> : null}
                    <Pagination links={media.links} />
                </section>
            ) : (
                <form onSubmit={submitSocial} className="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_280px]">
                    <div className="space-y-4 rounded-xl border border-line bg-white p-5">
                        <h2 className="text-lg font-semibold text-ink">Social links</h2>
                        <p className="text-sm text-muted">
                            Store public https URLs only. Disabled platforms are hidden from the header and footer.
                        </p>
                        {social.data.links.map((row, index) => {
                            const platform = socialLinks[index];
                            if (!platform) {
                                return null;
                            }

                            return (
                                <article key={row.id} className="rounded-lg border border-line p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <h3 className="font-semibold text-ink">{platform.label}</h3>
                                        <label className="flex items-center gap-2 text-sm">
                                            <input
                                                type="checkbox"
                                                checked={row.is_enabled}
                                                onChange={(event) =>
                                                    social.setData(
                                                        'links',
                                                        social.data.links.map((item, itemIndex) =>
                                                            itemIndex === index
                                                                ? { ...item, is_enabled: event.target.checked }
                                                                : item,
                                                        ),
                                                    )
                                                }
                                            />
                                            Enabled
                                        </label>
                                    </div>
                                    <div className="mt-3">
                                        <label className="mb-1 block text-sm font-medium text-ink">URL</label>
                                        <input
                                            className={inputClass}
                                            value={row.url}
                                            placeholder="https://"
                                            onChange={(event) =>
                                                social.setData(
                                                    'links',
                                                    social.data.links.map((item, itemIndex) =>
                                                        itemIndex === index ? { ...item, url: event.target.value } : item,
                                                    ),
                                                )
                                            }
                                        />
                                        {social.errors[`links.${index}.url`] ? (
                                            <p className="mt-1 text-sm text-danger">{social.errors[`links.${index}.url`]}</p>
                                        ) : null}
                                    </div>
                                </article>
                            );
                        })}
                        <button
                            type="submit"
                            disabled={social.processing}
                            className="rounded-md bg-brand px-4 py-2 text-sm font-semibold text-white disabled:bg-disabled"
                        >
                            {social.processing ? 'Saving…' : 'Save changes'}
                        </button>
                    </div>
                    <aside className="rounded-xl border border-line bg-white p-4">
                        <h2 className="text-xs font-semibold uppercase tracking-wide text-muted">Public preview</h2>
                        {previewLinks.length === 0 ? (
                            <p className="mt-3 text-sm text-muted">No enabled links will appear on the website.</p>
                        ) : (
                            <ul className="mt-3 space-y-2 text-sm">
                                {previewLinks.map((item) => (
                                    <li key={item.platform}>
                                        <span className="font-medium text-ink">{item.label}</span>
                                        <p className="break-all text-muted">{item.href}</p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </aside>
                </form>
            )}
        </AdminLayout>
    );
}
